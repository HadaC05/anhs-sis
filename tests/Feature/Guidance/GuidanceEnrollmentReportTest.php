<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $role = Role::query()->firstOrCreate(['role_name' => 'guidance counselor']);
    $this->counselor = User::query()->create([
        'role_id' => $role->id, 'username' => 'report.counselor', 'password' => 'password',
        'first_name' => 'Guidance', 'last_name' => 'Counselor', 'status' => 'active',
    ]);
    $this->year = AcademicYear::query()->create([
        'school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true,
    ]);
    $this->previousYear = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $this->learner = Student::query()->create([
        'lrn' => '123456789012', 'first_name' => 'Report', 'last_name' => 'Learner', 'sex' => 'female', 'status' => 'active',
    ]);
    $this->grade = GradeLevel::query()->firstOrCreate(['grade_label' => 'Grade 7'], ['category' => 'junior_high']);
    $offering = Curriculum::query()->create(['name' => 'Report curriculum', 'grade_ID' => $this->grade->grade_ID]);
    $pendingLearner = Student::query()->create(['lrn' => '123456789013', 'first_name' => 'Pending', 'last_name' => 'Applicant', 'status' => 'pending']);
    foreach ([[$this->year, 'enrolled'], [$this->year, 'pending'], [$this->previousYear, 'withdrawn']] as [$year, $status]) {
        Enrollment::query()->create([
            'student_ID' => $status === 'pending' ? $pendingLearner->id : $this->learner->id, 'SY_ID' => $year->SY_ID,
            'curriculum_grade_level_ID' => $offering->curriculum_ID,
            'enrollment_status' => $status, 'learner_type' => 'regular',
        ]);
    }
});

test('enrollment report defaults to active year and distinguishes records from learners', function () {
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment'))
        ->assertOk()->assertSee('Enrollment Reports')->assertSee('Learner, Report')
        ->assertViewHas('total', 2)
        ->assertViewHas('summary', fn ($summary) => $summary['Unique learners'] === 2 && $summary['Pending'] === 1 && $summary['Officially enrolled'] === 1)
        ->assertViewHas('breakdowns', fn ($rows) => $rows['Enrollment status']->sum('total') === 2);
    $this->get(route('guidance.reports.enrollment', ['academic_year_id' => '']))
        ->assertOk()->assertViewHas('total', 3)
        ->assertViewHas('summary', fn ($summary) => $summary['Unique learners'] === 2);
});

test('enrollment report applies filters to details summaries and downloads', function () {
    $filters = ['academic_year_id' => '', 'status' => 'withdrawn', 'learner_type' => 'regular', 'section' => 'unassigned', 'search' => '123456789012'];
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment', $filters))
        ->assertOk()->assertViewHas('total', 1)
        ->assertViewHas('records', fn ($rows) => $rows->total() === 1 && $rows->first()->school_year === '2025-2026');
    $response = $this->get(route('guidance.reports.enrollment', [...$filters, 'download' => 'csv']));
    $response->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('2025-2026')->toContain('Withdrawn')->not->toContain('2026-2027');
});

test('enrollment report shows an empty state and validates filters', function () {
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment', ['search' => 'DoesNotExist']))
        ->assertOk()->assertViewHas('total', 0)->assertSee('No enrollment records match these filters.');
    $this->getJson(route('guidance.reports.enrollment', ['status' => 'invalid']))->assertUnprocessable();
});

test('enrollment report and export are restricted to guidance counselors', function () {
    $this->get(route('guidance.reports.enrollment'))->assertRedirect();
    $this->counselor->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id]);
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment'))->assertForbidden();
    $this->get(route('guidance.reports.enrollment', ['download' => 'csv']))->assertForbidden();
    $this->get(route('guidance.reports.enrollment', ['download' => 'summary']))->assertForbidden();
    $this->get(route('guidance.reports.enrollment', ['download' => 'chart', 'chart' => 'status']))->assertForbidden();
});

test('summary download includes filtered totals and all breakdown tables', function () {
    $response = $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment', [
        'academic_year_id' => '', 'status' => 'withdrawn', 'download' => 'summary',
    ]));
    $response->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('All school years')->toContain('Status: Withdrawn')
        ->toContain('"Enrollment records",1')->toContain('"Unique learners",1')
        ->toContain('"Enrollment status",Withdrawn,1,100')
        ->toContain('"Grade level","Grade 7",1,100')
        ->toContain('"Learner type",Regular,1,100')->toContain('Sex,Female,1,100')
        ->toContain('Cluster,')->toContain('Section,Unassigned,1,100');
});

test('enrollment charts download as standalone SVG with the filtered data', function (string $chart) {
    $response = $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment', [
        'academic_year_id' => '', 'status' => 'withdrawn', 'download' => 'chart', 'chart' => $chart,
    ]));
    $response->assertOk()->assertDownload()->assertHeader('content-type', 'image/svg+xml; charset=UTF-8');
    $svg = simplexml_load_string($response->getContent());
    expect($svg)->not->toBeFalse();
    expect($response->getContent())->toContain('1 enrollment records')->toContain('Status: Withdrawn')->toContain('100.0%');
})->with(['status', 'grade', 'learner', 'sex']);

test('school year comparison includes historical records while details retain the selected year', function () {
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment'))
        ->assertOk()->assertViewHas('total', 2)
        ->assertViewHas('comparison', function ($comparison) {
            expect($comparison['totals']->pluck('total')->all())->toBe([1, 2]);
            expect($comparison['totals'][1]->change)->toBe(1);
            expect($comparison['totals'][1]->percent)->toBe(100.0);
            expect($comparison['grades'][0]['counts'])->toBe([1, 2]);

            return true;
        });
    $this->get(route('guidance.reports.enrollment', ['status' => 'enrolled']))
        ->assertOk()->assertViewHas('comparison', function ($comparison) {
            expect($comparison['totals']->pluck('total')->all())->toBe([0, 1]);
            expect($comparison['totals'][1]->percent)->toBeNull();

            return true;
        });
    $this->get(route('guidance.reports.enrollment', ['academic_year_id' => $this->previousYear->SY_ID]))
        ->assertOk()->assertViewHas('comparison', fn ($comparison) => $comparison['years']->count() === 1)
        ->assertSee('At least two school years');
});

test('comparison exports include historical counts and valid standalone charts', function () {
    $this->actingAs($this->counselor);
    foreach (['year-trend', 'year-grade'] as $chart) {
        $response = $this->get(route('guidance.reports.enrollment', ['download' => 'chart', 'chart' => $chart]));
        $response->assertOk()->assertDownload()->assertHeader('content-type', 'image/svg+xml; charset=UTF-8');
        expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
        expect($response->getContent())->toContain('2025-2026')->toContain('2026-2027');
    }
    $response = $this->get(route('guidance.reports.enrollment', ['download' => 'comparison']));
    $response->assertOk()->assertDownload();
    expect($response->streamedContent())->toContain('2025-2026,1,N/A,N/A')->toContain('2026-2027,2,1,100')->toContain('"Grade 7",1,2');
    $this->get(route('guidance.reports.enrollment', ['download' => 'chart', 'chart' => 'year-grade', 'search' => 'NoSuchLearner']))
        ->assertOk()->assertSee('No matching enrollment records.');
});

test('comparison retains empty school years and limits the window to five', function () {
    foreach (range(2019, 2024) as $start) {
        AcademicYear::query()->create([
            'school_year' => $start.'-'.($start + 1), 'start_date' => $start.'-06-01',
            'end_date' => ($start + 1).'-03-31', 'status' => false,
        ]);
    }
    $this->actingAs($this->counselor)->get(route('guidance.reports.enrollment'))
        ->assertOk()->assertViewHas('comparison', function ($comparison) {
            expect($comparison['years']->pluck('school_year')->all())->toBe(['2022-2023', '2023-2024', '2024-2025', '2025-2026', '2026-2027']);
            expect($comparison['totals']->pluck('total')->all())->toBe([0, 0, 0, 1, 2]);

            return true;
        });
});

test('chart exports validate chart names and handle empty data', function () {
    $this->actingAs($this->counselor)->getJson(route('guidance.reports.enrollment', ['download' => 'chart', 'chart' => 'unknown']))->assertUnprocessable();
    $this->getJson(route('guidance.reports.enrollment', ['download' => 'chart']))->assertUnprocessable();
    $this->get(route('guidance.reports.enrollment', ['download' => 'chart', 'chart' => 'grade', 'search' => 'NoSuchLearner']))
        ->assertOk()->assertSee('No matching enrollment records.')->assertDontSee('NaN');
});
