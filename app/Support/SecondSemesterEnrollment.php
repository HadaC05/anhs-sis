<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradingSemester;
use App\Models\PromotionStatus;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles learner-controlled Senior High progression between semester offerings.
 *
 * The historic class name is retained because the public route already uses it,
 * but the workflow now covers all four Grade 11/12 semesters and completion.
 */
class SecondSemesterEnrollment
{
    /** @return array<string, mixed> */
    public static function context(Student $student): array
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $currentEnrollment = self::latestEnrollment($student);
        $targetEnrollment = null;
        $targetCurriculum = null;
        $targetYear = null;
        $electives = collect();
        $eligible = false;
        $canComplete = false;
        $completed = false;
        $reason = null;
        $targetGradeLabel = null;
        $targetSemester = null;

        if (! $currentEnrollment) {
            $reason = 'This progression is only available to Senior High School learners.';
        } else {
            $gradeLabel = self::gradeLabel($currentEnrollment);
            $semester = $currentEnrollment->gradingSemester?->key;
            $completed = $currentEnrollment->promotion_status === PromotionStatus::COMPLETED_SENIOR_HIGH;

            if ($completed) {
                $reason = 'You have completed Grade 12 and Senior High School.';
            } elseif ($gradeLabel === 'Grade 12' && $semester === GradingSemester::SECOND) {
                if ($currentEnrollment->enrollment_status !== EnrollmentStatus::ENROLLED) {
                    $reason = 'Your Grade 12 second-semester enrollment must be fully enrolled before completion can be recorded.';
                } elseif ($currentEnrollment->promotion_status !== PromotionStatus::ELIGIBLE) {
                    $reason = 'Your Grade 12 completion has not yet been confirmed by the school.';
                } else {
                    $canComplete = true;
                }
            } else {
                [$targetGradeLabel, $targetSemester] = self::nextStage($gradeLabel, $semester);

                if (! $targetGradeLabel || ! $targetSemester) {
                    $reason = 'Your current Senior High School enrollment cannot be advanced.';
                } elseif (! $activeYear) {
                    $reason = 'No active school year is configured yet.';
                } elseif ($currentEnrollment->enrollment_status !== EnrollmentStatus::ENROLLED) {
                    $reason = 'Your current semester enrollment must be fully enrolled before you can continue.';
                } elseif ($currentEnrollment->promotion_status !== PromotionStatus::ELIGIBLE) {
                    $reason = 'Your current semester eligibility has not yet been confirmed by the school.';
                } else {
                    $targetYear = $targetGradeLabel === $gradeLabel
                        ? $currentEnrollment->academicYear
                        : $activeYear;

                    if ($targetGradeLabel !== $gradeLabel
                        && (! $targetYear || ! $currentEnrollment->academicYear
                            || $targetYear->start_date <= $currentEnrollment->academicYear->start_date)) {
                        $reason = 'The next school year must be activated before you can enroll in Grade 12.';
                    } else {
                        $targetCurriculum = Curriculum::query()
                            ->whereHas('gradeLevel', fn ($query) => $query->where('grade_label', $targetGradeLabel))
                            ->where('cluster_ID', $currentEnrollment->clusterId)
                            ->whereHas('gradingSemester', fn ($query) => $query->where('key', $targetSemester))
                            ->whereHas('dataStatus', fn ($query) => $query->where('key', 'active'))
                            ->first();

                        if (! $targetCurriculum) {
                            $reason = "The matching {$targetGradeLabel} ".self::semesterLabel($targetSemester).' curriculum is not available yet.';
                        } else {
                            $targetEnrollment = Enrollment::query()
                                ->where('student_ID', $student->getKey())
                                ->where('SY_ID', $targetYear->SY_ID)
                                ->where('curriculum_grade_level_ID', $targetCurriculum->curriculum_ID)
                                ->first();

                            if ($targetEnrollment) {
                                $reason = 'This semester enrollment has already been processed.';
                            } else {
                                $trackId = (int) ($currentEnrollment->track_ID ?: $currentEnrollment->cluster?->track_ID);
                                $electives = Subject::query()
                                    ->with('cluster')
                                    ->where('status', 'active')
                                    ->whereHas('subjectType', fn ($query) => $query->where('key', 'elective'))
                                    ->whereHas('cluster', fn ($query) => $query->where('track_ID', $trackId))
                                    ->orderBy('title')
                                    ->get();

                                $eligible = $electives->isNotEmpty();
                                if (! $eligible) {
                                    $reason = 'No active electives are currently available for your track.';
                                }
                            }
                        }
                    }
                }
            }
        }

        $track = $currentEnrollment?->track ?: $currentEnrollment?->cluster?->track;
        $requiredElectiveCount = $track?->isAcademic() ? 2 : 1;

        // Compatibility aliases keep older integrations working.
        $firstSemesterEnrollment = $currentEnrollment;
        $secondSemesterEnrollment = $targetEnrollment;

        return compact(
            'activeYear', 'currentEnrollment', 'targetEnrollment', 'targetCurriculum', 'targetYear',
            'electives', 'eligible', 'canComplete', 'completed', 'reason', 'track',
            'requiredElectiveCount', 'targetGradeLabel', 'targetSemester',
            'firstSemesterEnrollment', 'secondSemesterEnrollment',
        );
    }

    /** @param list<int> $electiveIds */
    public static function enroll(Student $student, array $electiveIds): Enrollment
    {
        return DB::transaction(function () use ($student, $electiveIds): Enrollment {
            Student::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $context = self::context($student);

            if (! $context['eligible']) {
                throw ValidationException::withMessages([
                    'enrollment' => $context['reason'] ?: 'You are not eligible to advance to the next semester.',
                ]);
            }

            $electiveIds = collect($electiveIds)->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $requiredCount = (int) $context['requiredElectiveCount'];

            if (count($electiveIds) !== $requiredCount) {
                $label = $requiredCount === 2 ? 'two different electives' : 'one elective';
                throw ValidationException::withMessages(['elective_ids' => "Please choose {$label} for your track."]);
            }

            $validElectiveIds = $context['electives']->pluck('subject_ID')->map(fn ($id): int => (int) $id);
            if (collect($electiveIds)->diff($validElectiveIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'elective_ids' => 'Every selection must be an active elective offered for your track.',
                ]);
            }

            /** @var Enrollment $currentEnrollment */
            $currentEnrollment = $context['currentEnrollment'];
            /** @var Curriculum $targetCurriculum */
            $targetCurriculum = $context['targetCurriculum'];
            /** @var AcademicYear $targetYear */
            $targetYear = $context['targetYear'];
            $changesGrade = $context['targetGradeLabel'] !== self::gradeLabel($currentEnrollment);

            $enrollment = Enrollment::query()->create([
                'student_ID' => $student->getKey(),
                'section_ID' => null,
                'SY_ID' => $targetYear->SY_ID,
                'curriculum_grade_level_ID' => $targetCurriculum->curriculum_ID,
                'track_ID' => $currentEnrollment->track_ID ?: $currentEnrollment->cluster?->track_ID,
                'learner_type_ID' => $currentEnrollment->learner_type_ID,
                'last_grade_level_completed' => $changesGrade
                    ? self::gradeLabel($currentEnrollment)
                    : $currentEnrollment->last_grade_level_completed,
                'last_school_year_completed' => $changesGrade
                    ? $currentEnrollment->academicYear?->school_year
                    : $currentEnrollment->last_school_year_completed,
                'last_school_attended' => $currentEnrollment->last_school_attended,
                'school_id_from_previous_school' => $currentEnrollment->school_id_from_previous_school,
                'enrollment_status' => EnrollmentStatus::ENROLLED,
                'placement_status_ID' => $currentEnrollment->placement_status_ID,
                'promotion_status' => PromotionStatus::PENDING,
            ]);

            $enrollment->electives()->sync($electiveIds);
            $section = VacantSectionAssigner::resolve($enrollment->fresh(['gradeLevel', 'cluster', 'academicYear']) ?? $enrollment);
            if (! $section) {
                throw ValidationException::withMessages([
                    'enrollment' => 'A section could not be assigned for the next semester. Please contact the registrar.',
                ]);
            }

            $enrollment->update(['section_ID' => $section->section_ID]);
            StudentSubjectRoster::sync($enrollment->fresh() ?? $enrollment, $electiveIds);
            $currentEnrollment->update(['promotion_status' => PromotionStatus::PROMOTED]);

            return $enrollment->fresh(['academicYear', 'cluster.track', 'track', 'electives', 'gradeLevel', 'gradingSemester', 'section']) ?? $enrollment;
        });
    }

    public static function complete(Student $student): Enrollment
    {
        return DB::transaction(function () use ($student): Enrollment {
            Student::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $context = self::context($student);

            if (! $context['canComplete']) {
                throw ValidationException::withMessages([
                    'enrollment' => $context['reason'] ?: 'Senior High School completion cannot be recorded yet.',
                ]);
            }

            /** @var Enrollment $enrollment */
            $enrollment = $context['currentEnrollment'];
            $enrollment->update(['promotion_status' => PromotionStatus::COMPLETED_SENIOR_HIGH]);

            return $enrollment->fresh(['promotionStatus', 'gradeLevel', 'gradingSemester']) ?? $enrollment;
        });
    }

    private static function latestEnrollment(Student $student): ?Enrollment
    {
        return Enrollment::query()
            ->with(['academicYear', 'cluster.track', 'track', 'electives.cluster', 'gradeLevel', 'gradingSemester', 'section', 'promotionStatus', 'enrollmentStatus'])
            ->where('student_ID', $student->getKey())
            ->whereHas('gradeLevel', fn ($query) => $query->whereIn('grade_label', ['Grade 11', 'Grade 12']))
            ->orderByDesc('created_at')
            ->orderByDesc('enrollment_ID')
            ->first();
    }

    /** @return array{0: ?string, 1: ?string} */
    private static function nextStage(?string $gradeLabel, ?string $semester): array
    {
        return match ([$gradeLabel, $semester]) {
            ['Grade 11', GradingSemester::FIRST] => ['Grade 11', GradingSemester::SECOND],
            ['Grade 11', GradingSemester::SECOND] => ['Grade 12', GradingSemester::FIRST],
            ['Grade 12', GradingSemester::FIRST] => ['Grade 12', GradingSemester::SECOND],
            default => [null, null],
        };
    }

    private static function gradeLabel(Enrollment $enrollment): ?string
    {
        $gradeLevel = $enrollment->relationLoaded('gradeLevel')
            ? $enrollment->getRelation('gradeLevel')
            : $enrollment->gradeLevel()->first();

        return $gradeLevel?->grade_label;
    }

    public static function semesterLabel(?string $semester): string
    {
        return $semester === GradingSemester::FIRST ? 'First Semester' : 'Second Semester';
    }
}
