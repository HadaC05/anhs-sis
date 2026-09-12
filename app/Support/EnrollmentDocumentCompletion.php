<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\DocumentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Contracts\Auth\Authenticatable;

class EnrollmentDocumentCompletion
{
    /**
     * @return list<string>
     */
    public static function requiredTypes(): array
    {
        return StudentDocument::requiredTypes();
    }

    public static function hasRequiredDocumentsVerified(Student $student): bool
    {
        $verifiedTypes = StudentDocument::query()
            ->where('student_ID', $student->id)
            ->where('status', DocumentStatus::VERIFIED)
            ->whereIn('doc_type', self::requiredTypes())
            ->get()
            ->pluck('doc_type')
            ->unique();

        return collect(self::requiredTypes())->every(
            fn (string $type): bool => $verifiedTypes->contains($type),
        );
    }

    public static function promoteWhenReady(Student $student, ?Authenticatable $actor = null): ?string
    {
        if (! self::hasRequiredDocumentsVerified($student)) {
            return null;
        }

        $enrollment = self::currentEnrollment($student);

        if (! $enrollment) {
            return null;
        }

        if ($enrollment->enrollment_status === EnrollmentStatus::ENROLLED) {
            return null;
        }

        if (! in_array($enrollment->enrollment_status, [
            EnrollmentStatus::PENDING,
            EnrollmentStatus::TEMPORARILY_ENROLLED,
        ], true)) {
            return null;
        }

        if (! $enrollment->section_ID) {
            $section = VacantSectionAssigner::resolve($enrollment);

            if ($section instanceof Section) {
                $enrollment->section_ID = $section->section_ID;
            }
        }

        $enrollment->enrollment_status = EnrollmentStatus::ENROLLED;
        $enrollment->save();

        StudentAccountProvisioner::ensure($enrollment, $actor);
        $enrollment->load(['academicYear', 'student']);
        StudentEnrollmentNotifier::send($student->fresh() ?? $student, $enrollment, EnrollmentStatus::ENROLLED);

        return EnrollmentStatus::ENROLLED;
    }

    public static function currentEnrollment(Student $student): ?Enrollment
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();

        return $student->enrollments()
            ->with(['academicYear', 'student', 'section'])
            ->when($activeYear, fn ($query) => $query->where('SY_ID', $activeYear->SY_ID))
            ->latest('created_at')
            ->first();
    }
}
