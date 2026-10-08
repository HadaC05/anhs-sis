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

class SecondSemesterEnrollment
{
    /**
     * @return array<string, mixed>
     */
    public static function context(Student $student): array
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $firstSemesterEnrollment = null;
        $secondSemesterEnrollment = null;
        $targetCurriculum = null;
        $electives = collect();
        $eligible = false;
        $reason = null;

        if (! $activeYear) {
            $reason = 'No active school year is configured yet.';
        } else {
            $firstSemesterEnrollment = self::gradeElevenEnrollmentQuery($student, $activeYear->SY_ID, GradingSemester::FIRST)
                ->with(['academicYear', 'cluster.track', 'track', 'electives.cluster', 'gradeLevel', 'gradingSemester', 'section', 'promotionStatus', 'enrollmentStatus'])
                ->latest('enrollment_ID')
                ->first();

            $secondSemesterEnrollment = self::gradeElevenEnrollmentQuery($student, $activeYear->SY_ID, GradingSemester::SECOND)
                ->with(['academicYear', 'cluster.track', 'track', 'electives.cluster', 'gradeLevel', 'gradingSemester', 'section', 'enrollmentStatus'])
                ->latest('enrollment_ID')
                ->first();

            if ($secondSemesterEnrollment) {
                $reason = 'Your Grade 11 second-semester enrollment has already been processed.';
            } elseif (! $firstSemesterEnrollment) {
                $reason = 'This continuation is only available to Grade 11 learners enrolled in the first semester of the active school year.';
            } elseif ($firstSemesterEnrollment->enrollment_status !== EnrollmentStatus::ENROLLED) {
                $reason = 'Your first-semester enrollment must be fully enrolled before you can continue to the second semester.';
            } elseif ($firstSemesterEnrollment->promotion_status !== PromotionStatus::ELIGIBLE) {
                $reason = 'Your first-semester eligibility has not yet been confirmed by the school.';
            } else {
                $targetCurriculum = Curriculum::query()
                    ->where('grade_ID', $firstSemesterEnrollment->gradeId)
                    ->where('cluster_ID', $firstSemesterEnrollment->clusterId)
                    ->whereHas('gradingSemester', fn ($query) => $query->where('key', GradingSemester::SECOND))
                    ->whereHas('dataStatus', fn ($query) => $query->where('key', 'active'))
                    ->first();

                if (! $targetCurriculum) {
                    $reason = 'The matching Grade 11 second-semester curriculum is not available yet.';
                } else {
                    $trackId = (int) ($firstSemesterEnrollment->track_ID ?: $firstSemesterEnrollment->cluster?->track_ID);
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

        $track = $firstSemesterEnrollment?->track ?: $firstSemesterEnrollment?->cluster?->track;
        $requiredElectiveCount = $track?->isAcademic() ? 2 : 1;

        return compact(
            'activeYear',
            'firstSemesterEnrollment',
            'secondSemesterEnrollment',
            'targetCurriculum',
            'electives',
            'eligible',
            'reason',
            'track',
            'requiredElectiveCount',
        );
    }

    /**
     * @param  list<int>  $electiveIds
     */
    public static function enroll(Student $student, array $electiveIds): Enrollment
    {
        return DB::transaction(function () use ($student, $electiveIds): Enrollment {
            Student::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $context = self::context($student);

            if (! $context['eligible']) {
                throw ValidationException::withMessages([
                    'enrollment' => $context['reason'] ?: 'You are not eligible for second-semester enrollment.',
                ]);
            }

            $electiveIds = collect($electiveIds)->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $requiredCount = (int) $context['requiredElectiveCount'];

            if (count($electiveIds) !== $requiredCount) {
                $label = $requiredCount === 2 ? 'two different electives' : 'one elective';
                throw ValidationException::withMessages([
                    'elective_ids' => "Please choose {$label} for your track.",
                ]);
            }

            $validElectiveIds = $context['electives']->pluck('subject_ID')->map(fn ($id): int => (int) $id);
            if (collect($electiveIds)->diff($validElectiveIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'elective_ids' => 'Every selection must be an active elective offered for your track.',
                ]);
            }

            /** @var Enrollment $firstSemesterEnrollment */
            $firstSemesterEnrollment = $context['firstSemesterEnrollment'];
            /** @var Curriculum $targetCurriculum */
            $targetCurriculum = $context['targetCurriculum'];

            $enrollment = Enrollment::query()->create([
                'student_ID' => $student->getKey(),
                'section_ID' => null,
                'SY_ID' => $firstSemesterEnrollment->SY_ID,
                'curriculum_grade_level_ID' => $targetCurriculum->curriculum_ID,
                'track_ID' => $firstSemesterEnrollment->track_ID ?: $firstSemesterEnrollment->cluster?->track_ID,
                'learner_type_ID' => $firstSemesterEnrollment->learner_type_ID,
                'last_grade_level_completed' => $firstSemesterEnrollment->last_grade_level_completed,
                'last_school_year_completed' => $firstSemesterEnrollment->last_school_year_completed,
                'last_school_attended' => $firstSemesterEnrollment->last_school_attended,
                'school_id_from_previous_school' => $firstSemesterEnrollment->school_id_from_previous_school,
                'enrollment_status' => EnrollmentStatus::ENROLLED,
                'placement_status_ID' => $firstSemesterEnrollment->placement_status_ID,
                'promotion_status' => PromotionStatus::PENDING,
            ]);

            $enrollment->electives()->sync($electiveIds);
            $section = VacantSectionAssigner::resolve($enrollment->fresh(['gradeLevel', 'cluster', 'academicYear']) ?? $enrollment);

            if (! $section) {
                throw ValidationException::withMessages([
                    'enrollment' => 'A second-semester section could not be assigned. Please contact the registrar.',
                ]);
            }

            $enrollment->update(['section_ID' => $section->section_ID]);
            StudentSubjectRoster::sync($enrollment->fresh() ?? $enrollment, $electiveIds);

            return $enrollment->fresh(['academicYear', 'cluster.track', 'track', 'electives', 'gradeLevel', 'gradingSemester', 'section']) ?? $enrollment;
        });
    }

    private static function gradeElevenEnrollmentQuery(Student $student, int $academicYearId, string $semester)
    {
        return Enrollment::query()
            ->where('student_ID', $student->getKey())
            ->where('SY_ID', $academicYearId)
            ->whereHas('gradeLevel', fn ($query) => $query->where('grade_label', 'Grade 11'))
            ->whereHas('gradingSemester', fn ($query) => $query->where('key', $semester));
    }
}
