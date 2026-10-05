<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\Sf9ReportCardBuilder;
use Illuminate\Support\Facades\Hash;

function createAdvisorySf9Fixtures(bool $isSeniorHigh = true): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => $isSeniorHigh ? 'teacher.sf9.shs' : 'teacher.sf9.jhs',
        'password' => Hash::make('password'),
        'first_name' => 'Ada',
        'last_name' => 'Adviser',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => $isSeniorHigh ? 'DepEd SHS - STEM' : 'Junior High Curriculum',
        'description' => 'Test curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()
        ->where('grade_label', $isSeniorHigh ? 'Grade 11' : 'Grade 7')
        ->firstOrFail();
    $cluster = Cluster::query()->create([
        'name' => $isSeniorHigh ? 'Science, Technology, Engineering and Mathematics' : 'General',
    ]);
    $course = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'STEM',
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => $isSeniorHigh ? 'ORALCOM' : 'ENG7',
        'title' => $isSeniorHigh ? 'Oral Communication' : 'English',
        'type' => 'core',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => $isSeniorHigh ? 'grade_11' : 'grade_7',
        'semester' => 'first',
    ]);

    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'cluster_ID' => $isSeniorHigh ? $cluster->cluster_ID : null,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    if ($isSeniorHigh) {
        $specializedSubject = Subject::query()->create([
            'cluster_ID' => $cluster->cluster_ID,
            'code' => 'PRECALC',
            'title' => 'Pre-Calculus',
            'type' => 'specialized',
            'status' => 'active',
        ]);

        $specializedCurriculumSubject = CurriculumSubject::query()->create([
            'curriculum_ID' => $curriculum->curriculum_ID,
            'subject_ID' => $specializedSubject->subject_ID,
            'cluster_ID' => $cluster->cluster_ID,
            'grade_level' => 'grade_11',
            'semester' => 'second',
        ]);

        TeacherSubjectAssignment::query()->create([
            'section_ID' => $section->section_ID,
            'curr_subj_ID' => $specializedCurriculumSubject->curr_subj_ID,
            'staff_ID' => $teacher->staff_id,
            'SY_ID' => $academicYear->SY_ID,
        ]);
    }

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'middle_name' => 'Cruz',
        'last_name' => 'Santos',
        'sex' => 'female',
        'birthdate' => '2009-06-15',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'cluster_ID' => $isSeniorHigh ? $cluster->cluster_ID : null,
        'course_ID' => $isSeniorHigh ? $course->course_ID : null,
        'semester' => $isSeniorHigh ? 'first' : null,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $studentSubject = StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
    ]);

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey($isSeniorHigh ? 'shs_sem1_term_1' : 'term_1'),
        'numeric_grade' => 91,
        'posted_by' => $teacher->staff_id,
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('advisory teacher can print the junior high sf9 layout', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures(isSeniorHigh: false);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section));

    $response->assertOk();
    $response->assertSee('SF 9 - JHS');
    $response->assertSee('Learning Areas');
    $response->assertSee('Filipino');
    $response->assertSee('MAPEH');
    $response->assertSee('General Average');
    $response->assertDontSee('SF9-SHS');
    $response->assertDontSee('Track / Strand');
    $response->assertDontSee('Semester Final Grade');
});

test('sf9 attendance uses the configured school year months and totals', function (bool $isSeniorHigh, array $months) {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures($isSeniorHigh);
    $section->academicYear->update([
        'attendance_start_month' => $months[0],
        'attendance_end_month' => $months[array_key_last($months)],
    ]);

    // June is outside both configured ranges, but still has historical records.
    foreach ([$months[0], $months[array_key_last($months)], 6] as $month) {
        AcademicYearAttendanceSetting::factory()->create([
            'SY_ID' => $section->SY_ID,
            'month' => $month,
            'school_days' => 20,
        ]);
        EnrollmentMonthlyAttendance::query()->create([
            'enrollment_ID' => $enrollment->enrollment_ID,
            'month' => $month,
            'days_present' => 18,
            'days_absent' => 2,
        ]);
    }

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section));
    $response->assertOk();
    $card = $response->viewData('cards')->first();
    expect($card['attendance_months'])->toBe($months)
        ->and($card['attendance']['total_school_days'])->toBe(40)
        ->and($card['attendance']['total_present'])->toBe(36)
        ->and($card['attendance']['total_absent'])->toBe(4);

    $labels = \App\Models\Month::labels();
    $headers = array_map(fn (int $month): string => $isSeniorHigh
        ? '<th>'.Sf9ReportCardBuilder::attendanceMonthAbbreviation($month).'</th>'
        : '<th class="month">'.$labels[$month].'</th>', $months);
    $response->assertSeeInOrder($headers, false);
    $response->assertDontSee($isSeniorHigh ? '<th>Jun</th>' : '<th class="month">June</th>', false);
})->with([
    'junior high across calendar years' => [false, [8, 9, 10, 11, 12, 1, 2, 3, 4, 5]],
    'senior high across calendar years' => [true, [8, 9, 10, 11, 12, 1, 2, 3, 4, 5]],
    'junior high within one calendar year' => [false, [2, 3, 4, 5]],
    'senior high within one calendar year' => [true, [2, 3, 4, 5]],
]);

test('advisory teacher can print the senior high sf9 performance report layout', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section));

    $response->assertOk();
    $response->assertSee("Learner's Performance Report", false);
    $response->assertSee('Learning Progress and Achievement');
    $response->assertSee('Learning Areas');
    $response->assertSee('Core Subjects');
    $response->assertSee('Elective Subjects');
    $response->assertSee('Effective Communication');
    $response->assertSee('Mabisang Komunikasyon');
    $response->assertSee('General Mathematics');
    $response->assertSee('Pre-Calculus');
    $response->assertSee('Final Grade');
    $response->assertSee('General Average');
    $response->assertSee('Performance Descriptors');
    $response->assertSee('Advancing');
    $response->assertSee('Benchmarking');
    $response->assertSee("Teacher's Comments/ Remarks", false);
    $response->assertSee('Track (SHS only):');
    $response->assertSee('ACADEMIC');
    $response->assertSee('Term 1');
    $response->assertSee('Term 3');
    $response->assertSee('No. of Class Days');
    $response->assertSee('Santos');
    $response->assertSee('91');
    $response->assertDontSee('SF 9 - JHS');
    $response->assertDontSee('SF9-SHS');
    $response->assertDontSee('First Semester');
    $response->assertDontSee('Semester Final Grade');
    $response->assertDontSee('Applied and Specialized Subjects');
    $response->assertDontSee('Track / Strand');
    $response->assertDontSee("Report on Learner's Observed Values", false);
    $response->assertDontSee('>MAPEH</td>', false);
    $response->assertDontSee('Quarter 1');
    $response->assertDontSee('1st Quarter');
});

test('configured updated junior high SF9 is used for teacher and academic record printing and can be reverted', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures(isSeniorHigh: false);
    $settings = \App\Models\Sf9Configuration::create(['id' => 1, 'junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section))->assertOk()
        ->assertSee('jhs-updated')->assertSee('GMRC / Values Education')->assertSee('Benchmarking')->assertDontSee('Report on Learner');
    $this->post(route('teacher.advisory.sf9', $section), ['enrollment_ids' => [$enrollment->enrollment_ID]])->assertOk()->assertSee('GMRC / Values Education');
    $registrar = Staff::create(['role_id' => Role::firstOrCreate(['role_name' => 'registrar'])->id, 'username' => 'sf9.registrar', 'password' => 'password', 'first_name' => 'Record', 'last_name' => 'Manager', 'status' => 'active']);
    $this->actingAs($registrar)->get(route('registrar.students.sf9', ['student' => $enrollment->student_ID, 'enrollment' => $enrollment]))->assertOk()->assertSee('GMRC / Values Education');
    $settings->update(['junior_high' => 'jhs_legacy']);
    $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section))->assertOk()->assertSee('SF 9 - JHS')->assertDontSee('GMRC / Values Education');
});

test('updated junior high selection does not change senior high SF9', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures(isSeniorHigh: true);
    \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section))->assertOk()->assertSee('Life')->assertDontSee('GMRC / Values Education');
});

test('new SF9 replaces observed values with persisted term comments and preserves both when switching formats', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures(false);
    $settings = \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    \App\Models\GradingTerm::query()->juniorHigh()->where('key', 'term_1')->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    \App\Models\StudentObservedValue::create(['enrollment_ID' => $enrollment->enrollment_ID, 'statement_key' => 'maka_diyos_spiritual_respect', 'grading_period' => 'term_1', 'marking' => 'AO', 'status' => 'recorded', 'posted_by' => $teacher->staff_id]);
    $this->actingAs($teacher)->get(route('teacher.advisory.observed-values', $section))->assertOk()->assertSee('Teacher Remarks')->assertDontSee('Save Observed Values');
    $url = route('teacher.advisory.comments.store', $section);
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Shows steady progress.']])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('student_sf9_comments', ['enrollment_ID' => $enrollment->enrollment_ID, 'grading_period' => 'term_1', 'comment' => 'Shows steady progress.', 'posted_by' => $teacher->staff_id]);
    $this->get(route('teacher.advisory.sf9', $section))->assertOk()->assertSee('Shows steady progress.');
    $this->post(route('teacher.advisory.sf9', $section), ['enrollment_ids' => [$enrollment->enrollment_ID]])->assertOk()->assertSee('Shows steady progress.');
    $registrarView = (new \App\Http\Controllers\Registrar\RegistrarDashboardController)->studentSf9($enrollment->student, $enrollment->fresh());
    expect($registrarView->render())->toContain('Shows steady progress.');
    $this->post(route('teacher.advisory.observed-values.store', $section), [])->assertForbidden();
    $this->post(route('teacher.advisory.observed-values.bulk', $section), [])->assertForbidden();
    $settings->update(['junior_high' => 'jhs_legacy']);
    $this->get(route('teacher.advisory.observed-values', $section))->assertOk()->assertSee('Save Observed Values')->assertDontSee('Save Remarks');
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Changed']])->assertForbidden();
    $this->assertDatabaseCount('student_observed_values', 1);
    $settings->update(['junior_high' => 'jhs_2026']);
    $this->get(route('teacher.advisory.observed-values', $section))->assertSee('Shows steady progress.');
});

test('SF9 comments enforce term locks ownership length and safe output', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures(false);
    \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    \App\Models\GradingTerm::query()->juniorHigh()->where('key', 'term_1')->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    $this->actingAs($teacher);
    $url = route('teacher.advisory.comments.store', $section);
    $this->post($url, ['grading_period' => 'term_2', 'comments' => [$enrollment->enrollment_ID => 'Locked']])->assertSessionHasErrors('grading_period');
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => str_repeat('x', 241)]])->assertSessionHasErrors('comments.'.$enrollment->enrollment_ID);
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [999999 => 'Other learner']])->assertForbidden();
    $this->assertDatabaseCount('student_sf9_comments', 0);
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => '<script>alert(1)</script>']])->assertSessionHasNoErrors();
    $this->get(route('teacher.advisory.sf9', $section))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => '']])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('student_sf9_comments', 0);
    \App\Models\GradingTerm::closeAllJuniorHighTerms();
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Closed']])->assertSessionHasErrors('grading_period');
    $other = Staff::create(['role_id' => $teacher->role_id, 'username' => 'other.adviser', 'password' => 'password', 'first_name' => 'Other', 'last_name' => 'Teacher', 'status' => 'active']);
    $this->actingAs($other)->get(route('teacher.advisory.observed-values', $section))->assertForbidden();
    $this->post($url, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Unauthorized']])->assertForbidden();
});

test('senior high retains observed values when junior high uses comments', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures(true);
    \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    $this->actingAs($teacher)->get(route('teacher.advisory.observed-values', $section))->assertOk()->assertSee('Observed Values')->assertDontSee('Teacher Remarks');
    $this->post(route('teacher.advisory.comments.store', $section), [])->assertForbidden();
});

test('teacher remarks separates active input from the read only overview and uses result toasts', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures(false);
    \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    \App\Models\GradingTerm::query()->juniorHigh()->where('key', 'term_1')->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    \App\Models\StudentSf9Comment::create(['enrollment_ID' => $enrollment->enrollment_ID, 'grading_period' => 'term_2', 'comment' => 'Previously saved remarks']);
    $page = route('teacher.advisory.observed-values', $section);
    $save = route('teacher.advisory.comments.store', $section);
    $response = $this->actingAs($teacher)->get($page)->assertOk()->assertSee('Teacher Remarks')->assertSee('Overview');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@id="remarks-input-panel"]//textarea')->length)->toBe(1)
        ->and($xpath->query('//*[@id="remarks-input-panel"]//thead/th | //*[@id="remarks-input-panel"]//thead/tr/th')->length)->toBe(2)
        ->and($xpath->query('//*[@id="remarks-overview-panel"]//textarea')->length)->toBe(0)
        ->and($document->getElementById('remarks-overview-panel')->hasAttribute('hidden'))->toBeTrue()
        ->and($document->getElementById('remarks-overview-panel')->textContent)->toContain('Previously saved remarks');
    $this->from($page)->post($save, ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Saved remarks']])->assertRedirect($page);
    $this->get($page)->assertSee('data-test="teacher-remarks-status"', false)->assertSee('Teacher remarks saved successfully.');
    $this->from($page)->post($save, ['grading_period' => 'term_2', 'comments' => [$enrollment->enrollment_ID => 'Keep this draft']])->assertSessionHasErrors('grading_period');
    $this->get($page)->assertSee('data-test="teacher-remarks-error"', false)->assertSee('Keep this draft');
    \App\Models\GradingTerm::closeAllJuniorHighTerms();
    $response = $this->get($page)->assertOk()->assertSee('No grading term is open.')->assertSee('Previously saved remarks');
    expect($response->getContent())->not->toContain('<textarea');
});

test('teacher remarks save failures show an error toast and keep the draft', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf9Fixtures(false);
    \App\Models\Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    \App\Models\GradingTerm::query()->juniorHigh()->where('key', 'term_1')->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    $database = \Illuminate\Support\Facades\DB::getFacadeRoot();
    \Illuminate\Support\Facades\Exceptions::fake();
    $mockDatabase = Mockery::mock($database);
    $mockDatabase->shouldReceive('transaction')->once()->andThrow(new RuntimeException('Simulated storage failure'));
    \Illuminate\Support\Facades\DB::swap($mockDatabase);
    $page = route('teacher.advisory.observed-values', $section);
    $this->actingAs($teacher)->from($page)->post(route('teacher.advisory.comments.store', $section), ['grading_period' => 'term_1', 'comments' => [$enrollment->enrollment_ID => 'Unsaved draft']])->assertRedirect($page)->assertSessionHas('error');
    \Illuminate\Support\Facades\DB::swap($database);
    $this->get($page)->assertOk()->assertSee('data-test="teacher-remarks-error"', false)->assertSee('Teacher remarks could not be saved.')->assertSee('Unsaved draft');
    $this->assertDatabaseCount('student_sf9_comments', 0);
});
