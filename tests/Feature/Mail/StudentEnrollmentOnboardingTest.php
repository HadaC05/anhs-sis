<?php

use App\Mail\StudentEnrollmentStatusMail;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Support\StudentCredentials;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function createOnboardingRegistrationFixtures(): void
{
    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );
}

test('registration temporarily enrolls the student, creates login credentials, and emails login instructions', function () {
    Mail::fake();
    createOnboardingRegistrationFixtures();

    $payload = [
        'grade_level' => '7',
        'LRN' => '123456789012',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan Elementary School',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'birthdate' => '2012-05-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Male',
        'contact_no' => '+639123456789',
        'email' => 'juan.cruz@example.com',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'ip_community' => 'No',
        'four_ps_beneficiary' => 'No',
        'pwd' => 'No',
        'curr_barangay' => 'Doongan',
        'curr_municipality_city' => 'Butuan City',
        'curr_province' => 'Agusan del Norte',
        'curr_country' => 'Philippines',
        'curr_zip_code' => '8600',
        'perm_barangay' => 'Doongan',
        'perm_municipality_city' => 'Butuan City',
        'perm_province' => 'Agusan del Norte',
        'perm_country' => 'Philippines',
        'perm_zip_code' => '8600',
        'same_address' => '1',
        'father_lname' => 'Cruz',
        'father_fname' => 'Pedro',
        'mother_lname' => 'Santos',
        'mother_fname' => 'Maria',
    ];

    $this->post(route('register.store'), $payload)
        ->assertRedirect(route('register'))
        ->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '123456789012')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student?->id)->first();

    expect($student)->not->toBeNull()
        ->and($student->status)->toBe('approved')
        ->and($student->username)->toBe('123456789012')
        ->and($enrollment?->enrollment_status)->toBe(EnrollmentStatus::TEMPORARILY_ENROLLED);

    Mail::assertSent(StudentEnrollmentStatusMail::class, function (StudentEnrollmentStatusMail $mail) use ($student): bool {
        return $mail->hasTo('juan.cruz@example.com')
            && $mail->status === EnrollmentStatus::TEMPORARILY_ENROLLED
            && $mail->student->is($student);
    });
});

test('verifying required documents enrolls the student and emails enrolled login instructions', function () {
    Mail::fake();

    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.onboarding.docs',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'username' => '123456789013',
        'password' => Hash::make(StudentCredentials::defaultPassword('Juan', 'Cruz', 2026)),
        'change_password' => true,
        'lrn' => '123456789013',
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'email' => 'juan.enrolled@example.com',
        'status' => 'approved',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::TEMPORARILY_ENROLLED,
    ]);

    $birthCertificate = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'birth_certificate',
        'file_path' => 'student_documents/birth.pdf',
        'status' => 'verified',
        'date_uploaded' => now(),
        'date_verified' => now(),
        'verified_by' => $user->staff_id,
    ]);

    $form137 = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'form_137',
        'file_path' => 'student_documents/form-137.pdf',
        'status' => 'pending',
        'date_uploaded' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('guidance.documents.verify', $form137))
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'documents',
        ]));

    expect($form137->fresh()->status)->toBe('verified')
        ->and($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::ENROLLED)
        ->and($birthCertificate->fresh()->status)->toBe('verified');

    Mail::assertSent(StudentEnrollmentStatusMail::class, function (StudentEnrollmentStatusMail $mail): bool {
        return $mail->hasTo('juan.enrolled@example.com')
            && $mail->status === EnrollmentStatus::ENROLLED;
    });
});
