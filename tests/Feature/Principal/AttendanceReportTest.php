<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;

function attendanceReportPrincipal(string $role = 'principal'): Staff
{
    return Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'report.'.$role, 'password' => bcrypt('password'),
        'first_name' => 'Report', 'last_name' => 'User', 'status' => 'active',
    ]);
}

test('principal attendance report handles an unconfigured school and is role protected', function () {
    $this->get(route('principal.reports.attendance'))->assertRedirect();
    $this->actingAs(attendanceReportPrincipal('teacher'))->get(route('principal.reports.attendance'))->assertForbidden();
    $this->actingAs(attendanceReportPrincipal())->get(route('principal.reports.attendance'))
        ->assertOk()->assertSee('No school year configured')->assertSee('No sections found');
});

test('attendance report separates missing records from absences and current movement from monthly attendance', function () {
    $principal = attendanceReportPrincipal();
    $year = AcademicYear::query()->create([
        'school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true,
    ]);
    $curriculum = Curriculum::query()->create(['name' => 'Report curriculum', 'status' => true]);
    $grade = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $section = Section::query()->create([
        'name' => 'Report section', 'grade_ID' => $grade->grade_ID, 'SY_ID' => $year->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID, 'capacity' => 40,
    ]);
    AcademicYearAttendanceSetting::factory()->create(['SY_ID' => $year->SY_ID, 'month' => 6, 'school_days' => 20]);
    foreach ([['male', 'enrolled', 'regular', 18], ['female', 'transferred_out', 'transferee', 10], [null, 'enrolled', 'regular', null]] as $index => [$sex, $status, $type, $present]) {
        $student = Student::query()->create([
            'lrn' => '98765432100'.$index, 'first_name' => 'Learner', 'last_name' => 'Report '.$index, 'sex' => $sex, 'status' => 'active',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID,
            'enrollment_status' => $status, 'learner_type' => $type,
        ]);
        if ($present !== null) {
            EnrollmentMonthlyAttendance::query()->create([
                'enrollment_ID' => $enrollment->enrollment_ID, 'month' => 6, 'days_present' => $present, 'days_absent' => 20 - $present,
            ]);
        }
    }
    $url = route('principal.reports.attendance', ['academic_year_id' => $year->SY_ID, 'month' => 6]);
    $this->actingAs($principal)->get($url)->assertOk()->assertSee('Unspecified')
        ->assertViewHas('summary', fn ($summary) => $summary['registered'] === 2 && $summary['recorded'] === 2 && $summary['learners'] === 3 && $summary['rate'] === 70.0)
        ->assertViewHas('rows', function ($rows) {
            $total = $rows->firstWhere('sex', 'Total');

            return $total['average'] === 1.4 && $total['transferred_out'] === 1 && $total['transferees'] === 1
                && $rows->firstWhere('sex', 'Unspecified')['present'] === null;
        });
    $section->update(['name' => '=Report section']);
    $download = $this->get($url.'&download=csv')->assertOk()->assertDownload('attendance-movement-'.$year->SY_ID.'-6.csv');
    expect($download->streamedContent())->toContain("'=Report section", 'current status snapshots', '70');
    $this->get(route('principal.reports.attendance', ['month' => 7]))->assertOk()
        ->assertViewHas('summary', fn ($summary) => $summary['recorded'] === 0 && $summary['rate'] === null);
    $this->get(route('principal.reports.attendance', ['month' => 4]))->assertSessionHasErrors('month');
    $this->get(route('principal.reports.attendance', ['grade_id' => GradeLevel::query()->where('grade_label', 'Grade 8')->value('grade_ID')]))
        ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
});
