<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createGuidanceEnrollmentRegistrationFixtures(): array
{
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.register',
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

    GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 8'],
        ['category' => 'Junior High School']
    );

    return compact('user', 'academicYear', 'gradeLevel');
}

function guidanceEnrollmentPayload(array $overrides = []): array
{
    return array_merge([
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
    ], $overrides);
}

test('guidance counselor can open the student registration form', function () {
    ['user' => $user] = createGuidanceEnrollmentRegistrationFixtures();

    $this->actingAs($user)
        ->get(route('guidance.enrollments.create'))
        ->assertOk()
        ->assertSee('Register Student')
        ->assertSee('name="LRN"', false)
        ->assertSee('name="last_school_attended"', false);
});

test('guidance counselor can register a student', function () {
    ['user' => $user, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    $response = $this->actingAs($user)->post(route('guidance.enrollments.store'), guidanceEnrollmentPayload([
        'LRN' => '321321321321',
        'email' => 'guidance.registered@example.com',
    ]));

    $student = Student::query()->where('lrn', '321321321321')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student?->id)->first();

    expect($student)->not->toBeNull()
        ->and($student->first_name)->toBe('Juan')
        ->and($student->email)->toBe('guidance.registered@example.com')
        ->and($enrollment)->not->toBeNull()
        ->and($enrollment->enrollment_status)->toBe(EnrollmentStatus::TEMPORARILY_ENROLLED)
        ->and($enrollment->grade_ID)->toBe($gradeLevel->grade_ID);

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHas('status');
});

test('guests cannot open the guidance registration form', function () {
    $this->get(route('guidance.enrollments.create'))
        ->assertRedirect();
});

test('non-guidance staff cannot register students', function () {
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = User::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.blocked',
        'password' => Hash::make('password'),
        'first_name' => 'Teacher',
        'last_name' => 'User',
        'status' => 'active',
    ]);

    $this->actingAs($teacher)
        ->get(route('guidance.enrollments.create'))
        ->assertForbidden();
});

test('guidance counselor can open the enrollment edit form with existing details', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    $student = Student::query()->create([
        'lrn' => '888888888888',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'email' => 'maria.reyes@example.com',
        'contact_no' => '+639111111111',
        'sex' => 'female',
        'birthdate' => '2013-02-02',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'last_school_attended' => 'Doongan Elementary School',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.edit', $enrollment))
        ->assertOk()
        ->assertSee('Edit Enrollment Details')
        ->assertSee('value="Maria"', false)
        ->assertSee('value="888888888888"', false)
        ->assertSee('value="Doongan Elementary School"', false);
});

test('guidance counselor can update enrollment details', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    $gradeEight = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 8'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'lrn' => '777777777701',
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old.name@example.com',
        'contact_no' => '+639111111111',
        'sex' => 'male',
        'birthdate' => '2012-05-01',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'birthplace' => 'Butuan City',
        'status' => 'approved',
        'username' => '777777777701',
    ]);

    StudentProfile::query()->create([
        'student_ID' => $student->id,
        'is_4ps' => false,
        'is_ip' => false,
        'has_disability' => false,
    ]);

    StudentGuardian::query()->create([
        'student_ID' => $student->id,
        'relationship' => 'father',
        'first_name' => 'Pedro',
        'last_name' => 'Name',
    ]);

    StudentAddress::query()->create([
        'student_ID' => $student->id,
        'address_type' => 'current',
        'barangay' => 'Doongan',
        'municipality' => 'Butuan City',
        'province' => 'Agusan del Norte',
        'country' => 'Philippines',
        'zip_code' => '8600',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'last_school_attended' => 'Old School',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $response = $this->actingAs($user)->put(route('guidance.enrollments.update', $enrollment), guidanceEnrollmentPayload([
        'grade_level' => '8',
        'LRN' => '777777777701',
        'first_name' => 'Updated',
        'last_name' => 'Learner',
        'email' => 'updated.learner@example.com',
        'last_school_attended' => 'New Elementary School',
        'contact_no' => '+639222222222',
    ]));

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHas('status');

    $student->refresh();
    $enrollment->refresh();

    expect($student->first_name)->toBe('Updated')
        ->and($student->last_name)->toBe('Learner')
        ->and($student->email)->toBe('updated.learner@example.com')
        ->and($enrollment->last_school_attended)->toBe('New Elementary School')
        ->and($enrollment->grade_ID)->toBe($gradeEight->grade_ID);
});

test('guidance counselor can keep the same lrn and email when editing', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    $student = Student::query()->create([
        'lrn' => '666666666666',
        'first_name' => 'Keep',
        'last_name' => 'Same',
        'email' => 'keep.same@example.com',
        'contact_no' => '+639111111111',
        'sex' => 'female',
        'birthdate' => '2012-05-01',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'birthplace' => 'Butuan City',
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
        'last_school_attended' => 'Agusan Elementary School',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.edit', $enrollment))
        ->put(route('guidance.enrollments.update', $enrollment), guidanceEnrollmentPayload([
            'LRN' => '666666666666',
            'email' => 'keep.same@example.com',
            'first_name' => 'Kept',
            'gender' => 'Female',
        ]))
        ->assertRedirect(route('guidance.enrollments.show', $enrollment))
        ->assertSessionHasNoErrors();

    expect($student->fresh()->first_name)->toBe('Kept');
});

test('guidance counselor cannot change an lrn to one already registered', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    Student::query()->create([
        'lrn' => '111111111111',
        'first_name' => 'Taken',
        'last_name' => 'Lrn',
        'email' => 'taken.lrn@example.com',
        'status' => 'pending',
    ]);

    $student = Student::query()->create([
        'lrn' => '222222222222',
        'first_name' => 'Editable',
        'last_name' => 'Student',
        'email' => 'editable@example.com',
        'contact_no' => '+639111111111',
        'sex' => 'male',
        'birthdate' => '2012-05-01',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'birthplace' => 'Butuan City',
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
        'last_school_attended' => 'Agusan Elementary School',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.edit', $enrollment))
        ->put(route('guidance.enrollments.update', $enrollment), guidanceEnrollmentPayload([
            'LRN' => '111111111111',
            'email' => 'editable@example.com',
        ]))
        ->assertRedirect(route('guidance.enrollments.edit', $enrollment))
        ->assertSessionHasErrors(['LRN']);
});

test('guidance enrollment list and details include register and edit actions', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createGuidanceEnrollmentRegistrationFixtures();

    $student = Student::query()->create([
        'lrn' => '555555555555',
        'first_name' => 'Visible',
        'last_name' => 'Student',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.index'))
        ->assertOk()
        ->assertSee('Register Student')
        ->assertSee(route('guidance.enrollments.create'), false)
        ->assertSee(route('guidance.enrollments.edit', $enrollment), false);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.show', $enrollment))
        ->assertOk()
        ->assertSee('Edit details')
        ->assertSee(route('guidance.enrollments.edit', $enrollment), false);
});
