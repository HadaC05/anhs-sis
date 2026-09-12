<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

test('registrar can view the student masterlist', function () {
    $role = Role::query()->create(['role_name' => 'registrar']);
    $registrar = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'registrar.students',
        'password' => Hash::make('password'),
        'first_name' => 'Reg',
        'last_name' => 'istrar',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'DepEd SHS - GAS',
        'description' => 'General Academic Strand curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 301',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '777777777777',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'birthdate' => '2010-01-15',
        'sex' => 'female',
        'status' => 'active',
    ]);

    Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $response = $this->actingAs($registrar)->get(route('registrar.students'));

    $response->assertOk();
    $response->assertSee('Student Masterlist');
    $response->assertSee('Santos, Ana');
    $response->assertSee('777777777777');
    $response->assertSee('Rizal');
});
