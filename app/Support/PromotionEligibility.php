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

    /** Evaluate and save the tag without advancing the learner. */
    public static function synchronize(Enrollment $enrollment): array
    {
        $enrollment->loadMissing(['academicYear', 'curriculumGradeLevel', 'section.gradeLevel']);
        $currentGradeId = $enrollment->section?->grade_ID ?: $enrollment->curriculumGradeLevel?->grade_ID;
        // Recognize promotions completed before the Promoted status was introduced.
        if ($enrollment->promotion_status !== PromotionStatus::PROMOTED
            && $enrollment->academicYear?->start_date && $currentGradeId
            && Enrollment::query()
                ->where('student_ID', $enrollment->student_ID)
                ->whereHas('academicYear', fn ($query) => $query->whereDate('start_date', '>', $enrollment->academicYear->start_date))
                ->whereHas('curriculumGradeLevel', fn ($query) => $query->where('grade_ID', '>', $currentGradeId))
                ->exists()) {
            $enrollment->update(['promotion_status' => PromotionStatus::PROMOTED]);
            $enrollment->unsetRelation('promotionStatus');
        }

        if ($enrollment->promotion_status === PromotionStatus::PROMOTED) {
            return ['status' => PromotionStatus::PROMOTED, 'reason' => 'Already promoted.', 'subject_averages' => []];
        }

        $evaluation = self::evaluate($enrollment);
        $enrollment->promotion_status = $evaluation['status'];
        if ($enrollment->isDirty('promotion_status_ID')) {
            $enrollment->save();
            $enrollment->unsetRelation('promotionStatus');
        }

        return $evaluation;
    }

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

        $failingGrades = $grades->flatten()
            ->filter(fn (StudentSubjectGrade $grade): bool => (float) $grade->numeric_grade < self::PASSING_GRADE)
            ->count();
        $status = match (true) {
            $failingGrades === 0 => PromotionStatus::ELIGIBLE,
            $failingGrades <= 2 => PromotionStatus::CONDITIONALLY_PROMOTED,
            default => PromotionStatus::RETAINED,
        };

        return [
            'status' => $status,
            'reason' => match ($status) {
                PromotionStatus::CONDITIONALLY_PROMOTED => 'The learner has one or two failing grades. Conditional promotion does not permit advancement to the next grade.',
                PromotionStatus::RETAINED => 'The learner has more than two failing grades.',
                default => '',
            },
            'subject_averages' => $averages,
        ];
    }

    /** @return array{status: string, reason: string, subject_averages: array<int, float>} */
    private static function pending(string $reason): array
    {
        return ['status' => PromotionStatus::PENDING, 'reason' => $reason, 'subject_averages' => []];
    }
}
