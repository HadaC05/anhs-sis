<?php

use App\Mail\StudentEnrollmentStatusMail;
use App\Mail\StudentPlacementTestMail;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\PlacementStatus;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('overage registration emails placement test overview and instructions', function () {
    Mail::fake();
    createPlacementMailAcademicYear();

    $this->post(route('register.store'), placementMailRegistrationPayload([
        'LRN' => '555555555555',
        'email' => 'overage.mail@example.com',
        'birthdate' => '2010-01-01',
    ]))->assertRedirect(route('register'));

    Mail::assertSent(StudentEnrollmentStatusMail::class);
    Mail::assertSent(StudentPlacementTestMail::class, function (StudentPlacementTestMail $mail): bool {
        $html = $mail->render();

        return $mail->hasTo('overage.mail@example.com')
            && $mail->envelope()->subject === 'Placement test recommended at Agusan National High School'
            && str_contains($html, 'Why this is needed')
            && str_contains($html, 'What you should do')
            && str_contains($html, 'Above expected age range for Grade 7')
            && str_contains($html, 'Guidance Office')
            && str_contains($html, 'Learner Reference Number')
            && str_contains($html, 'Arrive at least 15 minutes');
    });
});

test('age appropriate registration does not email a placement test notice', function () {
    Mail::fake();
    createPlacementMailAcademicYear();

    $this->post(route('register.store'), placementMailRegistrationPayload([
        'LRN' => '666666666666',
        'email' => 'age.ok.mail@example.com',
        'birthdate' => '2014-03-01',
    ]))->assertRedirect(route('register'));

    Mail::assertSent(StudentEnrollmentStatusMail::class);
    Mail::assertNotSent(StudentPlacementTestMail::class);
});

test('guidance counselor emails placement instructions when recommending a student', function () {
    Mail::fake();

    $user = createPlacementMailGuidanceUser('guidance.placement.mail');
    $enrollment = createPlacementMailEnrollment();
    $enrollment->student->update(['email' => 'recommended.mail@example.com']);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::RECOMMENDED,
        ])
        ->assertRedirect(route('guidance.enrollments.show', $enrollment));

    Mail::assertSent(StudentPlacementTestMail::class, function (StudentPlacementTestMail $mail): bool {
        return $mail->hasTo('recommended.mail@example.com');
    });
});

test('guidance counselor does not resend placement email when status is already recommended', function () {
    Mail::fake();

    $user = createPlacementMailGuidanceUser('guidance.placement.resend');
    $enrollment = createPlacementMailEnrollment(PlacementStatus::RECOMMENDED, '777777777771');
    $enrollment->student->update(['email' => 'already.recommended@example.com']);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::RECOMMENDED,
        ])
        ->assertRedirect(route('guidance.enrollments.show', $enrollment));

    Mail::assertNotSent(StudentPlacementTestMail::class);
});

test('updating placement status to passed does not send a placement test email', function () {
    Mail::fake();

    $user = createPlacementMailGuidanceUser('guidance.placement.passed.mail');
    $enrollment = createPlacementMailEnrollment(PlacementStatus::RECOMMENDED, '777777777772');
    $enrollment->student->update(['email' => 'passed.mail@example.com']);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::PASSED,
        ])
        ->assertRedirect(route('guidance.enrollments.show', $enrollment));

    Mail::assertNotSent(StudentPlacementTestMail::class);
});

test('placement test email is skipped when the student has no email address', function () {
    Mail::fake();

    $user = createPlacementMailGuidanceUser('guidance.placement.noemail');
    $enrollment = createPlacementMailEnrollment(null, '777777777773');

    expect($enrollment->student->email)->toBeNull();

    $this->actingAs($user)
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::RECOMMENDED,
        ])
        ->assertRedirect();

    Mail::assertNotSent(StudentPlacementTestMail::class);
});

function createPlacementMailAcademicYear(): AcademicYear
{
    return AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);
}

function createPlacementMailGuidanceUser(string $username): User
{
    $role = Role::query()->create(['role_name' => 'guidance counselor']);

    return User::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function placementMailRegistrationPayload(array $overrides = []): array
{
    GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    return array_merge([
        'grade_level' => '7',
        'LRN' => '123456789012',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan Elementary School',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'birthdate' => '2014-03-01',
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
    ], $overrides);
}

function createPlacementMailEnrollment(?string $status = null, string $lrn = '888888888881'): Enrollment
{
    $academicYear = AcademicYear::query()->firstOrCreate(
        ['school_year' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]
    );

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'lrn' => $lrn,
        'first_name' => 'Placement',
        'last_name' => 'Learner',
        'status' => 'pending',
    ]);

    $attributes = [
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::PENDING,
    ];

    if ($status !== null) {
        $attributes['placement_status'] = $status;
    }

    return Enrollment::query()->create($attributes)->load('student');
}
