<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;

function academicPortalStaff(string $role): Staff
{
    return Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'academic.'.$role, 'password' => bcrypt('password'),
        'first_name' => 'Academic', 'last_name' => 'Staff', 'status' => 'active', 'change_password' => false,
    ]);
}

test('academic pages and report navigation stay in the selected portal', function (string $portal) {
    $this->actingAs(academicPortalStaff($portal));
    foreach (['enrollments.index', 'enrollments.create', 'students', 'reports.enrollment', 'reports.promotion', 'reports.age-for-grade', 'proficiency-levels'] as $page) {
        $response = $this->get(route($portal.'.'.$page))->assertOk();
        $response->assertSee('id="'.$portal.'-sidebar"', false)
            ->assertSee(route($portal.'.enrollments.index'), false)
            ->assertSee(route($portal.'.students'), false)
            ->assertSeeInOrder(['Academic Records', 'Enrollment Management', 'Student Masterlist', 'Reports', 'Proficiency Levels', 'Enrollment Reports', 'Promotion Reports']);
        $response->assertDontSee('href="'.url('/guidance').'/', false)
            ->assertDontSee('action="'.url('/guidance').'/', false)
            ->assertDontSee('href="'.url('/registrar').'/', false);
    }
})->with(['principal', 'admin']);

test('record details and enrollment actions use the selected portal', function (string $portal) {
    $this->actingAs(academicPortalStaff($portal));
    $year = AcademicYear::query()->create(['school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true]);
    $curriculum = \App\Models\Curriculum::query()->create(['name' => 'Academic test', 'status' => true]);
    $grade = GradeLevel::query()->firstOrCreate(['grade_label' => 'Grade 7'], ['category' => 'Junior High School']);
    $section = \App\Models\Section::query()->create(['name' => 'Test', 'grade_ID' => $grade->grade_ID, 'SY_ID' => $year->SY_ID, 'curriculum_ID' => $curriculum->curriculum_ID, 'capacity' => 40]);
    $student = Student::query()->create(['lrn' => '123456789012', 'first_name' => 'Test', 'last_name' => 'Learner', 'status' => 'active']);
    $enrollment = Enrollment::query()->create(['student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID, 'grade_ID' => GradeLevel::idForValue('grade_7'), 'enrollment_status' => 'temporarily_enrolled']);
    foreach (['show', 'edit', 'print'] as $action) {
        $this->get(route($portal.'.enrollments.'.$action, $enrollment))->assertOk()
            ->assertDontSee('action="'.url('/guidance').'/', false);
    }
    $this->get(route($portal.'.students.show', $student))->assertOk()
        ->assertSee(route($portal.'.students'), false);
    $this->put(route($portal.'.enrollments.update', $enrollment), [])->assertSessionHasErrors();
    $this->getJson(route($portal.'.enrollments.check-lrn', ['LRN' => $student->lrn]))->assertOk();
    $this->get(route($portal.'.reports.enrollment', ['download' => 'summary']))->assertOk()->assertDownload();
})->with(['principal', 'admin']);

test('academic portals reject other staff roles', function (string $portal) {
    $this->actingAs(academicPortalStaff('teacher'));
    foreach (['enrollments.index', 'students', 'reports.enrollment', 'reports.promotion', 'proficiency-levels'] as $page) {
        $this->get(route($portal.'.'.$page))->assertForbidden();
    }
    $this->post(route($portal.'.enrollments.store'), [])->assertForbidden();
})->with(['principal', 'admin']);
