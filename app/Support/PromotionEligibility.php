<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\GradeStatus;
use App\Models\GradingPeriodStatus;
use App\Models\GradingTerm;
use App\Models\PromotionStatus;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;

/** Evaluates a completed enrollment; it never creates the next enrollment. */
class PromotionEligibility
{
    public const PASSING_GRADE = 75;

    /** @return array{status: string, reason: string, subject_averages: array<int, float>} */
    public static function evaluate(Enrollment $enrollment): array
    {
        $enrollment->loadMissing(['section.gradeLevel', 'gradingSemester.status']);
        $section = $enrollment->section;
        if (! $section) {
            return self::pending('The learner has no section for this school year.');
        }

        if (GradingTerm::isSeniorHighSection($section)) {
            if ((int) $enrollment->gradingSemester?->grading_period_status_ID !== GradingPeriodStatus::closedId()) {
                return self::pending('The learner can be promoted after their Senior High semester is closed.');
            }
        } elseif (! GradingTerm::areJuniorHighTermsClosed()) {
            return self::pending('The learner can be promoted after all Junior High terms are closed.');
        }

        $studentSubjectIds = StudentSubject::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->pluck('curr_subj_ID');

        $assignments = TeacherSubjectAssignment::query()
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->whereIn('curr_subj_ID', $studentSubjectIds)
            ->get(['assignment_ID']);
        $periodKeys = collect(GradingTerm::isSeniorHighSection($section)
            ? GradingTerm::seniorHighPeriods($enrollment->semester)
            : GradingTerm::configuredPeriods())
            ->pluck('key')
            ->values();
        if ($assignments->isEmpty() || $periodKeys->isEmpty()) {
            return self::pending('Subjects or final grading periods have not been configured.');
        }

        $grades = StudentSubjectGrade::query()
            ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->whereIn('term_ID', $periodKeys->map(fn (string $key) => StudentSubjectGrade::termIdForPeriodKey($key)))
            ->where('grade_status_ID', GradeStatus::idFor(GradeStatus::RELEASED))
            ->get()
            ->groupBy('assignment_ID');

        $averages = [];
        foreach ($assignments as $assignment) {
            $subjectGrades = $grades->get($assignment->assignment_ID, collect());
            if ($subjectGrades->count() !== $periodKeys->count() || $subjectGrades->contains(fn ($grade) => $grade->numeric_grade === null)) {
                return self::pending('Complete released grades are required before promotion.');
            }
            $averages[$assignment->assignment_ID] = round((float) $subjectGrades->avg('numeric_grade'), 2);
        }

        $status = $grades->flatten()->every(fn (StudentSubjectGrade $grade): bool => (float) $grade->numeric_grade >= self::PASSING_GRADE)
            ? PromotionStatus::ELIGIBLE
            : PromotionStatus::RETAINED;

        return [
            'status' => $status,
            'reason' => $status === PromotionStatus::RETAINED ? 'The learner has one or more failing grades.' : '',
            'subject_averages' => $averages,
        ];
    }

    /** @return array{status: string, reason: string, subject_averages: array<int, float>} */
    private static function pending(string $reason): array
    {
        return ['status' => PromotionStatus::PENDING, 'reason' => $reason, 'subject_averages' => []];
    }
}
