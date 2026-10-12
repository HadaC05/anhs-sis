<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\PromotionStatus;
use Illuminate\Support\Facades\DB;

class PromotedEnrollmentActivator
{
    /**
     * Enroll pending learners whose prior enrollment was promoted into this year.
     * Ordinary pending applications are intentionally excluded.
     */
    public static function activateFor(AcademicYear $academicYear): int
    {
        $pendingId = EnrollmentStatus::idFor(EnrollmentStatus::PENDING);
        $enrolledId = EnrollmentStatus::idFor(EnrollmentStatus::ENROLLED);
        $promotedId = PromotionStatus::idFor(PromotionStatus::PROMOTED);

        if (! $pendingId || ! $enrolledId || ! $promotedId) {
            return 0;
        }

        $enrollments = Enrollment::query()
            ->where('SY_ID', $academicYear->SY_ID)
            ->where('enrollment_status_ID', $pendingId)
            ->whereExists(function ($query) use ($academicYear, $promotedId): void {
                $query->selectRaw('1')
                    ->from('enrollments as promoted_source')
                    ->join('academic_years as promoted_source_year', 'promoted_source_year.SY_ID', '=', 'promoted_source.SY_ID')
                    ->whereColumn('promoted_source.student_ID', 'enrollments.student_ID')
                    ->where('promoted_source.promotion_status_ID', $promotedId)
                    ->whereDate('promoted_source_year.start_date', '<', $academicYear->start_date);
            })
            ->lockForUpdate()
            ->get();

        $activated = 0;

        foreach ($enrollments as $enrollment) {
            $section = $enrollment->section
                ?: VacantSectionAssigner::resolve(
                    $enrollment->fresh(['gradeLevel', 'cluster', 'academicYear']) ?? $enrollment,
                );

            if (! $section) {
                continue;
            }

            $enrollment->update([
                'section_ID' => $section->section_ID,
                'enrollment_status_ID' => $enrolledId,
            ]);
            StudentSubjectRoster::sync($enrollment->fresh() ?? $enrollment);
            $activated++;
        }

        return $activated;
    }

    public static function activateForActiveYear(): int
    {
        $academicYear = AcademicYear::query()->where('status', true)->first();

        if (! $academicYear) {
            return 0;
        }

        return DB::transaction(fn (): int => self::activateFor($academicYear));
    }
}
