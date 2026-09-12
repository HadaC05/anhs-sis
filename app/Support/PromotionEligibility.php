<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\PromotionStatus;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;

/** Evaluates a completed enrollment; it never creates the next enrollment. */
class PromotionEligibility
{
    public const PASSING_GRADE = 75;

    /** @return array{status: string, reason: string, subject_averages: array<int, float>} */
    public static function evaluate(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('section.gradeLevel');
        $section = $enrollment->section;
        if (! $section) {
            return self::pending('The learner has no section for this school year.');
        }

        $assignments = TeacherSubjectAssignment::query()
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->get(['assignment_ID']);
        $periodKeys = collect(GradingTerm::periodsForSection($section))->pluck('key')->values();
        if ($assignments->isEmpty() || $periodKeys->isEmpty()) {
            return self::pending('Subjects or final grading periods have not been configured.');
        }

        $grades = StudentSubjectGrade::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->whereIn('grading_period', $periodKeys)
            ->where('grade_status_ID', GradeStatus::idFor(GradeStatus::RELEASED))
            ->get()
            ->groupBy('assignment_ID');

        $averages = [];
        foreach ($assignments as $assignment) {
            $subjectGrades = $grades->get($assignment->assignment_ID, collect());
            if ($subjectGrades->count() !== $periodKeys->count() || $subjectGrades->contains(fn ($grade) => $grade->numeric_grade === null)) {
                return self::pending('All released grades are required before promotion can be evaluated.');
            }
            $averages[$assignment->assignment_ID] = round((float) $subjectGrades->avg('numeric_grade'), 2);
        }

        $status = collect($averages)->every(fn (float $average): bool => $average >= self::PASSING_GRADE)
            ? PromotionStatus::ELIGIBLE
            : PromotionStatus::RETAINED;

        return ['status' => $status, 'reason' => '', 'subject_averages' => $averages];
    }

    /** @return array{status: string, reason: string, subject_averages: array<int, float>} */
    private static function pending(string $reason): array
    {
        return ['status' => PromotionStatus::PENDING, 'reason' => $reason, 'subject_averages' => []];
    }
}
