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
