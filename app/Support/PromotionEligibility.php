<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeStatus;
use App\Models\GradingPeriodStatus;
use App\Models\GradingTerm;
use App\Models\PromotionStatus;
use App\Models\Sf9Configuration;
use App\Models\StudentObservedValue;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;

/** Evaluates a completed enrollment; it never creates the next enrollment. */
class PromotionEligibility
{
    public const PASSING_GRADE = 75;

    /** Evaluate a bounded batch with shared queries, then write only changed statuses. */
    public static function synchronizeMany(\Illuminate\Database\Eloquent\Collection $enrollments): array
    {
        if ($enrollments->isEmpty()) {
            return [];
        }
        $enrollments->loadMissing(['academicYear', 'curriculumGradeLevel', 'section.gradeLevel', 'section.curriculum', 'gradingSemester.status', 'studentSubjects', 'sf9Comments']);
        $laterEnrollments = Enrollment::query()->with(['academicYear', 'curriculumGradeLevel'])
            ->whereIn('student_ID', $enrollments->pluck('student_ID'))->get()->groupBy('student_ID');
        $assignments = TeacherSubjectAssignment::query()->whereIn('section_ID', $enrollments->pluck('section_ID')->filter())
            ->get()->groupBy('section_ID');
        $grades = StudentSubjectGrade::query()->with('studentSubject')
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->modelKeys()))
            ->get()
            ->groupBy(fn ($grade) => $grade->studentSubject->enrollment_ID);
        $observedValues = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $enrollments->modelKeys())
            ->get()
            ->groupBy('enrollment_ID');
        $attendance = EnrollmentMonthlyAttendance::query()
            ->whereIn('enrollment_ID', $enrollments->modelKeys())
            ->get()
            ->groupBy('enrollment_ID');
        $closed = GradingTerm::areJuniorHighTermsClosed();
        $statusIds = PromotionStatus::query()->pluck('promotion_status_ID', 'slug')->all();
        $closedSemesterId = GradingPeriodStatus::closedId();
        $periods = [];
        $evaluations = [];
        $changes = [];
        foreach ($enrollments as $enrollment) {
            $gradeId = $enrollment->section?->grade_ID ?: $enrollment->curriculumGradeLevel?->grade_ID;
            $promoted = $enrollment->promotion_status === PromotionStatus::PROMOTED
                || ($gradeId && $enrollment->academicYear?->start_date && $laterEnrollments->get($enrollment->student_ID, collect())->contains(
                    fn ($later) => $later->academicYear?->start_date > $enrollment->academicYear->start_date
                        && $later->curriculumGradeLevel?->grade_ID > $gradeId
                ));
            $key = GradingTerm::isSeniorHighSection($enrollment->section) ? 'shs:'.$enrollment->semester : 'jhs';
            if (! isset($periods[$key])) {
                $keys = collect($key === 'jhs' ? GradingTerm::configuredPeriods() : GradingTerm::seniorHighPeriods($enrollment->semester))->pluck('key');
                $periods[$key] = ['keys' => $keys, 'ids' => $keys->map(fn ($key) => StudentSubjectGrade::termIdForPeriodKey($key))];
            }
            $evaluation = $promoted
                ? ['status' => PromotionStatus::PROMOTED, 'reason' => 'Already promoted.', 'subject_averages' => []]
                : self::evaluate($enrollment, [
                    'closed' => $closed,
                    'closed_semester_id' => $closedSemesterId,
                    'periods' => $periods[$key],
                    'assignments' => $assignments->get($enrollment->section_ID, collect())->where('SY_ID', $enrollment->SY_ID)
                        ->whereIn('subject_ID', $enrollment->studentSubjects->pluck('subject_ID')),
                    'grades' => $grades->get($enrollment->enrollment_ID, collect()),
                    'observed_values' => $observedValues->get($enrollment->enrollment_ID, collect()),
                    'attendance' => $attendance->get($enrollment->enrollment_ID, collect()),
                ]);
            $evaluations[$enrollment->enrollment_ID] = $evaluation;
            $statusId = (int) $statusIds[$evaluation['status']];
            if ((int) $enrollment->promotion_status_ID !== $statusId) {
                $changes[$statusId][] = $enrollment->enrollment_ID;
                $enrollment->promotion_status_ID = $statusId;
                $enrollment->unsetRelation('promotionStatus');
            }
        }
        foreach ($changes as $statusId => $ids) {
            Enrollment::query()->whereIn('enrollment_ID', $ids)
                ->where('promotion_status_ID', '!=', $statusIds[PromotionStatus::PROMOTED])
                ->update(['promotion_status_ID' => $statusId]);
        }

        return $evaluations;
    }

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
    public static function evaluate(Enrollment $enrollment, ?array $batch = null): array
    {
        $enrollment->loadMissing(['section.gradeLevel', 'gradingSemester.status']);
        $section = $enrollment->section;
        if (! $section) {
            return self::pending('The learner has no section for this school year.');
        }

        if (GradingTerm::isSeniorHighSection($section)) {
            if ((int) $enrollment->gradingSemester?->grading_period_status_ID !== ($batch['closed_semester_id'] ?? GradingPeriodStatus::closedId())) {
                return self::pending('The learner can be promoted after their Senior High semester is closed.');
            }
        } elseif (! ($batch['closed'] ?? GradingTerm::areJuniorHighTermsClosed())) {
            return self::pending('The learner can be promoted after all Junior High terms are closed.');
        }

        $studentSubjectIds = $batch !== null ? $enrollment->studentSubjects->pluck('subject_ID') : StudentSubject::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->pluck('subject_ID');

        $assignments = $batch['assignments'] ?? TeacherSubjectAssignment::query()
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->whereIn('subject_ID', $studentSubjectIds)
            ->get();
        $periodKeys = $batch['periods']['keys'] ?? collect(GradingTerm::isSeniorHighSection($section)
            ? GradingTerm::seniorHighPeriods($enrollment->semester)
            : GradingTerm::configuredPeriods())
            ->pluck('key')
            ->values();
        if ($assignments->isEmpty() || $periodKeys->isEmpty()) {
            return self::pending('Subjects or final grading periods have not been configured.');
        }

        $grades = $batch !== null ? $batch['grades']->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->whereIn('term_ID', $batch['periods']['ids']) : StudentSubjectGrade::query()
            ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->whereIn('term_ID', $periodKeys->map(fn (string $key) => StudentSubjectGrade::termIdForPeriodKey($key)))
            ->get();

        // Use the same calculated MAPEH subject as reports. Keep unreleased
        // component entries visible to the calculator so they block legacy fallback.
        $assignments = MapehGrades::assignments($section, $assignments);
        $grades = MapehGrades::grades(
            $assignments,
            $grades->groupBy('assignment_ID')->map(fn ($items) => $items->keyBy('grading_period')),
            $periodKeys->all(),
            true,
        );
        $assignments = $assignments->reject(fn ($assignment) => $assignment->mapeh_component);
        $grades = $grades->only($assignments->pluck('assignment_ID')->all())
            ->map(fn ($items) => $items->filter(fn ($grade) => $grade->status === GradeStatus::RELEASED));

        $averages = [];
        foreach ($assignments as $assignment) {
            $subjectGrades = $grades->get($assignment->assignment_ID, collect());
            if ($subjectGrades->count() !== $periodKeys->count() || $subjectGrades->contains(fn ($grade) => $grade->numeric_grade === null)) {
                return self::pending('Complete released grades are required before promotion.');
            }
            $averages[$assignment->assignment_ID] = round((float) $subjectGrades->avg('numeric_grade'), 2);
        }

        if (Sf9Configuration::usesTeacherComments($section)) {
            $comments = $enrollment->sf9Comments->keyBy('grading_period');
            if ($periodKeys->contains(fn ($key) => trim($comments->get($key)?->comment ?? '') === '')) {
                return self::pending('Complete teacher comments / remarks are required before promotion.');
            }
        } elseif (! self::hasCompleteObservedValues($enrollment, $periodKeys, $batch)) {
            return self::pending('Complete observed values are required before promotion.');
        }

        if (! self::hasCompleteAttendance($enrollment, $batch)) {
            return self::pending('Complete attendance records are required before promotion.');
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

    private static function hasCompleteObservedValues(Enrollment $enrollment, $periodKeys, ?array $batch): bool
    {
        $statementKeys = collect(Sf9ReportCardBuilder::observedValueStatements())->pluck('key');
        $values = $batch !== null
            ? $batch['observed_values']
            : StudentObservedValue::query()->where('enrollment_ID', $enrollment->enrollment_ID)->get();
        $values = $values
            ->whereIn('statement_key', $statementKeys)
            ->whereIn('grading_period', $periodKeys)
            ->filter(fn (StudentObservedValue $value): bool => array_key_exists($value->marking, Sf9ReportCardBuilder::observedValueMarkings()))
            ->unique(fn (StudentObservedValue $value): string => $value->statement_key.'|'.$value->grading_period);

        return $values->count() === $statementKeys->count() * $periodKeys->count();
    }

    private static function hasCompleteAttendance(Enrollment $enrollment, ?array $batch): bool
    {
        $months = array_keys($enrollment->academicYear?->attendanceMonths() ?? []);
        if ($months === []) {
            return false;
        }

        $records = $batch !== null
            ? $batch['attendance']
            : EnrollmentMonthlyAttendance::query()->where('enrollment_ID', $enrollment->enrollment_ID)->get();

        return $records->whereIn('month', $months)->unique('month')->count() === count($months);
    }
}
