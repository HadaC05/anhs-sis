<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\Sf9ReportCardBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('maps senior high subjects onto the official learning areas and averages term grades', function () {
    $fixtures = createSf9BuilderFixtures();

    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'assignment_ID' => $fixtures['firstAssignment']->assignment_ID,
        'grading_period' => 'term_1',
        'numeric_grade' => 90,
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);
    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'assignment_ID' => $fixtures['firstAssignment']->assignment_ID,
        'grading_period' => 'term_2',
        'numeric_grade' => 88,
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);
    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'assignment_ID' => $fixtures['secondAssignment']->assignment_ID,
        'grading_period' => 'term_1',
        'numeric_grade' => 80,
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);
    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'assignment_ID' => $fixtures['secondAssignment']->assignment_ID,
        'grading_period' => 'term_2',
        'numeric_grade' => 82,
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);

    $grades = StudentSubjectGrade::query()
        ->where('enrollment_ID', $fixtures['enrollment']->enrollment_ID)
        ->get()
        ->groupBy('assignment_ID')
        ->map(fn ($assignmentGrades) => $assignmentGrades->keyBy('grading_period'));

    $card = Sf9ReportCardBuilder::buildCard(
        $fixtures['enrollment']->load(['student', 'cluster', 'preferredCourse']),
        $fixtures['section']->load(['academicYear', 'cluster', 'gradeLevel', 'adviser', 'curriculum']),
        collect([$fixtures['firstAssignment']->load('curriculumSubject.subject'), $fixtures['secondAssignment']->load('curriculumSubject.subject')]),
        $grades,
        collect(),
        [['key' => 'term_1', 'label' => 'Term 1'], ['key' => 'term_2', 'label' => 'Term 2']],
    );

    $rows = collect($card['subjects'])->keyBy('slot');

    expect($card['is_senior_high'])->toBeTrue()
        ->and($card['shs_track'])->toBe('ACADEMIC')
        ->and($card['signature_labels'])->toBe(['Term 1', 'Term 2', 'Term 3'])
        ->and($rows['effective_communication']['label'])->toBe('Effective Communication')
        ->and($rows['effective_communication']['terms']['term_1'])->toBe(90)
        ->and($rows['effective_communication']['terms']['term_2'])->toBe(88)
        ->and($rows['effective_communication']['final'])->toBe(89)
        ->and($rows['effective_communication_group']['final'])->toBe(89)
        ->and($rows['elective_1']['label'])->toBe('Pre-Calculus')
        ->and($rows['elective_1']['terms']['term_1'])->toBe(80)
        ->and($rows['elective_1']['final'])->toBe(81)
        ->and($card['general_average'])->toBe(85)
        ->and($card['general_remarks'])->toBe('Passed')
        ->and(collect($card['subjects'])->pluck('label'))->toContain('Core Subjects', 'Elective Subjects', 'Academic Elective 2')
        ->and($card['first_semester']['core'])->toBe([]);
});

it('labels techpro tracks and uses the techpro elective rows', function () {
    $fixtures = createSf9BuilderFixtures();
    $fixtures['enrollment']->cluster->update(['name' => 'TVL Track - ICT']);
    $fixtures['enrollment']->load(['student', 'cluster', 'preferredCourse']);

    $card = Sf9ReportCardBuilder::buildCard(
        $fixtures['enrollment'],
        $fixtures['section']->load(['academicYear', 'cluster', 'gradeLevel', 'adviser', 'curriculum']),
        collect([$fixtures['firstAssignment']->load('curriculumSubject.subject')]),
        collect(),
        collect(),
        [['key' => 'term_1', 'label' => 'Term 1']],
    );

    expect($card['shs_track'])->toBe('TECHPRO')
        ->and(collect($card['subjects'])->pluck('label')->all())->toContain('Academic Elective 1')
        ->and(collect($card['subjects'])->pluck('label')->all())->not->toContain('Academic Elective 2')
        ->and(collect($card['subjects'])->pluck('label')->all())->not->toContain('Academic Elective 3');
});

it('keeps junior high report cards on the official learning areas', function () {
    $fixtures = createSf9BuilderFixtures(isSeniorHigh: false);

    $card = Sf9ReportCardBuilder::buildCard(
        $fixtures['enrollment']->load(['student']),
        $fixtures['section']->load(['academicYear', 'cluster', 'gradeLevel', 'adviser', 'curriculum']),
        collect([$fixtures['firstAssignment']->load('curriculumSubject.subject')]),
        collect(),
        collect(),
        [['key' => 'term_1', 'label' => 'Term 1']],
    );

    expect($card['is_senior_high'])->toBeFalse()
        ->and(collect($card['subjects'])->pluck('label')->all())->toContain('Filipino', 'MAPEH', 'Music')
        ->and($card['first_semester']['core'])->toBe([])
        ->and($card['signature_labels'][0])->toBe('1st Term');
});

it('maps grade-numbered TLE and EPP titles to the official SF9 learning area', function (string $title) {
    expect(Sf9ReportCardBuilder::juniorHighSubjectSlot($title))->toBe('epp_tle');
})->with([
    'TLE 7',
    'TLE 8',
    'TLE 9',
    'EPP 7',
]);

/**
 * @return array{teacher: Staff, section: Section, enrollment: Enrollment, firstAssignment: TeacherSubjectAssignment, secondAssignment: TeacherSubjectAssignment}
 */
function createSf9BuilderFixtures(bool $isSeniorHigh = true): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.sf9.builder',
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
    $cluster = Cluster::query()->create(['name' => 'Science, Technology, Engineering and Mathematics']);
    $course = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'STEM',
    ]);

    $coreSubject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => $isSeniorHigh ? 'ORALCOM' : 'ENG7',
        'title' => $isSeniorHigh ? 'Oral Communication' : 'English',
        'type' => 'core',
        'status' => 'active',
    ]);

    $coreCurriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $coreSubject->subject_ID,
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

    $firstAssignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $coreCurriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    $secondAssignment = $firstAssignment;

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

        $secondAssignment = TeacherSubjectAssignment::query()->create([
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

    return compact('teacher', 'section', 'enrollment', 'firstAssignment', 'secondAssignment');
}

it('includes additional senior high terms in report averages columns and observations', function () {
    $fixtures = createSf9BuilderFixtures();
    \App\Models\GradingTermSetting::current()->update(['senior_high_max_terms' => 4]);
    $assignment = $fixtures['firstAssignment'];
    $grades = collect([$assignment->assignment_ID => collect([
        'shs_sem1_term_1' => (object) ['numeric_grade' => 80],
        'shs_sem1_term_4' => (object) ['numeric_grade' => 100],
    ])]);
    $card = Sf9ReportCardBuilder::buildCard(
        $fixtures['enrollment']->load(['student', 'cluster', 'preferredCourse']),
        $fixtures['section']->load(['academicYear', 'cluster', 'gradeLevel', 'adviser', 'curriculum']),
        collect([$assignment->load('curriculumSubject.subject')]),
        $grades, collect(), \App\Models\GradingTerm::seniorHighPeriods(),
    );
    $row = collect($card['subjects'])->firstWhere('slot', 'effective_communication');
    expect($row['terms']['term_4'])->toBe(100)->and($row['final'])->toBe(90)
        ->and(count($card['senior_high_terms']))->toBe(4)
        ->and(count($card['observed_periods']))->toBe(8)
        ->and($card['signature_labels'])->toBe(['Term 1', 'Term 2', 'Term 3', 'Term 4']);
    $html = view('users.teacher.advisory.sf9-print', [
        'cards' => [$card], 'section' => $fixtures['section'], 'periods' => \App\Models\GradingTerm::seniorHighPeriods(),
    ])->render();
    expect($html)->toContain('colspan="4">TERM')
        ->toContain('class="sheet jhs-updated" data-school-level="senior-high"')
        ->toContain('class="shs-grid jhs-panels"')
        ->toContain('Track (SHS only):')
        ->toContain('ACADEMIC')
        ->toContain('Term 4')
        ->toContain('100');
});

it('rejects reducing senior high maximum when an excluded term has saved grades', function () {
    $fixtures = createSf9BuilderFixtures();
    $settings = \App\Models\GradingTermSetting::current();
    $settings->update(['senior_high_max_terms' => 4]);
    $assignment = $fixtures['firstAssignment'];
    $studentSubject = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'curr_subj_ID' => $assignment->curr_subj_ID,
    ]);
    $grade = StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => \App\Models\GradingTerm::query()->seniorHigh()->where('key', 'term_4')->value('term_ID'),
        'numeric_grade' => 91,
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);
    $admin = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'admin'])->id,
        'username' => 'admin.shs.records', 'password' => 'password',
        'first_name' => 'Admin', 'last_name' => 'School', 'status' => 'active',
    ]);
    $this->actingAs($admin)->put(route('admin.grading-term-config.senior-high.settings.update'), ['senior_high_max_terms' => 3])
        ->assertSessionHasErrors('senior_high_max_terms');
    expect($settings->fresh()->senior_high_max_terms)->toBe(4)
        ->and((int) $grade->fresh()->numeric_grade)->toBe(91);
});

it('separates existing senior high grade references without changing saved grades or period keys', function () {
    $fixtures = createSf9BuilderFixtures();
    $assignment = $fixtures['firstAssignment'];
    $juniorTerm = \App\Models\GradingTerm::query()->juniorHigh()->where('key', 'term_1')->firstOrFail();
    $studentSubject = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
        'curr_subj_ID' => $assignment->curr_subj_ID,
    ]);
    $grade = StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID, 'term_ID' => $juniorTerm->term_ID,
        'numeric_grade' => 93, 'posted_by' => $fixtures['teacher']->staff_id,
    ]);
    // Reconstruct the old shared catalog to exercise the upgrade with real grade data.
    \App\Models\GradingTermSetting::current()->update(['term_ID' => $juniorTerm->term_ID]);
    \App\Models\GradingTerm::query()->seniorHigh()->delete();
    \Illuminate\Support\Facades\DB::table('grading_terms')->update([
        'senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::activeId(),
    ]);
    \Illuminate\Support\Facades\Schema::table('grading_terms', function ($table) {
        $table->dropUnique(['school_level', 'key']);
        $table->dropColumn('school_level');
        $table->unique('key');
    });
    $before = $grade->fresh()->getAttributes();
    $migration = require database_path('migrations/2026_09_28_000003_separate_school_level_grading_terms.php');
    $migration->up();
    $seniorTerm = \App\Models\GradingTerm::query()->seniorHigh()->where('key', 'term_1')->firstOrFail();
    expect($grade->fresh()->term_ID)->toBe($seniorTerm->term_ID)
        ->and($grade->fresh()->getAttributes())->toBe(array_replace($before, ['term_ID' => $seniorTerm->term_ID]))
        ->and(\App\Models\GradingTermSetting::current()->term_ID)->toBe($seniorTerm->term_ID)
        ->and($juniorTerm->fresh()->senior_high_grading_period_status_ID)->toBeNull()
        ->and($seniorTerm->junior_high_grading_period_status_ID)->toBeNull()
        ->and(StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_1'))->toBe($seniorTerm->term_ID)
        ->and(StudentSubjectGrade::termIdForPeriodKey('term_1'))->toBe($juniorTerm->term_ID);
});
