<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradingTerm;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;

beforeEach(function () {
    $this->registrar = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'registrar'])->id,
        'username' => 'reports.registrar', 'password' => bcrypt('password'),
        'first_name' => 'Reports', 'last_name' => 'Registrar', 'status' => 'active', 'change_password' => false,
    ]);
    $this->actingAs($this->registrar);
});

function registrarReportFixture(string $yearLabel, bool $active): array
{
    $year = AcademicYear::query()->create(['school_year' => $yearLabel, 'start_date' => substr($yearLabel, 0, 4).'-06-01', 'end_date' => substr($yearLabel, 5, 4).'-03-31', 'status' => $active]);
    $curriculum = Curriculum::query()->create(['name' => 'Report '.$yearLabel, 'status' => true]);
    $subject = Subject::query()->create(['code' => 'R'.$year->SY_ID, 'title' => '=Report subject '.$yearLabel, 'type' => 'core', 'status' => 'active']);
    $curriculumSubject = CurriculumSubject::query()->create(['curriculum_ID' => $curriculum->curriculum_ID, 'subject_ID' => $subject->subject_ID, 'grade_level' => 'grade_11', 'semester' => 'first']);
    $section = Section::query()->create(['name' => 'Report section '.$yearLabel, 'grade_ID' => GradeLevel::idForValue('grade_11'), 'SY_ID' => $year->SY_ID, 'curriculum_ID' => $curriculum->curriculum_ID, 'capacity' => 40]);
    $assignment = TeacherSubjectAssignment::query()->create(['section_ID' => $section->section_ID, 'curr_subj_ID' => $curriculumSubject->curr_subj_ID, 'SY_ID' => $year->SY_ID, 'staff_ID' => test()->registrar->staff_id]);
    $student = Student::query()->create(['lrn' => (string) (123456780000 + $year->SY_ID), 'first_name' => 'Test', 'last_name' => 'Learner', 'status' => 'active']);
    $enrollment = Enrollment::query()->create(['student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID, 'enrollment_status' => 'enrolled']);
    $roster = StudentSubject::query()->firstOrCreate(['enrollment_ID' => $enrollment->enrollment_ID, 'curr_subj_ID' => $curriculumSubject->curr_subj_ID]);
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();
    StudentSubjectGrade::query()->create(['student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID, 'term_ID' => $term->term_ID, 'numeric_grade' => 90, 'status' => 'submitted', 'posted_by' => test()->registrar->staff_id]);

    return [$year, $section, $term, $enrollment];
}

test('registrar reports render empty states and sidebar links', function () {
    foreach (['enrollment', 'grades'] as $report) {
        $this->get(route('registrar.reports.index', compact('report')))->assertOk()
            ->assertSee('No records found')->assertSee('Export CSV')->assertSee('Print / Save PDF')
            ->assertSee('Enrollment Summary')->assertSee('Grade Approval Status')->assertViewHas('total', 0);
    }
});

test('enrollment report scopes years and grades and includes unassigned records', function () {
    [$current, $section, , $enrollment] = registrarReportFixture('2026-2027', true);
    [$old] = registrarReportFixture('2025-2026', false);
    $this->get(route('registrar.reports.index'))->assertOk()->assertViewHas('total', 1)->assertSee($section->name)->assertDontSee('Report section 2025-2026');
    $this->get(route('registrar.reports.index', ['academic_year_id' => $old->SY_ID]))->assertOk()->assertViewHas('total', 1)->assertDontSee($section->name);
    $this->get(route('registrar.reports.index', ['grade_id' => GradeLevel::idForValue('grade_7')]))->assertOk()->assertViewHas('total', 0);
    $enrollment->update(['section_ID' => null]);
    $this->get(route('registrar.reports.index'))->assertOk()->assertViewHas('total', 1)->assertSee('Unassigned');
});

test('grade report counts statuses and filters terms while retaining zero record classes', function () {
    [$year, $section, $term] = registrarReportFixture('2026-2027', true);
    registrarReportFixture('2025-2026', false);
    $response = $this->get(route('registrar.reports.index', ['report' => 'grades', 'term_id' => $term->term_ID]))->assertOk()->assertViewHas('total', 1);
    expect($response->viewData('rows')->first())->toBe(['Grade 11', $section->name, '=Report subject 2026-2027', 'Reports Registrar', 0, 1, 0, 0, 0, 1]);
    $otherTerm = GradingTerm::query()->where('term_ID', '!=', $term->term_ID)->firstOrFail();
    $this->get(route('registrar.reports.index', ['report' => 'grades', 'term_id' => $otherTerm->term_ID]))->assertOk()->assertViewHas('total', 0)->assertSee($section->name);
});

test('CSV export uses filtered results and escapes spreadsheet formulas', function () {
    [$year] = registrarReportFixture('2026-2027', true);
    registrarReportFixture('2025-2026', false);
    $response = $this->get(route('registrar.reports.index', ['report' => 'grades', 'academic_year_id' => $year->SY_ID, 'format' => 'csv']))->assertOk()->assertDownload('registrar-grades-'.$year->SY_ID.'.csv');
    expect($response->streamedContent())->toContain("'=Report subject 2026-2027")->not->toContain('2025-2026');
});

test('reports validate filters and reject other roles including exports', function () {
    $this->getJson(route('registrar.reports.index', ['academic_year_id' => 999999, 'grade_id' => 99999, 'term_id' => 99999, 'report' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors(['academic_year_id', 'grade_id', 'term_id', 'report']);
    $this->registrar->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id]);
    $this->actingAs($this->registrar->fresh());
    $this->get(route('registrar.reports.index'))->assertForbidden();
    $this->get(route('registrar.reports.index', ['format' => 'csv']))->assertForbidden();
});
