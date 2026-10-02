<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

function createTeacherDashboardFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.grades',
        'password' => Hash::make('password'),
        'first_name' => 'Grade',
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
        'name' => 'Newton',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'cluster_ID' => $cluster->cluster_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'semester' => 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('teacher', 'assignment', 'enrollment');
}

test('teacher dashboard connects subject and advisory work without double counting learners', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherDashboardFixtures();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('My subject grades')
        ->assertSee('Grading checklist')
        ->assertSee('English 11')
        ->assertSee('No grades recorded')
        ->assertSee(route('teacher.sections.show', $assignment), false)
        ->assertSee(route('teacher.advisory.attendance', $assignment->section), false)
        ->assertSee(route('teacher.advisory.observed-values', $assignment->section), false)
        ->assertSee(route('teacher.advisory.promotions.index', $assignment->section), false)
        ->assertViewHas('totalStudents', 1)
        ->assertViewHas('ungradedAssignments', fn ($items) => $items->count() === 1);
});

test('teacher dashboard aggregates grade records across terms and highlights returned work', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherDashboardFixtures();
    $roster = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'curr_subj_ID' => $assignment->curr_subj_ID,
    ]);
    foreach (['rejected', 'draft', 'submitted', 'released'] as $index => $status) {
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $roster->student_subject_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => GradingTerm::query()->where('key', 'term_'.($index + 1))->value('term_ID'),
            'numeric_grade' => 85,
            'grade_status_ID' => GradeStatus::idFor($status),
            'posted_by' => $teacher->staff_id,
        ]);
    }

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertOk()->assertSee('Released grades')->assertSee('1 submitted')
        ->assertSee('No ungraded subjects')->assertDontSee('English 11')
        ->assertViewHas('gradeTotals', fn ($totals) => $totals['rejected'] === 1 && $totals['draft'] === 1 && $totals['submitted'] === 1 && $totals['released'] === 1)
        ->assertViewHas('returnedAssignments', fn ($items) => $items->count() === 1)
        ->assertViewHas('ungradedAssignments', fn ($items) => $items->isEmpty());
});

test('teacher dashboard excludes another teachers assignments and learners', function () {
    ['teacher' => $teacher] = createTeacherDashboardFixtures();
    $otherTeacher = $teacher->replicate();
    $otherTeacher->username = 'other.teacher';
    $otherTeacher->save();

    $this->actingAs($otherTeacher)->get(route('teacher.dashboard'))
        ->assertOk()->assertDontSee('English 11')->assertDontSee('Newton')
        ->assertSee('No subject assignments yet')->assertSee('No advisory class assigned')
        ->assertViewHas('totalStudents', 0)
        ->assertViewHas('assignments', fn ($items) => $items->isEmpty())
        ->assertViewHas('advisorySections', fn ($items) => $items->isEmpty());
});

test('teacher dashboard excludes historical work and handles a missing active year', function () {
    ['teacher' => $teacher] = createTeacherDashboardFixtures();
    AcademicYear::query()->update(['status' => false]);

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertOk()->assertSee('No active school year is set')
        ->assertDontSee('English 11')->assertDontSee('Newton')
        ->assertViewHas('totalStudents', 0);

    AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => true,
    ]);
    $this->get(route('teacher.dashboard'))->assertOk()
        ->assertSee('2027-2028')->assertDontSee('English 11')->assertDontSee('Newton')
        ->assertViewHas('totalStudents', 0)
        ->assertViewHas('assignments', fn ($items) => $items->isEmpty());
});

test('dashboard shows only five ungraded subjects while preserving full counts', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherDashboardFixtures();
    for ($index = 2; $index <= 7; $index++) {
        $subject = $assignment->curriculumSubject->subject->replicate();
        $subject->code = 'SUB'.$index;
        $subject->title = 'Dashboard subject '.$index;
        $subject->save();
        $curriculumSubject = $assignment->curriculumSubject->replicate();
        $curriculumSubject->subject_ID = $subject->subject_ID;
        $curriculumSubject->save();
        $extraAssignment = $assignment->replicate();
        $extraAssignment->curr_subj_ID = $curriculumSubject->curr_subj_ID;
        $extraAssignment->save();
    }

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('English 11')->assertSee('Dashboard subject 5')
        ->assertDontSee('Dashboard subject 6')->assertDontSee('Dashboard subject 7')
        ->assertDontSee('Your teaching workspace')->assertDontSee('Welcome,')
        ->assertDontSee('Start with your classroom.')
        ->assertSeeInOrder(['Subject assignments', 'Advisory classes', 'Active learners', 'Returned grades', 'Advisory quick access', 'My subject grades'])
        ->assertSee('teacher-content w-full max-w-none', false)
        ->assertViewHas('assignments', fn ($items) => $items->count() === 7)
        ->assertViewHas('ungradedAssignments', fn ($items) => $items->count() === 7);
});
