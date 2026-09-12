<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('age alignment report defaults to above range and uses an icon action', function () {
    [$user, $academicYear, $gradeLevel] = createGuidanceAgeAlignmentFixtures();

    $overageStudent = Student::query()->create([
        'lrn' => '111111111111',
        'first_name' => 'Older',
        'last_name' => 'Learner',
        'birthdate' => '2010-01-01',
        'status' => 'pending',
    ]);

    $appropriateStudent = Student::query()->create([
        'lrn' => '333333333333',
        'first_name' => 'Ontrack',
        'last_name' => 'Learner',
        'birthdate' => '2014-03-01',
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

    Enrollment::query()->create([
        'student_ID' => $appropriateStudent->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('guidance.reports.age-for-grade'));

    $response->assertOk();
    $response->assertSee('Age Alignment Report');
    $response->assertSee('Expected age by grade');
    $response->assertSee('Grade 7');
    $response->assertSee('12–13');
    $response->assertSee('Grade 12');
    $response->assertSee('17–18');
    $response->assertDontSee('>Expected</th>', false);
    $response->assertSee('value="overage" selected', false);
    $response->assertSee('Learner, Older');
    $response->assertDontSee('Learner, Ontrack');
    $response->assertSee('title="View enrollment"', false);
    $response->assertSee(route('guidance.enrollments.show', $overageEnrollment), false);
    $response->assertDontSee('>Open</a>', false);
});

test('age alignment report can show all alignments when requested', function () {
    [$user, $academicYear, $gradeLevel] = createGuidanceAgeAlignmentFixtures();

    $appropriateStudent = Student::query()->create([
        'lrn' => '444444444444',
        'first_name' => 'Ontrack',
        'last_name' => 'Learner',
        'birthdate' => '2014-03-01',
        'status' => 'pending',
    ]);

    Enrollment::query()->create([
        'student_ID' => $appropriateStudent->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('guidance.reports.age-for-grade', ['alignment' => 'all']))
        ->assertOk()
        ->assertSee('value="all" selected', false)
        ->assertSee('Learner, Ontrack');
});

/**
 * @return array{0: User, 1: AcademicYear, 2: GradeLevel}
 */
function createGuidanceAgeAlignmentFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $role->id,
        'username' => 'guidance.age-alignment',
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

    return [$user, $academicYear, $gradeLevel];
}
