<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\AssignmentGradeTermUnlocker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createRegistrarClassSubjectFixtures(): array
{
    $registrarRole = Role::query()->create(['role_name' => 'registrar']);
    $registrar = Staff::query()->create([
        'role_id' => $registrarRole->id,
        'username' => 'registrar.class',
        'password' => Hash::make('password'),
        'first_name' => 'Reg',
        'last_name' => 'istrar',
        'status' => 'active',
    ]);

    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.class',
        'password' => Hash::make('password'),
        'first_name' => 'Class',
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

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();

    $cluster = Cluster::query()->create(['name' => 'Core']);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'ENG11',
        'title' => 'English 11',
        'type' => 'core',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_11',
        'semester' => 'first',
    ]);

    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'room' => 'Room 301',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    return compact('registrar', 'teacher', 'section', 'assignment', 'academicYear', 'gradeLevel', 'subject');
}

test('registrar can view class subjects index', function () {
    ['registrar' => $registrar, 'section' => $section, 'subject' => $subject] = createRegistrarClassSubjectFixtures();

    $response = $this->actingAs($registrar)->get(route('registrar.class-subjects.index'));

    $response->assertOk();
    $response->assertSee('Class Subjects');
    $response->assertDontSee('Teacher Assignments');
    $response->assertSee($section->name);
    $response->assertSee('View subjects');
    $response->assertSee($subject->code);
    $response->assertSee('section-subjects-'.$section->section_ID);
    $response->assertSee('Grade submission');
    $response->assertDontSee('Loading subjects');
    expect($response->viewData('sectionProgress')[$section->section_ID])->toBe(['submitted' => 0, 'expected' => 1]);
    $response->assertSee(route('registrar.classes.subjects', $section));
});

test('registrar can view subject and grade status for an advisory class', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'subject' => $subject] = createRegistrarClassSubjectFixtures();

    $response = $this->actingAs($registrar)->get(route('registrar.classes.status', $section));

    $response->assertOk();
    $response->assertSee('Class subject status');
    $response->assertSee($section->name);
    $response->assertSee($subject->code);
    $response->assertSee(trim($teacher->last_name.', '.$teacher->first_name));
    $response->assertSee('Ungraded');
    $response->assertSee('View terms');
    $response->assertDontSee('Class roster');
});

test('registrar class status shows submitted grade status for assigned subjects', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'assignment' => $assignment, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel, 'subject' => $subject] = createRegistrarClassSubjectFixtures();

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Class',
        'last_name' => 'Student',
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

    $studentSubject = StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'curr_subj_ID' => $assignment->curr_subj_ID,
    ]);

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => GradingTerm::query()->where('key', 'term_1')->value('term_ID'),
        'numeric_grade' => 90,
        'grade_status_ID' => GradeStatus::idFor(GradeStatus::SUBMITTED),
        'posted_by' => $teacher->staff_id,
    ]);

    $status = $this->actingAs($registrar)->get(route('registrar.classes.status', $section));
    $status->assertOk()
        ->assertSee('Submitted')
        ->assertSee('1 record')
        ->assertDontSee('Student, Class');
});

test('registrar can unlock a grading term for a class subject', function (bool $json) {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'assignment' => $assignment, 'gradeLevel' => $gradeLevel, 'academicYear' => $academicYear] = createRegistrarClassSubjectFixtures();

    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);

    $student = Student::query()->create([
        'lrn' => '888888888888',
        'first_name' => 'Test',
        'last_name' => 'Student',
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

    $studentSubject = StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'curr_subj_ID' => $assignment->curr_subj_ID,
    ]);

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_1'),
        'numeric_grade' => 88,
        'status' => 'approved',
        'posted_by' => $teacher->staff_id,
    ]);

    $response = $this->actingAs($registrar)->{$json ? 'postJson' : 'post'}(route('registrar.class-subjects.unlock-term', $assignment), [
        'grading_period' => 'shs_sem1_term_1',
        'notes' => 'Principal approved correction',
    ]);

    if ($json) {
        $response->assertOk()->assertJsonStructure(['message']);
    } else {
        $response->assertRedirect(route('registrar.class-subjects.show', $assignment));
        $response->assertSessionHas('status');
    }

    expect(AssignmentGradeTermUnlocker::unlockedPeriodKeysFor($assignment->assignment_ID))->toContain('shs_sem1_term_1');

    $grade = StudentSubjectGrade::query()->where('assignment_ID', $assignment->assignment_ID)->first();
    expect($grade?->status)->toBe('draft');

    $this->actingAs($registrar)->get(route('registrar.classes.subjects', $section))
        ->assertOk()->assertSee('Draft')->assertSee('Registrar unlocked')->assertSee('1 draft');
})->with([false, true]);


test('registrar can view section subjects and term actions in the modal', function () {
    ['registrar' => $registrar, 'section' => $section, 'subject' => $subject, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);

    $this->actingAs($registrar)->get(route('registrar.classes.subjects', $section))
        ->assertOk()
        ->assertSee($subject->code)
        ->assertSee('Ungraded')
        ->assertSee('Grade submission')
        ->assertSee('Edit term status')
        ->assertSee('Unlock term')
        ->assertSee(route('registrar.class-subjects.unlock-term', $assignment))
        ->assertDontSee('Back to Class Subjects');
});

test('subject modal handles sections without assignments', function () {
    ['registrar' => $registrar, 'section' => $section, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();
    $assignment->delete();

    $this->actingAs($registrar)->get(route('registrar.classes.subjects', $section))
        ->assertOk()->assertSee('No subjects assigned for this section.');
});

test('modal unlock rejects invalid periods without unlocking the assignment', function () {
    ['registrar' => $registrar, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();

    $this->actingAs($registrar)->postJson(route('registrar.class-subjects.unlock-term', $assignment), [
        'grading_period' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors('grading_period');

    expect(AssignmentGradeTermUnlocker::unlockedPeriodKeysFor($assignment->assignment_ID))->toBe([]);
});

test('teachers cannot access registrar subject modal or unlock terms', function () {
    ['teacher' => $teacher, 'section' => $section, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();

    $this->actingAs($teacher)->getJson(route('registrar.classes.subjects', $section))->assertForbidden();
    $this->actingAs($teacher)->postJson(route('registrar.class-subjects.unlock-term', $assignment), [
        'grading_period' => 'shs_sem1_term_1',
    ])->assertForbidden();
});


test('preloaded subject modals use a bounded number of queries as sections grow', function () {
    ['registrar' => $registrar, 'section' => $section, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);
    $this->actingAs($registrar);
    $url = route('registrar.class-subjects.index', ['per_page' => 20]);
    $this->get($url)->assertOk();

    \Illuminate\Support\Facades\DB::enableQueryLog();
    \Illuminate\Support\Facades\DB::flushQueryLog();
    $this->get($url)->assertOk();
    $singleCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    for ($i = 1; $i <= 10; $i++) {
        $extraSection = $section->replicate();
        $extraSection->name = 'Additional section '.$i;
        $extraSection->save();
        $extraAssignment = $assignment->replicate();
        $extraAssignment->section_ID = $extraSection->section_ID;
        $extraAssignment->save();
    }

    \Illuminate\Support\Facades\DB::enableQueryLog();
    \Illuminate\Support\Facades\DB::flushQueryLog();
    $this->get($url)->assertOk()->assertSee('Additional section 10')->assertDontSee('Loading subjects');
    $manyCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect($manyCount)->toBeLessThanOrEqual($singleCount + 5);
});

test('bulk term summaries preserve the existing term status rules', function () {
    ['section' => $section, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);
    $assignment->load(['section.gradeLevel', 'curriculumSubject.gradingSemester']);
    $periods = GradingTerm::openPeriodsForSection($section, $assignment->curriculumSubject?->semester);
    $bulk = AssignmentGradeTermUnlocker::termSummariesForAssignments([$assignment]);

    foreach ($periods as $index => $period) {
        $expected = AssignmentGradeTermUnlocker::termSummary($assignment, $period);
        expect(array_intersect_key($bulk[$assignment->assignment_ID][$index], $expected))->toBe($expected);
    }
});


test('section submission totals count complete subjects rather than individual grades', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'assignment' => $assignment, 'academicYear' => $year, 'gradeLevel' => $level] = createRegistrarClassSubjectFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);

    foreach (['submitted', 'approved', 'released', 'draft', 'submitted'] as $index => $status) {
        $student = Student::query()->create([
            'lrn' => (string) (123456780000 + $index),
            'first_name' => 'Student', 'last_name' => (string) $index, 'status' => 'active',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_ID' => $student->id, 'section_ID' => $section->section_ID,
            'SY_ID' => $year->SY_ID, 'grade_ID' => $level->grade_ID,
            'learner_type' => 'regular', 'enrollment_status' => $index === 4 ? 'withdrawn' : 'enrolled',
        ]);
        $studentSubject = StudentSubject::query()->firstOrCreate([
            'enrollment_ID' => $enrollment->enrollment_ID, 'curr_subj_ID' => $assignment->curr_subj_ID,
        ]);
        foreach (['shs_sem1_term_1', 'shs_sem1_term_3'] as $period) {
            StudentSubjectGrade::query()->create([
                'student_subject_ID' => $studentSubject->student_subject_ID,
                'assignment_ID' => $assignment->assignment_ID,
                'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period),
                'numeric_grade' => 90, 'status' => $status, 'posted_by' => $teacher->staff_id,
            ]);
        }
    }

    $response = $this->actingAs($registrar)->get(route('registrar.class-subjects.index'));
    $response->assertOk()->assertSee('Subjects submitted')->assertSee('Total subjects');
    expect($response->viewData('sectionProgress')[$section->section_ID])->toBe(['submitted' => 0, 'expected' => 1]);

    StudentSubjectGrade::query()->where('assignment_ID', $assignment->assignment_ID)
        ->update(['grade_status_ID' => GradeStatus::idFor(GradeStatus::APPROVED)]);
    foreach (StudentSubject::query()->where('curr_subj_ID', $assignment->curr_subj_ID)->get() as $studentSubject) {
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $studentSubject->student_subject_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_2'),
            'numeric_grade' => 90, 'status' => 'submitted', 'posted_by' => $teacher->staff_id,
        ]);
    }
    $complete = $this->get(route('registrar.class-subjects.index'))->assertOk();
    expect($complete->viewData('sectionProgress')[$section->section_ID])->toBe(['submitted' => 1, 'expected' => 1]);

    $this->postJson(route('registrar.class-subjects.unlock-term', $assignment), [
        'grading_period' => 'shs_sem1_term_1',
    ])->assertOk();
    $this->get(route('registrar.classes.subjects', $section))->assertOk()
        ->assertSee('data-submitted="0"', false)->assertSee('data-expected="1"', false);
});


test('class subject filters default to active year term and semester', function () {
    ['registrar' => $registrar, 'academicYear' => $year] = createRegistrarClassSubjectFixtures();
    $response = $this->actingAs($registrar)->get(route('registrar.class-subjects.index', ['grade_level' => 'grade_11']));
    $response->assertOk()->assertSee('class-subject-filters')->assertSee('Filter')->assertSee('Clear');
    $filters = $response->viewData('filters');
    expect($filters['SY_ID'])->toBe((string) $year->SY_ID)
        ->and($filters['semester'])->toBe(GradingTermSetting::current()->seniorHighSemester())
        ->and((int) $filters['term_id'])->toBe((int) $response->viewData('periods')['termDefaults']['senior_high'])
        ->and($response->viewData('periods')['showSemesterFilter'])->toBeTrue()
        ->and($response->viewData('showPeriodColumns'))->toBeTrue();
});

test('junior high filters hide senior high controls and period columns', function () {
    ['registrar' => $registrar, 'section' => $section] = createRegistrarClassSubjectFixtures();
    $junior = $section->replicate();
    $junior->name = 'Junior Section';
    $junior->grade_ID = GradeLevel::idForValue('grade_7');
    $junior->save();
    $response = $this->actingAs($registrar)->get(route('registrar.class-subjects.index', [
        'grade_level' => 'grade_7', 'semester' => 'second', 'cluster_ID' => 999,
    ]));
    $response->assertOk()->assertSee('Junior Section');
    expect($response->viewData('periods')['showSemesterFilter'])->toBeFalse()
        ->and($response->viewData('showPeriodColumns'))->toBeFalse()
        ->and($response->viewData('filters')['cluster_ID'])->toBe('')
        ->and($response->viewData('filters')['semester'])->toBeNull();
});

test('semester and term selection scope subject totals and modal contents', function () {
    ['registrar' => $registrar, 'section' => $section, 'assignment' => $assignment, 'subject' => $subject] = createRegistrarClassSubjectFixtures();
    $this->actingAs($registrar);
    $assignment->curriculumSubject->curriculumGradeLevel->update([
        'semester_ID' => \App\Models\GradingSemester::idFor('first'),
    ]);
    $second = ['grade_level' => 'grade_11', 'semester' => 'second', 'term_id' => ''];
    $response = $this->get(route('registrar.class-subjects.index', $second))->assertOk();
    expect($response->viewData('sectionProgress')[$section->section_ID] ?? ['submitted' => 0, 'expected' => 0])
        ->toBe(['submitted' => 0, 'expected' => 0]);
    $this->get(route('registrar.classes.subjects', ['section' => $section] + $second))
        ->assertOk()->assertSee('No subjects assigned for this section.')->assertDontSee($subject->code);

    $first = ['grade_level' => 'grade_11', 'semester' => 'first', 'term_id' => StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_3')];
    $response = $this->get(route('registrar.class-subjects.index', $first))->assertOk();
    expect($response->viewData('sectionProgress')[$section->section_ID]['expected'])->toBe(1);
    $terms = $response->viewData('termsByAssignment')[$assignment->assignment_ID];
    expect($terms)->toHaveCount(1)->and((int) $terms[0]['term_ID'])->toBe($first['term_id']);
    $this->get(route('registrar.classes.subjects', ['section' => $section] + $first))
        ->assertOk()->assertSee($subject->code)->assertSee('Not yet available');
});

test('class subjects default to active school year but allow an explicit year', function () {
    ['registrar' => $registrar, 'section' => $section] = createRegistrarClassSubjectFixtures();
    $oldYear = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $oldSection = $section->replicate();
    $oldSection->name = 'Previous Year Section';
    $oldSection->SY_ID = $oldYear->SY_ID;
    $oldSection->save();
    $this->actingAs($registrar)->get(route('registrar.class-subjects.index'))->assertOk()->assertDontSee($oldSection->name);
    $this->get(route('registrar.class-subjects.index', ['SY_ID' => $oldYear->SY_ID]))->assertOk()->assertSee($oldSection->name);
});


test('subject modal shows active term counts and all term actions despite the selected filter', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'assignment' => $assignment, 'academicYear' => $year, 'gradeLevel' => $level] = createRegistrarClassSubjectFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 2);
    $assignment->curriculumSubject->curriculumGradeLevel->update([
        'semester_ID' => \App\Models\GradingSemester::idFor('first'),
    ]);
    $periodFilters = \App\Support\GradeRecordPeriodFilters::resolve(\Illuminate\Http\Request::create('/'), $level);
    $activeId = (int) $periodFilters['termDefaults']['senior_high'];
    $otherId = StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_3');
    foreach (['submitted', 'approved', 'draft'] as $index => $status) {
        $student = Student::query()->create([
            'lrn' => (string) (223456780000 + $index), 'first_name' => 'Modal', 'last_name' => 'Learner'.$index, 'status' => 'active',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID,
            'grade_ID' => $level->grade_ID, 'learner_type' => 'regular', 'enrollment_status' => 'enrolled',
        ]);
        $roster = StudentSubject::query()->firstOrCreate([
            'enrollment_ID' => $enrollment->enrollment_ID, 'curr_subj_ID' => $assignment->curr_subj_ID,
        ]);
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => $activeId, 'numeric_grade' => 91, 'status' => $status, 'posted_by' => $teacher->staff_id,
        ]);
    }
    $params = ['grade_level' => 'grade_11', 'semester' => 'first', 'term_id' => $otherId];
    $response = $this->actingAs($registrar)->get(route('registrar.class-subjects.index', $params))->assertOk();
    $row = $response->viewData('sections')->first()->teacherSubjectAssignments->first();
    expect($row->subject_students_count)->toBe(3)->and($row->active_submitted_count)->toBe(2);
    expect($response->viewData('allTermsByAssignment')[$assignment->assignment_ID])->toHaveCount(3);
    expect($response->viewData('termsByAssignment')[$assignment->assignment_ID])->toHaveCount(1);

    $this->get(route('registrar.classes.subjects', ['section' => $section] + $params))->assertOk()
        ->assertSee('Total students')->assertSee('Grades submitted')->assertSee('Partially submitted')
        ->assertSee('View grades')->assertSee('Edit term status');
    $periodKey = 'shs_sem1_'.GradingTerm::findOrFail($activeId)->key;
    $this->get(route('registrar.class-subjects.grade-records', ['assignment' => $assignment, 'grading_period' => $periodKey]))
        ->assertOk()->assertSee('Learner0')->assertSee('Learner1')->assertDontSee('Learner2')->assertSee('91.00');
});

test('subject grade records enforce registrar access and validate the selected term', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'assignment' => $assignment] = createRegistrarClassSubjectFixtures();
    $url = route('registrar.class-subjects.grade-records', $assignment);
    $this->actingAs($teacher)->getJson($url.'?grading_period=shs_sem1_term_1')->assertForbidden();
    $this->actingAs($registrar)->getJson($url.'?grading_period=invalid')->assertUnprocessable();
    $this->get($url.'?grading_period=shs_sem1_term_1')->assertOk()->assertSee('No submitted grades for active students in this term.');
});


test('registrar unlock permits teacher editing only for the selected assignment and term while school input is closed', function (bool $seniorHigh) {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'assignment' => $assignment, 'academicYear' => $year] = createRegistrarClassSubjectFixtures();
    $gradeId = GradeLevel::idForValue($seniorHigh ? 'grade_11' : 'grade_7');
    $section->update(['grade_ID' => $gradeId]);
    $curriculum = $assignment->curriculumSubject->curriculumGradeLevel;
    $curriculum->update([
        'grade_ID' => $gradeId,
        'semester_ID' => \App\Models\GradingSemester::idFor($seniorHigh ? 'first' : 'full_year'),
    ]);
    if ($seniorHigh) {
        GradingTermSetting::current()->setSeniorHighPeriod('second', 1);
    } else {
        GradingTerm::closeAllJuniorHighTerms();
    }
    $settingsBefore = GradingTermSetting::current()->getAttributes();
    $term1 = $seniorHigh ? 'shs_sem1_term_1' : 'term_1';
    $term2 = $seniorHigh ? 'shs_sem1_term_2' : 'term_2';

    $otherSubject = Subject::query()->create(['code' => 'MATH', 'title' => 'Mathematics', 'type' => 'core', 'status' => 'active']);
    $otherCurriculumSubject = $assignment->curriculumSubject->replicate();
    $otherCurriculumSubject->subject_ID = $otherSubject->subject_ID;
    $otherCurriculumSubject->save();
    $otherAssignment = $assignment->replicate();
    $otherAssignment->curr_subj_ID = $otherCurriculumSubject->curr_subj_ID;
    $otherAssignment->save();
    $otherSection = $section->replicate();
    $otherSection->name = 'Other section';
    $otherSection->save();
    $otherSectionAssignment = $assignment->replicate();
    $otherSectionAssignment->section_ID = $otherSection->section_ID;
    $otherSectionAssignment->save();

    $grades = [];
    $enrollments = [];
    foreach ([$section, $otherSection] as $index => $class) {
        $student = Student::query()->create(['lrn' => (string) (323456780000 + $index), 'first_name' => 'Unlock', 'last_name' => 'Student'.$index, 'status' => 'active']);
        $enrollments[$index] = Enrollment::query()->create([
            'student_ID' => $student->id, 'section_ID' => $class->section_ID, 'SY_ID' => $year->SY_ID,
            'grade_ID' => $gradeId, 'curriculum_grade_level_ID' => $curriculum->curriculum_ID,
            'learner_type' => 'regular', 'enrollment_status' => 'enrolled',
        ]);
    }
    foreach ([[$assignment, 0, $term1], [$assignment, 0, $term2], [$otherAssignment, 0, $term1], [$otherSectionAssignment, 1, $term1]] as [$subjectAssignment, $studentIndex, $period]) {
        $roster = StudentSubject::query()->firstOrCreate(['enrollment_ID' => $enrollments[$studentIndex]->enrollment_ID, 'curr_subj_ID' => $subjectAssignment->curr_subj_ID]);
        $grades[] = StudentSubjectGrade::query()->create([
            'student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $subjectAssignment->assignment_ID,
            'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period), 'numeric_grade' => 80, 'status' => 'approved',
            'posted_by' => $teacher->staff_id, 'submitted_at' => now(), 'reviewed_by' => $registrar->staff_id, 'reviewed_at' => now(),
        ]);
    }
    $this->actingAs($registrar)->postJson(route('registrar.class-subjects.unlock-term', $assignment), ['grading_period' => $term1])->assertOk();
    expect($grades[0]->fresh()->status)->toBe('draft')
        ->and($grades[0]->fresh()->reviewed_by)->toBeNull()
        ->and($grades[0]->fresh()->submitted_at)->toBeNull();
    foreach (array_slice($grades, 1) as $grade) expect($grade->fresh()->status)->toBe('approved');
    expect(GradingTermSetting::current()->getAttributes())->toBe($settingsBefore);
    expect(AssignmentGradeTermUnlocker::unlockedPeriodKeysFor($otherAssignment->assignment_ID))->toBe([]);
    expect(AssignmentGradeTermUnlocker::unlockedPeriodKeysFor($otherSectionAssignment->assignment_ID))->toBe([]);

    $page = $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment))->assertOk();
    expect(array_column($page->viewData('inputPeriods'), 'key'))->toContain($term1)->not->toContain($term2);
    expect($page->viewData('canEditCurrentTerm'))->toBeTrue();
    $this->post(route('teacher.sections.grades.store', $assignment), ['grades' => [
        $enrollments[0]->enrollment_ID => [$term1 => ['grade' => 91], $term2 => ['grade' => 99]],
    ]])->assertSessionHasNoErrors();
    expect((float) $grades[0]->fresh()->numeric_grade)->toBe(91.0);
    expect((float) $grades[1]->fresh()->numeric_grade)->toBe(80.0);
    $this->post(route('teacher.sections.grades.store', $otherAssignment), ['grades' => [
        $enrollments[0]->enrollment_ID => [$term1 => ['grade' => 99]],
    ]])->assertSessionHasErrors('grades');
    expect((float) $grades[2]->fresh()->numeric_grade)->toBe(80.0);
    $this->post(route('teacher.sections.grades.submit', $assignment))->assertSessionHas('status', 'Grades submitted successfully.');
    expect($grades[0]->fresh()->status)->toBe('submitted');
    $this->post(route('teacher.sections.grades.store', $assignment), ['grades' => [
        $enrollments[0]->enrollment_ID => [$term1 => ['grade' => 95]],
    ]]);
    expect((float) $grades[0]->fresh()->numeric_grade)->toBe(91.0);
    foreach (array_slice($grades, 1) as $grade) expect($grade->fresh()->status)->toBe('approved');
    if ($seniorHigh) {
        GradingTermSetting::current()->setSeniorHighPeriod('first', 2);
        GradingTerm::query()->where('term_ID', StudentSubjectGrade::termIdForPeriodKey($term2))->update([
            'senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId(),
        ]);
        $grades[0]->delete();
        $emptyTermPage = $this->get(route('teacher.sections.show', $assignment))->assertOk();
        expect($emptyTermPage->viewData('canEditCurrentTerm'))->toBeTrue();
        expect(array_column($emptyTermPage->viewData('inputPeriods'), 'key'))->toContain($term1, $term2);
        $this->post(route('teacher.sections.grades.store', $assignment), ['grades' => [
            $enrollments[0]->enrollment_ID => [$term1 => ['grade' => 92]],
        ]])->assertSessionHasNoErrors();
        expect((float) $assignment->grades()->forPeriodKey($term1)->value('numeric_grade'))->toBe(92.0);
        expect($grades[1]->fresh()->status)->toBe('approved');
    }
})->with([true, false]);
