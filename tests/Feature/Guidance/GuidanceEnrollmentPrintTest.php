<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createGuidanceEnrollmentPrintFixtures(): array
{
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.print',
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

    $gradeSeven = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $gradeEleven = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 11'],
        ['category' => 'Senior High School']
    );

    return compact('user', 'academicYear', 'gradeSeven', 'gradeEleven');
}

function createGuidancePrintableEnrollment(
    AcademicYear $academicYear,
    GradeLevel $gradeLevel,
    array $studentOverrides = [],
    array $enrollmentOverrides = [],
): Enrollment {
    $student = Student::query()->create(array_merge([
        'lrn' => '123456789012',
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'suffix' => 'Jr.',
        'email' => 'maria.reyes.print@example.com',
        'contact_no' => '+639111111111',
        'sex' => 'female',
        'birthdate' => '2013-08-15',
        'birthplace' => 'Butuan City',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'status' => 'pending',
    ], $studentOverrides));

    StudentProfile::query()->create([
        'student_ID' => $student->id,
        'is_4ps' => true,
        'four_ps_household_id' => '123-456-789-012',
        'is_ip' => false,
        'has_disability' => false,
    ]);

    StudentAddress::query()->create([
        'student_ID' => $student->id,
        'address_type' => 'current',
        'house_no' => '12',
        'street_name' => 'Rizal Street',
        'barangay' => 'Doongan',
        'municipality' => 'Butuan City',
        'province' => 'Agusan del Norte',
        'country' => 'Philippines',
        'zip_code' => '8600',
    ]);

    StudentAddress::query()->create([
        'student_ID' => $student->id,
        'address_type' => 'permanent',
        'house_no' => '12',
        'street_name' => 'Rizal Street',
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
        'middle_name' => 'Cruz',
        'last_name' => 'Reyes',
        'contact_no' => '+639222222222',
        'is_deceased' => true,
    ]);

    StudentGuardian::query()->create([
        'student_ID' => $student->id,
        'relationship' => 'mother',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'contact_no' => '+639333333333',
    ]);

    return Enrollment::query()->create(array_merge([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ], $enrollmentOverrides));
}

test('guidance counselor can preview the official enrollment form layout', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeSeven' => $gradeSeven] = createGuidanceEnrollmentPrintFixtures();

    $enrollment = createGuidancePrintableEnrollment($academicYear, $gradeSeven);

    $response = $this->actingAs($user)->get(route('guidance.enrollments.print', $enrollment));

    $response->assertOk();
    $html = $response->getContent();

    $response->assertSee('Print preview of the official 2-page Enhanced Basic Education Enrollment Form (Annex 1)');
    $response->assertSee('class="sheet"', false);
    $response->assertSee('size: A4 portrait', false);
    $response->assertSee('Enhanced Basic Education Enrollment Form');
    $response->assertSee('THIS FORM IS NOT FOR SALE.');
    $response->assertSee('ANNEX 1');
    $response->assertSee('Page 1 of 2');
    $response->assertSee('Page 2 of 2');
    $response->assertSee('images/deped_logo.png', false);
    $response->assertSee('REYES');
    $response->assertSee('MARIA');
    $response->assertSee('SANTOS');
    $response->assertSee('Learner Information');
    $response->assertSee('Current Address');
    $response->assertSee('Parent\'s/Guardian\'s Information', false);
    $response->assertSee('Signature Over Printed Name of Parent/Guardian');
    $response->assertSee('Preferred Distance Learning Modality/ies');
    $response->assertSee('DOONGAN');
    $response->assertSee('PEDRO');
    $response->assertSee('Deceased');
    $response->assertSee('123-456-789-012');
    $response->assertDontSee('SEAL');
    $response->assertDontSee('@foreach');

    expect(substr_count($html, 'class="sheet"'))->toBe(2);
});

test('enrollment print preview fills grade, lrn, and school year boxes from stored data', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeSeven' => $gradeSeven] = createGuidanceEnrollmentPrintFixtures();

    $enrollment = createGuidancePrintableEnrollment($academicYear, $gradeSeven, [
        'lrn' => '987654321098',
        'email' => 'boxes.print@example.com',
    ]);

    $html = $this->actingAs($user)
        ->get(route('guidance.enrollments.print', $enrollment))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('Grade level to Enroll:')
        ->toContain('>7</span>')
        ->toContain('>9</span>')
        ->toContain('>8</span>')
        ->toContain('>2</span>')
        ->toContain('>0</span>')
        ->toContain('>6</span>');
});

test('senior high print preview shows track and strand separately', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeEleven' => $gradeEleven] = createGuidanceEnrollmentPrintFixtures();

    $cluster = Cluster::query()->create(['name' => 'Academic Track']);
    $course = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'Science, Technology, Engineering and Mathematics',
    ]);

    $enrollment = createGuidancePrintableEnrollment($academicYear, $gradeEleven, [
        'lrn' => '111122223333',
        'email' => 'shs.print@example.com',
        'first_name' => 'Liza',
        'last_name' => 'Gomez',
        'birthdate' => '2009-04-02',
    ], [
        'cluster_ID' => $cluster->cluster_ID,
        'course_ID' => $course->course_ID,
        'semester' => 'first',
    ]);

    $this->actingAs($user)
        ->get(route('guidance.enrollments.print', $enrollment))
        ->assertOk()
        ->assertSee('For Learners in Senior High School')
        ->assertSee('ACADEMIC TRACK')
        ->assertSee('SCIENCE, TECHNOLOGY, ENGINEERING AND MATHEMATICS')
        ->assertSee('>1</span>', false)
        ->assertDontSee('>SEAL<', false);
});

test('guidance counselor can preview multiple enrollment forms as separate print sheets', function () {
    ['user' => $user, 'academicYear' => $academicYear, 'gradeSeven' => $gradeSeven] = createGuidanceEnrollmentPrintFixtures();

    $first = createGuidancePrintableEnrollment($academicYear, $gradeSeven);
    $second = createGuidancePrintableEnrollment($academicYear, $gradeSeven, [
        'lrn' => '555566667777',
        'email' => 'second.print@example.com',
        'first_name' => 'Carlos',
        'last_name' => 'Diaz',
    ]);

    $html = $this->actingAs($user)
        ->post(route('guidance.enrollments.print-multiple'), [
            'enrollment_ids' => [$first->enrollment_ID, $second->enrollment_ID],
        ])
        ->assertOk()
        ->assertSee('CARLOS')
        ->assertSee('DIAZ')
        ->assertSee('MARIA')
        ->getContent();

    expect(substr_count($html, 'class="sheet"'))->toBe(4);
});

test('non guidance staff cannot print an enrollment form', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $admin = User::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.print',
        'password' => Hash::make('password'),
        'first_name' => 'Admin',
        'last_name' => 'User',
        'status' => 'active',
    ]);

    ['academicYear' => $academicYear, 'gradeSeven' => $gradeSeven] = createGuidanceEnrollmentPrintFixtures();
    $enrollment = createGuidancePrintableEnrollment($academicYear, $gradeSeven, [
        'lrn' => '000011112222',
        'email' => 'forbidden.print@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('guidance.enrollments.print', $enrollment))
        ->assertForbidden();
});
