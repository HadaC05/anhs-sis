<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionSf2Upload;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createAdvisoryAttendanceFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.attendance',
        'password' => Hash::make('password'),
        'first_name' => 'Advisory',
        'last_name' => 'Teacher',
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

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Einstein',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'room' => 'Room 201',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '987654321098',
        'first_name' => 'Juan',
        'last_name' => 'Dela',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('advisory teacher can view attendance record page', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('Attendance Record');
    $response->assertSee('Monthly Attendance Summary');
    $response->assertSee('Upload SF2');
    $response->assertDontSee('Save Attendance');
});

test('attendance summary displays imported monthly counts', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 6,
        'school_days' => 20,
    ]);

    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 6,
        'days_present' => 19,
        'days_absent' => 1,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('19', false);
    $response->assertSee('20', false);
    $response->assertSee('School days are set by the administrator');
});

test('advisory attendance displays admin configured school days', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 7,
        'school_days' => 23,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('23', false);
    $response->assertSee('School Days');
});

test('advisory teacher can upload sf2 pdf for future import', function () {
    Storage::fake('local');

    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 6,
        'sf2_file' => UploadedFile::fake()->create('SF2-June.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $upload = SectionSf2Upload::query()->where('section_ID', $section->section_ID)->first();

    expect($upload)->not->toBeNull();
    expect($upload->status)->toBe('pending');
    expect($upload->report_month)->toBe(6);
    Storage::disk('local')->assertExists($upload->storage_path);
});

test('non adviser cannot access attendance record page', function () {
    ['section' => $section] = createAdvisoryAttendanceFixtures();

    $role = Role::query()->create(['role_name' => 'teacher-other']);
    $otherTeacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.other',
        'password' => Hash::make('password'),
        'first_name' => 'Other',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    $response = $this->actingAs($otherTeacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertForbidden();
});
