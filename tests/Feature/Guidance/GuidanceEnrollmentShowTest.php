<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guidance counselor can view enrollment details with addresses and guardians', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.show',
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
        'lrn' => '777777777777',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'email' => 'maria.reyes@example.com',
        'status' => 'pending',
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

    StudentGuardian::query()->create([
        'student_ID' => $student->id,
        'relationship' => 'father',
        'first_name' => 'Pedro',
        'last_name' => 'Reyes',
        'contact_no' => '+639111111111',
        'is_deceased' => true,
    ]);

    StudentGuardian::query()->create([
        'student_ID' => $student->id,
        'relationship' => 'guardian',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'contact_no' => '+639222222222',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('guidance.enrollments.show', $enrollment));

    $response->assertOk();
    $response->assertSee('Enrollment Details');
    $response->assertSee('Parents / Guardians');
    $response->assertSee("Father's Full Name");
    $response->assertSee('Given Name');
    $response->assertSee('Pedro');
    $response->assertSee('Ana');
    $response->assertSee('Doongan');
    $response->assertSee('Deceased');
    $response->assertSee('Contact No.');
    $response->assertDontSee('>Deceased</div>', false);
    $response->assertSee('Email Address');
    $response->assertSee('maria.reyes@example.com');
    $response->assertSee('min-h-[2.75rem]', false);
    $response->assertSee('bg-gray-50', false);
    $response->assertDontSee('@foreach');

    $content = $response->getContent();
    $addressesPos = strpos($content, 'data-guidance-step="2"');
    $parentsPos = strpos($content, 'data-guidance-step="3"');
    $documentsPos = strpos($content, 'data-guidance-step="4"');

    expect($addressesPos)->not->toBeFalse()
        ->and($parentsPos)->toBeGreaterThan($addressesPos)
        ->and($documentsPos)->toBeGreaterThan($parentsPos)
        ->and(substr($content, $documentsPos, 120))->toContain('Documents');
});

test('enrollment details stays on the requested tab after reload', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.tabs',
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
        'lrn' => '333333333333',
        'first_name' => 'Tab',
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
        'enrollment_status' => 'pending',
    ]);

    $documents = $this->actingAs($user)->get(route('guidance.enrollments.show', [
        'enrollment' => $enrollment,
        'step' => 'documents',
    ]));

    $documents->assertOk();
    $documents->assertSee('class="guidance-detail-step active" data-guidance-step="4"', false);
    $documents->assertDontSee('class="guidance-detail-step active" data-guidance-step="0"', false);
    $documents->assertSee('history.replaceState', false);

    $personal = $this->actingAs($user)->get(route('guidance.enrollments.show', [
        'enrollment' => $enrollment,
        'step' => 'personal',
    ]));

    $personal->assertOk();
    $personal->assertSee('class="guidance-detail-step active" data-guidance-step="1"', false);
    $personal->assertDontSee('class="guidance-detail-step active" data-guidance-step="4"', false);
});

test('guidance counselor sees a placement test recommendation for overage students only', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.placement',
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

    $overageStudent = Student::query()->create([
        'lrn' => '111111111111',
        'first_name' => 'Older',
        'last_name' => 'Learner',
        'birthdate' => '2010-01-01',
        'status' => 'pending',
    ]);

    $underageStudent = Student::query()->create([
        'lrn' => '222222222222',
        'first_name' => 'Younger',
        'last_name' => 'Learner',
        'birthdate' => '2015-07-01',
        'status' => 'pending',
    ]);

    $overageEnrollment = Enrollment::query()->create([
        'student_ID' => $overageStudent->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $underageEnrollment = Enrollment::query()->create([
        'student_ID' => $underageStudent->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.show', $overageEnrollment))
        ->assertOk()
        ->assertSee('Above expected age range')
        ->assertSee('Placement assessment recommended')
        ->assertSee('Update status')
        ->assertSee('value="recommended"', false);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.show', $underageEnrollment))
        ->assertOk()
        ->assertDontSee('Below expected age range')
        ->assertDontSee('Placement assessment recommended')
        ->assertDontSee('Placement test')
        ->assertDontSee('name="placement_status"', false);
});
