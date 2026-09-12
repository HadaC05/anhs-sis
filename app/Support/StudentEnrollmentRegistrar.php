<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\PlacementStatus;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentApplication;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentRegistrar
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function register(array $validated, ?AcademicYear $academicYear = null): Enrollment
    {
        $validated = StudentEnrollmentForm::normalized($validated);
        $academicYear ??= AcademicYear::query()->where('status', true)->first();

        if (! $academicYear) {
            throw ValidationException::withMessages([
                'enrollment' => 'No active school year is configured.',
            ]);
        }

        $gradeId = GradeLevel::idForValue('grade_'.$validated['grade_level']);
        $isSeniorHigh = in_array($validated['grade_level'], ['11', '12'], true);

        if (! $gradeId) {
            throw ValidationException::withMessages([
                'grade_level' => 'Selected grade level is not configured.',
            ]);
        }

        $createdEnrollmentId = null;

        DB::transaction(function () use ($validated, $academicYear, $gradeId, $isSeniorHigh, &$createdEnrollmentId): void {
            $student = StudentApplication::query()->create([
                'lrn' => $validated['LRN'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'email' => $validated['email'] ?? null,
                'contact_no' => $validated['contact_no'] ?? null,
                'sex' => isset($validated['gender']) ? strtolower($validated['gender']) : null,
                'birthdate' => $validated['birthdate'],
                'birthplace' => $validated['birthplace'] ?? null,
                'mother_tongue' => $validated['mother_tongue'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            $enrollment = Enrollment::query()->create([
                'student_ID' => $student->id,
                'section_ID' => null,
                'SY_ID' => $academicYear->SY_ID,
                'cluster_ID' => $isSeniorHigh ? $validated['cluster_ID'] : null,
                'course_ID' => $isSeniorHigh ? $validated['course_ID'] : null,
                'grade_ID' => $gradeId,
                'semester' => $isSeniorHigh ? $validated['semester'] : null,
                'learner_type' => $validated['learner_type'],
                'last_grade_level_completed' => $validated['last_grade_level_completed'] ?? null,
                'last_school_year_completed' => $validated['last_school_year_completed'] ?? null,
                'last_school_attended' => $validated['last_school_attended'] ?? null,
                'school_id_from_previous_school' => $validated['school_id_from_previous_school'] ?? null,
                'enrollment_status' => EnrollmentStatus::TEMPORARILY_ENROLLED,
            ]);

            $section = VacantSectionAssigner::resolve($enrollment);
            if ($section) {
                $enrollment->update(['section_ID' => $section->section_ID]);
            }

            $studentModel = Student::query()->findOrFail($student->id);
            $enrollment->setRelation('student', $studentModel);
            $enrollment->setRelation('academicYear', $academicYear);
            $enrollment->markRecommendedIfOverage();
            StudentAccountProvisioner::ensure($enrollment);

            self::syncRelatedRecords($student->id, $validated);

            $createdEnrollmentId = $enrollment->enrollment_ID;
        });

        $enrollment = Enrollment::query()
            ->with(['academicYear', 'student', 'gradeLevel', 'section'])
            ->findOrFail($createdEnrollmentId);

        StudentEnrollmentNotifier::send($enrollment->student, $enrollment, EnrollmentStatus::TEMPORARILY_ENROLLED);
        StudentPlacementTestNotifier::sendIfNewlyRecommended($enrollment);

        return $enrollment;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function update(Enrollment $enrollment, array $validated): Enrollment
    {
        $validated = StudentEnrollmentForm::normalized($validated);
        $enrollment->loadMissing(['student', 'academicYear', 'section', 'placementStatus']);

        $student = $enrollment->student;

        if (! $student) {
            throw ValidationException::withMessages([
                'enrollment' => 'This enrollment is missing a student record.',
            ]);
        }

        $gradeId = GradeLevel::idForValue('grade_'.$validated['grade_level']);
        $isSeniorHigh = in_array($validated['grade_level'], ['11', '12'], true);

        if (! $gradeId) {
            throw ValidationException::withMessages([
                'grade_level' => 'Selected grade level is not configured.',
            ]);
        }

        $previousPlacementStatus = $enrollment->placement_status;

        DB::transaction(function () use ($enrollment, $student, $validated, $gradeId, $isSeniorHigh): void {
            $oldLrn = (string) $student->lrn;
            $newLrn = (string) $validated['LRN'];
            $username = $student->username;

            if ($oldLrn !== $newLrn && ($username === null || $username === '' || $username === $oldLrn)) {
                $username = StudentCredentials::usernameFromLrn($newLrn);

                if (Student::query()->where('username', $username)->whereKeyNot($student->id)->exists()) {
                    throw ValidationException::withMessages([
                        'LRN' => "Cannot update the LRN because the username {$username} is already assigned to another student.",
                    ]);
                }
            }

            $student->update([
                'lrn' => $newLrn,
                'username' => $username,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'email' => $validated['email'] ?? null,
                'contact_no' => $validated['contact_no'] ?? null,
                'sex' => strtolower($validated['gender']),
                'birthdate' => $validated['birthdate'],
                'birthplace' => $validated['birthplace'] ?? null,
                'mother_tongue' => $validated['mother_tongue'] ?? null,
                'religion' => $validated['religion'] ?? null,
            ]);

            $previousGradeId = (int) $enrollment->grade_ID;
            $previousClusterId = $enrollment->cluster_ID ? (int) $enrollment->cluster_ID : null;
            $newClusterId = $isSeniorHigh ? ($validated['cluster_ID'] ? (int) $validated['cluster_ID'] : null) : null;

            $enrollment->update([
                'cluster_ID' => $newClusterId,
                'course_ID' => $isSeniorHigh ? ($validated['course_ID'] ?? null) : null,
                'grade_ID' => $gradeId,
                'semester' => $isSeniorHigh ? ($validated['semester'] ?? null) : null,
                'learner_type' => $validated['learner_type'],
                'last_grade_level_completed' => $validated['last_grade_level_completed'] ?? null,
                'last_school_year_completed' => $validated['last_school_year_completed'] ?? null,
                'last_school_attended' => $validated['last_school_attended'] ?? null,
                'school_id_from_previous_school' => $validated['school_id_from_previous_school'] ?? null,
            ]);

            $gradeOrClusterChanged = $previousGradeId !== (int) $gradeId || $previousClusterId !== $newClusterId;
            $section = $enrollment->section;
            $sectionMatches = $section
                && (int) $section->grade_ID === (int) $gradeId
                && ($section->cluster_ID ? (int) $section->cluster_ID : null) === $newClusterId
                && (int) $section->SY_ID === (int) $enrollment->SY_ID;

            if ($gradeOrClusterChanged || ! $sectionMatches) {
                $enrollment->unsetRelation('section');
                $assigned = VacantSectionAssigner::resolve($enrollment->fresh(['gradeLevel', 'cluster', 'academicYear']) ?? $enrollment);
                $enrollment->update(['section_ID' => $assigned?->section_ID]);
            }

            self::syncRelatedRecords($student->id, $validated);

            $enrollment->refresh();
            $enrollment->setRelation('student', $student->fresh());
            $enrollment->loadMissing(['academicYear', 'gradeLevel']);

            if (in_array($enrollment->placement_status, [PlacementStatus::PENDING, PlacementStatus::AGE_APPROPRIATE, PlacementStatus::RECOMMENDED, ''], true)) {
                $placementStatus = $enrollment->requiresPlacementAssessment()
                    ? PlacementStatus::RECOMMENDED
                    : PlacementStatus::AGE_APPROPRIATE;

                if ($enrollment->placement_status !== $placementStatus) {
                    $enrollment->update(['placement_status' => $placementStatus]);
                }
            }

            StudentAccountProvisioner::ensure($enrollment);
        });

        $enrollment = $enrollment->fresh([
            'student.profile',
            'student.guardians',
            'student.addresses',
            'academicYear',
            'gradeLevel',
            'section',
            'placementStatus',
        ]) ?? $enrollment;

        StudentPlacementTestNotifier::sendIfNewlyRecommended($enrollment, $previousPlacementStatus);

        return $enrollment;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private static function syncRelatedRecords(int $studentId, array $validated): void
    {
        StudentProfile::query()->updateOrCreate(
            ['student_ID' => $studentId],
            [
                'is_4ps' => $validated['four_ps_beneficiary'] === 'Yes',
                'four_ps_household_id' => $validated['four_ps_beneficiary'] === 'Yes' ? ($validated['four_ps_details'] ?? null) : null,
                'is_ip' => $validated['ip_community'] === 'Yes',
                'ip_community' => $validated['ip_community'] === 'Yes' ? ($validated['ip_details'] ?? null) : null,
                'has_disability' => $validated['pwd'] === 'Yes',
                'disability_name' => $validated['pwd'] === 'Yes' ? ($validated['pwd_details'] ?? null) : null,
            ]
        );

        foreach (['father', 'mother', 'guardian'] as $relationship) {
            StudentGuardian::query()->updateOrCreate(
                [
                    'student_ID' => $studentId,
                    'relationship' => $relationship,
                ],
                StudentGuardian::attributesFromForm($validated, $relationship)
            );
        }

        foreach ([
            'current' => ['curr_house_no', 'curr_street_name', 'curr_barangay', 'curr_municipality_city', 'curr_province', 'curr_country', 'curr_zip_code'],
            'permanent' => ['perm_house_no', 'perm_street_name', 'perm_barangay', 'perm_municipality_city', 'perm_province', 'perm_country', 'perm_zip_code'],
        ] as $type => [$house, $street, $barangay, $municipality, $province, $country, $zip]) {
            StudentAddress::query()->updateOrCreate(
                [
                    'student_ID' => $studentId,
                    'address_type' => $type,
                ],
                [
                    'house_no' => $validated[$house] ?? null,
                    'street_name' => $validated[$street] ?? null,
                    'barangay' => $validated[$barangay] ?? null,
                    'municipality' => $validated[$municipality] ?? null,
                    'province' => $validated[$province] ?? null,
                    'country' => $validated[$country] ?? null,
                    'zip_code' => $validated[$zip] ?? null,
                ]
            );
        }
    }
}
