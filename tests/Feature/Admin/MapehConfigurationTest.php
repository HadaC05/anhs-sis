<?php

use App\Models\AcademicYear;
use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\DataStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\MapehConfiguration;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\MapehGrades;
use App\Support\MapehSetup;
use App\Support\Sf9ReportCardBuilder;

function mapehFixtures(string $role = 'admin'): array
{
    $manager = Staff::create(['role_id' => Role::firstOrCreate(['role_name' => $role])->id, 'username' => 'mapeh.manager', 'password' => 'password', 'first_name' => 'Mapeh', 'last_name' => 'Manager', 'status' => 'active']);
    $teacher = Staff::create(['role_id' => Role::firstOrCreate(['role_name' => 'teacher'])->id, 'username' => 'mapeh.teacher', 'password' => 'password', 'first_name' => 'Music', 'last_name' => 'Teacher', 'status' => 'active']);
    $year = AcademicYear::create(['school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => true]);
    $curriculum = Curriculum::create(['name' => 'Grade 7 MAPEH Test', 'curricula_ID' => Curricula::first()->curricula_ID, 'grade_ID' => GradeLevel::idForValue('grade_7'), 'semester_ID' => GradingSemester::idFor(GradingSemester::FULL_YEAR), 'data_status_ID' => DataStatus::where('key', 'active')->value('data_status_ID')]);
    $subject = Subject::create(['code' => 'MAPEH7', 'title' => 'MAPEH 7', 'school_level' => 'Junior High School', 'type' => 'core', 'status' => 'active']);
    $parent = CurriculumSubject::create(['curriculum_grade_level_ID' => $curriculum->curriculum_ID, 'subject_ID' => $subject->subject_ID]);
    $section = Section::create(['name' => '7-A', 'grade_ID' => $curriculum->grade_ID, 'curriculum_grade_level_ID' => $curriculum->curriculum_ID, 'SY_ID' => $year->SY_ID, 'staff_ID' => $teacher->staff_id, 'capacity' => 50]);
    $student = Student::create(['lrn' => '123456789012', 'first_name' => 'Ana', 'last_name' => 'Santos', 'sex' => 'female', 'status' => 'active']);
    $enrollment = Enrollment::create(['student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID, 'enrollment_status' => 'enrolled', 'learner_type' => 'regular']);
    $assignment = TeacherSubjectAssignment::create(['section_ID' => $section->section_ID, 'curr_subj_ID' => $parent->curr_subj_ID, 'staff_ID' => $teacher->staff_id, 'SY_ID' => $year->SY_ID]);
    $data = ['SY_ID' => $year->SY_ID, 'parent_curr_subj_ID' => $parent->curr_subj_ID, 'mode' => 'four'];

    return compact('manager', 'teacher', 'year', 'curriculum', 'parent', 'section', 'student', 'enrollment', 'assignment', 'data');
}

function mapehComponentGrades(array $f, array $values, string $status = 'approved', string $term = 'term_1'): void
{
    $config = MapehConfiguration::where('SY_ID', $f['year']->SY_ID)->firstOrFail();
    foreach ($config->components as $index => $component) {
        if (! array_key_exists($index, $values)) {
            continue;
        }
        $assignment = TeacherSubjectAssignment::firstOrCreate(['section_ID' => $f['section']->section_ID, 'curr_subj_ID' => $component->curr_subj_ID], ['staff_ID' => $f['teacher']->staff_id, 'SY_ID' => $f['year']->SY_ID]);
        $roster = StudentSubject::where('enrollment_ID', $f['enrollment']->enrollment_ID)->where('curr_subj_ID', $component->curr_subj_ID)->firstOrFail();
        StudentSubjectGrade::updateOrCreate(['student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID, 'term_ID' => StudentSubjectGrade::termIdForPeriodKey($term)], ['numeric_grade' => $values[$index], 'status' => $status, 'posted_by' => $f['teacher']->staff_id]);
    }
}

function mapehDisplay(array $f, bool $releasedOnly = false): array
{
    $assignments = TeacherSubjectAssignment::with('curriculumSubject.subject')->where('section_ID', $f['section']->section_ID)->get();
    $grades = StudentSubjectGrade::get()->groupBy('assignment_ID')->map(fn ($items) => $items->keyBy('grading_period'));
    $display = MapehGrades::assignments($f['section']->fresh(), $assignments);

    return [$display, MapehGrades::grades($display, $grades, ['term_1'], $releasedOnly)];
}

test('admin and principal configure MAPEH components with roster backfill', function (string $role, string $mode, int $count) {
    $f = mapehFixtures($role);
    $this->actingAs($f['manager'])->get(route($role.'.curriculum-config.mapeh.edit', $f['curriculum']))->assertOk()->assertSee('Save MAPEH Configuration');
    $this->post(route($role.'.curriculum-config.mapeh.store', $f['curriculum']), array_replace($f['data'], ['mode' => $mode]))->assertSessionHasNoErrors()->assertRedirect();
    $config = MapehConfiguration::sole();
    expect($config->components)->toHaveCount($count)->and($config->mode)->toBe($mode)
        ->and(StudentSubject::where('enrollment_ID', $f['enrollment']->enrollment_ID)->count())->toBe($count + 1);
    $this->get(route($role.'.curriculum-config.mapeh.edit', $f['curriculum']))->assertOk()->assertSee('Component teachers by class')->assertSee('Not assigned');
    // Reposting the same configuration never duplicates subjects or grades.
    $this->post(route($role.'.curriculum-config.mapeh.store', $f['curriculum']), array_replace($f['data'], ['mode' => $mode]))->assertSessionHasNoErrors();
    expect(MapehConfiguration::count())->toBe(1)->and(StudentSubjectGrade::count())->toBe(0);
})->with([['admin', 'four', 4], ['principal', 'paired', 2]]);

test('existing subjects can be linked without creating duplicate catalog entries', function () {
    $f = mapehFixtures();
    $selected = [];
    foreach (MapehConfiguration::labels('four') as $key => $label) {
        $selected[$key] = Subject::create(['code' => strtoupper($key), 'title' => $label, 'school_level' => 'Junior High School', 'type' => 'core', 'status' => 'active'])->subject_ID;
    }
    $before = Subject::count();
    MapehSetup::save($f['curriculum'], $f['data'] + ['components' => $selected]);
    expect(Subject::count())->toBe($before)->and(MapehConfiguration::sole()->components)->toHaveCount(4);
});

test('MAPEH requires every approved component and counts zero as a real grade', function (array $values, string $status, ?int $expected) {
    $f = mapehFixtures();
    MapehSetup::save($f['curriculum'], $f['data']);
    mapehComponentGrades($f, $values, $status);
    [$assignments, $grades] = mapehDisplay($f);
    $parent = $assignments->firstWhere('computed_mapeh', true);
    expect($grades[$parent->assignment_ID]['term_1']->numeric_grade)->toBe($expected);
})->with([
    [[88, 90, 86, 92], 'approved', 89],
    [[88, 90, 86], 'approved', null],
    [[88, 90, 86, 92], 'draft', null],
    [[88, 90, 86, 92], 'submitted', null],
    [[0, 80, 80, 80], 'approved', 60],
]);

test('SF9 combines paired components once and shows their correct labels', function () {
    $f = mapehFixtures();
    MapehSetup::save($f['curriculum'], array_replace($f['data'], ['mode' => 'paired']));
    mapehComponentGrades($f, [88, 92]);
    $assignments = TeacherSubjectAssignment::with('curriculumSubject.subject')->get();
    $grades = StudentSubjectGrade::get()->groupBy('assignment_ID')->map(fn ($items) => $items->keyBy('grading_period'));
    $card = Sf9ReportCardBuilder::buildCard($f['enrollment'], $f['section']->fresh(), $assignments, $grades, collect(), [['key' => 'term_1', 'label' => 'Term 1']]);
    $rows = collect($card['subjects'])->keyBy('label');
    expect($rows['MAPEH']['quarters']['term_1'])->toBe(90.0)->and($rows['MAPEH']['final'])->toBe(90.0)
        ->and($rows['Music & Arts']['quarters']['term_1'])->toBe(88.0)
        ->and($rows['Physical Education & Health']['quarters']['term_1'])->toBe(92.0)
        ->and($card['general_average'])->toBe(90);
});

test('legacy MAPEH grades are preserved until component entry begins for that term', function () {
    $f = mapehFixtures();
    $legacy = StudentSubjectGrade::create(['student_subject_ID' => StudentSubject::sole()->student_subject_ID, 'assignment_ID' => $f['assignment']->assignment_ID, 'term_ID' => StudentSubjectGrade::termIdForPeriodKey('term_1'), 'numeric_grade' => 91, 'status' => 'released', 'posted_by' => $f['teacher']->staff_id]);
    MapehSetup::save($f['curriculum'], $f['data']);
    [$assignments, $grades] = mapehDisplay($f, true);
    expect($grades[$assignments->firstWhere('computed_mapeh', true)->assignment_ID]['term_1']->numeric_grade)->toEqual(91);
    mapehComponentGrades($f, [88], 'draft');
    [$assignments, $grades] = mapehDisplay($f, true);
    expect($grades[$assignments->firstWhere('computed_mapeh', true)->assignment_ID]['term_1']->numeric_grade)->toBeNull()
        ->and($legacy->fresh()->numeric_grade)->toEqual(91);
});

test('teachers cannot configure MAPEH or manually overwrite its calculated parent', function () {
    $f = mapehFixtures();
    $this->actingAs($f['teacher'])->post(route('admin.curriculum-config.mapeh.store', $f['curriculum']), $f['data'])->assertForbidden();
    MapehSetup::save($f['curriculum'], $f['data']);
    $this->post(route('teacher.sections.grades.store', $f['assignment']), ['grades' => [$f['enrollment']->enrollment_ID => ['term_1' => ['grade' => 100]]]])->assertSessionHasErrors('grades');
    expect(StudentSubjectGrade::count())->toBe(0);
    $this->actingAs($f['manager'])->post(route('admin.teacher-assignments.store'), ['section_ID' => $f['section']->section_ID, 'curr_subj_ID' => $f['parent']->curr_subj_ID, 'staff_ID' => $f['teacher']->staff_id])->assertSessionHasErrors('curr_subj_ID');
});

test('configuration is isolated by school year and existing mappings cannot be replaced', function () {
    $f = mapehFixtures();
    MapehSetup::save($f['curriculum'], $f['data']);
    $this->actingAs($f['manager'])->post(route('admin.curriculum-config.mapeh.store', $f['curriculum']), array_replace($f['data'], ['mode' => 'paired']))->assertSessionHasErrors('mode');
    $year = AcademicYear::create(['school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => false]);
    MapehSetup::save($f['curriculum'], array_replace($f['data'], ['SY_ID' => $year->SY_ID, 'mode' => 'paired']));
    expect(MapehConfiguration::forSection($f['section']->fresh())->mode)->toBe('four')->and(MapehConfiguration::count())->toBe(2);
    $section = $f['section']->replicate();
    $section->SY_ID = $year->SY_ID;
    $section->save();
    $enrollment = Enrollment::create(['student_ID' => $f['student']->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID, 'enrollment_status' => 'enrolled', 'learner_type' => 'regular']);
    expect($enrollment->studentSubjects)->toHaveCount(2);
});

test('component teachers can import and save their own grades without editing another component', function () {
    $f = mapehFixtures();
    $config = MapehSetup::save($f['curriculum'], $f['data']);
    $componentAssignments = [];
    foreach ($config->components as $index => $component) {
        $teacher = $f['teacher']->replicate();
        $teacher->username = 'component.teacher.'.$index;
        $teacher->save();
        $this->actingAs($f['manager'])->post(route('admin.teacher-assignments.store'), ['section_ID' => $f['section']->section_ID, 'curr_subj_ID' => $component->curr_subj_ID, 'staff_ID' => $teacher->staff_id])->assertSessionHasNoErrors();
        $assignment = TeacherSubjectAssignment::where('curr_subj_ID', $component->curr_subj_ID)->sole();
        $componentAssignments[] = $assignment;
        $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
            'period' => 'term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
        ])->assertOk()->assertJsonPath('grades.0.grade', 87);
        $this->post(route('teacher.sections.grades.store', $assignment), ['grades' => [$f['enrollment']->enrollment_ID => ['term_1' => ['grade' => 87 + $index]]]])->assertSessionHasNoErrors();
    }
    expect(StudentSubjectGrade::count())->toBe(4)->and(StudentSubjectGrade::where('assignment_ID', $f['assignment']->assignment_ID)->count())->toBe(0);
    $this->post(route('teacher.sections.grades.store', $componentAssignments[0]), ['grades' => [$f['enrollment']->enrollment_ID => ['term_1' => ['grade' => 100]]]])->assertForbidden();
});

test('student grades advisory SF10 and SF5 use the combined MAPEH grade', function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureStudent::class);
    $f = mapehFixtures();
    $f['student']->update(['username' => 'mapeh.student', 'password' => 'password', 'change_password' => false]);
    MapehSetup::save($f['curriculum'], $f['data']);
    foreach (GradingTerm::configuredPeriods() as $period) {
        mapehComponentGrades($f, [88, 90, 86, 92], 'released', $period['key']);
    }
    $math = Subject::create(['code' => 'MATH', 'title' => 'Mathematics', 'school_level' => 'Junior High School', 'type' => 'core', 'status' => 'active']);
    $offering = CurriculumSubject::create(['curriculum_grade_level_ID' => $f['curriculum']->curriculum_ID, 'subject_ID' => $math->subject_ID]);
    $mathAssignment = TeacherSubjectAssignment::create(['section_ID' => $f['section']->section_ID, 'curr_subj_ID' => $offering->curr_subj_ID, 'staff_ID' => $f['teacher']->staff_id, 'SY_ID' => $f['year']->SY_ID]);
    $roster = StudentSubject::create(['enrollment_ID' => $f['enrollment']->enrollment_ID, 'curr_subj_ID' => $offering->curr_subj_ID]);
    foreach (GradingTerm::configuredPeriods() as $period) {
        StudentSubjectGrade::create(['student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $mathAssignment->assignment_ID, 'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period['key']), 'numeric_grade' => 75, 'status' => 'released', 'posted_by' => $f['teacher']->staff_id]);
    }
    $this->actingAs($f['student'])->get(route('student.grades'))->assertOk()->assertSee('MAPEH')->assertSee('89');
    $this->get(route('student.grades', ['session' => $f['enrollment']->enrollment_ID, 'report' => 1]))->assertOk()->assertSee('89')->assertSee('82');
    $this->actingAs($f['teacher'])->get(route('teacher.advisory.show', $f['section']))->assertOk()->assertSee('89')->assertSee('82');
    $assignments = TeacherSubjectAssignment::with('curriculumSubject.subject')->get();
    $record = \App\Support\LearnerPermanentRecordBuilder::buildScholasticRecord($f['enrollment'], $f['section']->fresh(), $assignments, StudentSubjectGrade::get(), GradingTerm::configuredPeriods());
    expect(collect($record['subjects'])->firstWhere('label', 'MAPEH')['final'])->toBe(89.0)
        ->and($record['general_average'])->toBe(82.0);
    $sf5 = \App\Support\Sf5ReportBuilder::rows($f['section']->fresh());
    expect($sf5[0]['average'])->toBe(82)->and($sf5[0]['action'])->toBe('PROMOTED');
    StudentSubjectGrade::query()->first()->update(['status' => 'approved']);
    $sf5 = \App\Support\Sf5ReportBuilder::rows($f['section']->fresh());
    expect($sf5[0]['average'])->toBeNull();
});

test('MAPEH remains hidden from students until every component is released', function () {
    $f = mapehFixtures();
    MapehSetup::save($f['curriculum'], $f['data']);
    mapehComponentGrades($f, [88, 90, 86, 92], 'approved');
    [$assignments, $grades] = mapehDisplay($f, true);
    expect($grades[$assignments->firstWhere('computed_mapeh', true)->assignment_ID]['term_1']->numeric_grade)->toBeNull();
    mapehComponentGrades($f, [88, 90, 86, 92], 'released');
    [$assignments, $grades] = mapehDisplay($f, true);
    expect($grades[$assignments->firstWhere('computed_mapeh', true)->assignment_ID]['term_1']->numeric_grade)->toBe(89);
});

test('unassigned MAPEH components keep class submission progress incomplete', function () {
    $f = mapehFixtures();
    MapehSetup::save($f['curriculum'], $f['data']);
    $progress = \App\Support\SectionGradeSubmissionProgress::forAssignments(collect([$f['assignment']]), []);
    expect($progress[$f['section']->section_ID])->toBe(['submitted' => 0, 'expected' => 4]);
    mapehComponentGrades($f, [88, 90, 86], 'submitted');
    $assignments = TeacherSubjectAssignment::get();
    $terms = $assignments->mapWithKeys(fn ($assignment) => [$assignment->assignment_ID => [['term_ID' => StudentSubjectGrade::termIdForPeriodKey('term_1')]]])->all();
    $progress = \App\Support\SectionGradeSubmissionProgress::forAssignments($assignments, $terms);
    expect($progress[$f['section']->section_ID])->toBe(['submitted' => 3, 'expected' => 4]);
});
