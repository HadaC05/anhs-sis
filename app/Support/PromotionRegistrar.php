<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\LearnerType;
use App\Models\PromotionStatus;
use Illuminate\Validation\ValidationException;

class PromotionRegistrar
{
    /**
     * Create the learner's pending enrollment for the next grade and school year.
     * Existing future enrollments are returned unchanged, making promotion idempotent.
     */
    public static function promote(Enrollment $enrollment): Enrollment
    {
        $enrollment->loadMissing(['academicYear', 'section.gradeLevel', 'curriculumGradeLevel']);
        $evaluation = PromotionEligibility::evaluate($enrollment);
        $enrollment->update(['promotion_status' => $evaluation['status']]);

        if ($evaluation['status'] !== PromotionStatus::ELIGIBLE) {
            throw ValidationException::withMessages([
                'promotion' => $evaluation['reason'] ?: 'This learner is not eligible for promotion.',
            ]);
        }

        $currentGradeId = (int) ($enrollment->section?->grade_ID ?: $enrollment->curriculumGradeLevel?->grade_ID);
        $nextGrade = GradeLevel::query()->where('grade_ID', '>', $currentGradeId)->orderBy('grade_ID')->first();
        if (! $nextGrade) {
            throw ValidationException::withMessages([
                'promotion' => 'Grade 12 completers cannot be promoted to another grade level.',
            ]);
        }

        $targetYear = AcademicYear::query()
            ->whereDate('start_date', '>', $enrollment->academicYear?->start_date)
            ->orderBy('start_date')
            ->first();
        if (! $targetYear) {
            throw ValidationException::withMessages([
                'promotion' => 'Configure the next school year before promoting this learner.',
            ]);
        }

        $isSeniorHigh = in_array($nextGrade->grade_label, ['Grade 11', 'Grade 12'], true);
        $sourceOffering = $enrollment->curriculumGradeLevel;
        $targetOffering = Curriculum::query()
            ->where('grade_ID', $nextGrade->grade_ID)
            ->when($isSeniorHigh, function ($query) use ($sourceOffering): void {
                if ($sourceOffering?->cluster_ID) {
                    $query->where('cluster_ID', $sourceOffering->cluster_ID);
                }
            }, fn ($query) => $query->whereNull('cluster_ID'))
            ->whereHas('gradingSemester', fn ($query) => $query->where('key', $isSeniorHigh ? GradingSemester::FIRST : GradingSemester::FULL_YEAR))
            ->orderBy('curriculum_ID')
            ->first();

        if (! $targetOffering) {
            throw ValidationException::withMessages([
                'promotion' => "No curriculum offering is configured for {$nextGrade->grade_label}.",
            ]);
        }

        $nextEnrollment = Enrollment::query()->firstOrCreate(
            ['student_ID' => $enrollment->student_ID, 'SY_ID' => $targetYear->SY_ID],
            [
                'curriculum_grade_level_ID' => $targetOffering->curriculum_ID,
                'learner_type' => $enrollment->learner_type ?: LearnerType::REGULAR,
                'enrollment_status' => EnrollmentStatus::PENDING,
                'promotion_status' => PromotionStatus::PENDING,
                'last_grade_level_completed' => $enrollment->section?->getRelation('gradeLevel')?->grade_label,
                'last_school_year_completed' => $enrollment->academicYear?->school_year,
            ],
        );

        StudentSubjectRoster::sync($nextEnrollment);

        return $nextEnrollment;
    }
}
