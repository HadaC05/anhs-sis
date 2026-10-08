<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\PromotionStatus;
use Illuminate\Validation\ValidationException;

class RetainedEnrollmentRegistrar
{
    /** Create a pending same-grade enrollment in the later active school year. */
    public static function enroll(Enrollment $enrollment): Enrollment
    {
        $enrollment->loadMissing(['academicYear', 'curriculumGradeLevel', 'electives']);
        $evaluation = PromotionEligibility::evaluate($enrollment);

        if ($enrollment->promotion_status !== $evaluation['status']) {
            $enrollment->update(['promotion_status' => $evaluation['status']]);
        }

        if ($evaluation['status'] !== PromotionStatus::RETAINED) {
            throw ValidationException::withMessages([
                'retention' => 'Only learners with a Retained promotion status can repeat the same grade level.',
            ]);
        }

        $targetYear = AcademicYear::query()
            ->where('status', true)
            ->whereDate('start_date', '>', $enrollment->academicYear?->start_date)
            ->orderBy('start_date')
            ->first();

        if (! $targetYear) {
            throw ValidationException::withMessages([
                'retention' => "Activate a school year after the learner's retained school year before enrolling them to repeat the grade.",
            ]);
        }

        $existing = Enrollment::query()
            ->where('student_ID', $enrollment->student_ID)
            ->where('SY_ID', $targetYear->SY_ID)
            ->get();
        $sameOffering = $existing->firstWhere('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID);

        if ($sameOffering) {
            return $sameOffering;
        }

        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'retention' => "This learner already has a different enrollment for {$targetYear->school_year}. Review that enrollment before creating a retained-grade record.",
            ]);
        }

        $repeatEnrollment = Enrollment::query()->create([
            'student_ID' => $enrollment->student_ID,
            'section_ID' => null,
            'SY_ID' => $targetYear->SY_ID,
            'curriculum_grade_level_ID' => $enrollment->curriculum_grade_level_ID,
            'track_ID' => $enrollment->track_ID,
            'course_ID' => $enrollment->course_ID,
            'learner_type' => $enrollment->learner_type,
            'last_grade_level_completed' => $enrollment->last_grade_level_completed,
            'last_school_year_completed' => $enrollment->last_school_year_completed,
            'last_school_attended' => $enrollment->last_school_attended,
            'school_id_from_previous_school' => $enrollment->school_id_from_previous_school,
            'enrollment_status' => EnrollmentStatus::PENDING,
            'promotion_status' => PromotionStatus::PENDING,
        ]);

        $electiveIds = $enrollment->electives->pluck('subject_ID')->all();
        $repeatEnrollment->electives()->sync($electiveIds);
        StudentSubjectRoster::sync($repeatEnrollment, $electiveIds);

        return $repeatEnrollment->loadMissing(['academicYear', 'gradeLevel']);
    }
}
