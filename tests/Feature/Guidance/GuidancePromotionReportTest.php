<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PromotionStatus;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $role = Role::query()->firstOrCreate(['role_name' => 'guidance counselor']);
    $this->counselor = User::query()->create(['role_id' => $role->id, 'username' => 'promotion.report', 'password' => 'password', 'first_name' => 'Guidance', 'last_name' => 'Counselor', 'status' => 'active']);
    $this->year = AcademicYear::query()->create(['school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true]);
    $this->oldYear = AcademicYear::query()->create(['school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false]);
    $grade = GradeLevel::query()->firstOrCreate(['grade_label' => 'Grade 7'], ['category' => 'junior_high']);
    $offering = Curriculum::query()->create(['name' => 'Promotion report curriculum', 'grade_ID' => $grade->grade_ID]);
    foreach (PromotionStatus::definitions() as $index => $status) {
        $student = Student::query()->create(['lrn' => '12345678901'.$index, 'first_name' => 'Report'.$index, 'last_name' => 'Learner', 'status' => 'active']);
        Enrollment::query()->create(['student_ID' => $student->id, 'SY_ID' => $this->year->SY_ID, 'curriculum_grade_level_ID' => $offering->curriculum_ID, 'enrollment_status' => 'enrolled', 'promotion_status' => $status['slug']]);
        if ($status['slug'] === 'promoted') {
            Enrollment::query()->create(['student_ID' => $student->id, 'SY_ID' => $this->oldYear->SY_ID, 'curriculum_grade_level_ID' => $offering->curriculum_ID, 'enrollment_status' => 'enrolled', 'promotion_status' => 'promoted']);
        }
    }
});

test('promotion report includes every saved outcome without changing statuses', function () {
    $before = Enrollment::query()->pluck('promotion_status_ID', 'enrollment_ID')->all();
    $this->actingAs($this->counselor)->get(route('guidance.reports.promotion'))->assertOk()
        ->assertSee('Promotion Reports')->assertSee('Outcomes by school year')
        ->assertViewHas('total', 6)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 6 && $rows->every(fn ($row) => $row->total === 1));
    expect(Enrollment::query()->pluck('promotion_status_ID', 'enrollment_ID')->all())->toBe($before);
    $this->get(route('guidance.reports.promotion', ['academic_year_id' => '']))->assertOk()->assertViewHas('total', 7)
        ->assertViewHas('matrices', fn ($matrices) => $matrices['School year']->count() === 2);
});

test('promotion filters match the details summary and downloads', function () {
    $filters = ['academic_year_id' => '', 'status' => 'promoted', 'search' => 'Learner Report4', 'enrollment_status' => 'enrolled'];
    $this->actingAs($this->counselor)->get(route('guidance.reports.promotion', $filters))->assertOk()->assertViewHas('total', 2);
    foreach (['records', 'summary'] as $download) {
        $response = $this->get(route('guidance.reports.promotion', [...$filters, 'download' => $download]));
        $response->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        expect($response->streamedContent())->toContain('2025-2026')->toContain('2026-2027')->toContain('Promoted');
    }
    $response = $this->get(route('guidance.reports.promotion', [...$filters, 'download' => 'chart']));
    $response->assertOk()->assertDownload()->assertHeader('content-type', 'image/svg+xml; charset=UTF-8');
    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
    expect($response->getContent())->toContain('Promotion report')->toContain('2 enrollment records');
});

test('promotion report handles no results and rejects invalid filters', function () {
    $this->actingAs($this->counselor)->get(route('guidance.reports.promotion', ['search' => 'AbsentLearner']))
        ->assertOk()->assertViewHas('total', 0)->assertSee('No promotion records match these filters.');
    $this->getJson(route('guidance.reports.promotion', ['status' => 'invalid']))->assertUnprocessable();
});

test('promotion reports and exports require the guidance role', function () {
    $this->get(route('guidance.reports.promotion'))->assertRedirect();
    $this->counselor->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id]);
    foreach (['', 'records', 'summary', 'chart'] as $download) {
        $this->actingAs($this->counselor)->get(route('guidance.reports.promotion', ['download' => $download]))->assertForbidden();
    }
});
