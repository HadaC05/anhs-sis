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
                ->forPeriodKey($gradingPeriod)
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
     * Build summaries in bulk. Period settings are resolved once per school level
     * and semester, and grade records are counted in SQL rather than hydrated.
     *
     * @param iterable<TeacherSubjectAssignment> $assignments
     * @return array<int, list<array<string, mixed>>>
     */
    public static function termSummariesForAssignments(iterable $assignments, bool $includeFuturePeriods = false): array
    {
        $assignments = collect($assignments);
        if ($assignments->isEmpty()) {
            return [];
        }

        $ids = $assignments->pluck('assignment_ID');
        $counts = StudentSubjectGrade::query()
            ->whereIn('assignment_ID', $ids)
            ->select('assignment_ID', 'term_ID', 'grade_status_ID')
            ->selectRaw('COUNT(*) as record_count')
            ->groupBy('assignment_ID', 'term_ID', 'grade_status_ID')
            ->get()
            ->groupBy('assignment_ID');
        $unlocks = AssignmentGradeTermUnlock::query()
            ->whereIn('assignment_ID', $ids)
            ->get(['assignment_ID', 'grading_period'])
            ->groupBy('assignment_ID');
        $juniorTermIds = GradingTerm::query()->juniorHigh()->pluck('term_ID', 'key');
        $statusIds = GradeStatus::idsBySlug();
        $contexts = [];
        $summaries = [];

        foreach ($assignments as $assignment) {
            $section = $assignment->section;
            $semester = $assignment->curriculumSubject?->semester;
            $contextKey = GradingTerm::isSeniorHighSection($section) ? 'senior:'.($semester ?? 'all') : 'junior';
            if (! isset($contexts[$contextKey])) {
                $contexts[$contextKey] = [
                    'periods' => $includeFuturePeriods ? GradingTerm::periodsForSection($section, $semester) : GradingTerm::openPeriodsForSection($section, $semester),
                    'available' => $includeFuturePeriods ? array_column(GradingTerm::openPeriodsForSection($section, $semester), 'key') : null,
                    'current' => GradingTerm::currentEditablePeriodKeyForSection($section, $semester),
                ];
            }
            $context = $contexts[$contextKey];
            $assignmentCounts = $counts->get($assignment->assignment_ID, collect())->groupBy('term_ID');
            $unlockedKeys = $unlocks->get($assignment->assignment_ID, collect())->pluck('grading_period')->all();
            $summaries[$assignment->assignment_ID] = [];

            foreach ($context['periods'] as $period) {
                $termId = $period['term_ID'] ?? $juniorTermIds->get($period['key']);
                $termCounts = $assignmentCounts->get($termId, collect());
                $byStatus = $termCounts->pluck('record_count', 'grade_status_ID');
                $summary = [
                    'key' => $period['key'],
                    'label' => $period['label'],
                    'total' => (int) $termCounts->sum('record_count'),
                    'term_ID' => $termId,
                ];
                foreach (GradeStatus::slugs() as $status) {
                    $summary[$status] = (int) $byStatus->get($statusIds[$status] ?? 0, 0);
                }
                $isCurrent = $period['key'] === $context['current'];
                $isAvailable = $context['available'] === null || in_array($period['key'], $context['available'], true);
                $lockedCount = array_sum(array_map(fn (string $status): int => $summary[$status], GradeStatus::teacherLockedSlugs()));
                $summaries[$assignment->assignment_ID][] = $summary + [
                    'is_school_locked' => ! $isCurrent,
                    'is_registrar_unlocked' => in_array($period['key'], $unlockedKeys, true),
                    'is_current_term' => $isCurrent,
                    'can_unlock' => $isAvailable && (! $isCurrent || $lockedCount > 0),
                    'is_available' => $isAvailable,
                ];
            }
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    public static function termSummary(TeacherSubjectAssignment $assignment, array $period): array
    {
        $grades = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->forPeriodKey($period['key'])
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
