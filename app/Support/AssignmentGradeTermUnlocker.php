<?php

namespace App\Support;

use App\Models\AssignmentGradeTermUnlock;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\DB;

class AssignmentGradeTermUnlocker
{
    /**
     * @return list<string>
     */
    public static function unlockedPeriodKeysFor(int $assignmentId): array
    {
        return AssignmentGradeTermUnlock::query()
            ->where('assignment_ID', $assignmentId)
            ->pluck('grading_period')
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function editablePeriodKeysFor(int $assignmentId): array
    {
        $assignment = TeacherSubjectAssignment::query()
            ->with(['section.gradeLevel', 'curriculumSubject'])
            ->find($assignmentId);

        $keys = array_filter([
            GradingTerm::currentEditablePeriodKeyForSection(
                $assignment?->section,
                $assignment?->curriculumSubject?->semester,
            ),
            ...self::unlockedPeriodKeysFor($assignmentId),
        ]);

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    public static function lockedPeriodKeysForAssignment(int $assignmentId, array $openPeriodKeys): array
    {
        $editableKeys = self::editablePeriodKeysFor($assignmentId);

        return array_values(array_diff($openPeriodKeys, $editableKeys));
    }

    public static function unlock(TeacherSubjectAssignment $assignment, string $gradingPeriod, ?int $staffId, ?string $notes = null): int
    {
        return DB::transaction(function () use ($assignment, $gradingPeriod, $staffId, $notes): int {
            AssignmentGradeTermUnlock::query()->updateOrCreate(
                [
                    'assignment_ID' => $assignment->assignment_ID,
                    'grading_period' => $gradingPeriod,
                ],
                [
                    'unlocked_by' => $staffId,
                    'notes' => $notes,
                    'unlocked_at' => now(),
                ]
            );

            return StudentSubjectGrade::query()
                ->where('assignment_ID', $assignment->assignment_ID)
                ->where('grading_period', $gradingPeriod)
                ->whereStatus(StudentSubjectGrade::teacherLockedStatuses())
                ->update([
                    'grade_status_ID' => GradeStatus::idFor(GradeStatus::DRAFT),
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function termSummary(TeacherSubjectAssignment $assignment, array $period): array
    {
        $grades = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->where('grading_period', $period['key'])
            ->get();

        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $isSchoolLocked = in_array($period['key'], GradingTerm::lockedPeriodKeysForSection($section, $semester), true);
        $isRegistrarUnlocked = AssignmentGradeTermUnlock::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->where('grading_period', $period['key'])
            ->exists();
        $lockedCount = $grades->filter(fn (StudentSubjectGrade $grade): bool => $grade->isTeacherLocked())->count();
        $isCurrentTerm = $period['key'] === GradingTerm::currentEditablePeriodKeyForSection($section, $semester);
        $canUnlock = ($isSchoolLocked || $lockedCount > 0) && ! ($isCurrentTerm && $lockedCount === 0 && ! $isSchoolLocked);

        return [
            'key' => $period['key'],
            'label' => $period['label'],
            'total' => $grades->count(),
            'draft' => $grades->where('status', 'draft')->count(),
            'submitted' => $grades->where('status', 'submitted')->count(),
            'approved' => $grades->where('status', 'approved')->count(),
            'released' => $grades->where('status', 'released')->count(),
            'is_school_locked' => $isSchoolLocked,
            'is_registrar_unlocked' => $isRegistrarUnlocked,
            'is_current_term' => $isCurrentTerm,
            'can_unlock' => $canUnlock || ($lockedCount > 0),
        ];
    }
}
