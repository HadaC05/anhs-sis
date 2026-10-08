<?php

use App\Models\AcademicYear;
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
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $role = Role::query()->create(['role_name' => 'registrar']);
    $this->registrar = Staff::query()->create([
        'role_id' => $role->id, 'username' => 'registrar.dashboard',
        'password' => Hash::make('password'), 'first_name' => 'Reg',
        'last_name' => 'istrar', 'status' => 'active',
    ]);
    $this->actingAs($this->registrar);
});

function createRegistrarDashboardRecords(AcademicYear $year, int $index): TeacherSubjectAssignment
{
    $teacherRole = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id, 'username' => 'dashboard.teacher.'.$index,
        'password' => Hash::make('password'), 'first_name' => 'Teacher',
        'last_name' => 'Example', 'status' => 'active',
    ]);
    $curriculum = Curriculum::query()->create(['name' => 'Dashboard curriculum '.$index, 'status' => true]);
    $subject = Subject::query()->create(['code' => 'DASH'.$index, 'title' => 'Dashboard subject '.$index, 'type' => 'core', 'status' => 'active']);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID, 'subject_ID' => $subject->subject_ID,
        'grade_level' => 'grade_11', 'semester' => 'first',
    ]);
    $section = Section::query()->create([
        'name' => 'Dashboard section '.$index, 'grade_ID' => GradeLevel::idForValue('grade_11'),
        'SY_ID' => $year->SY_ID, 'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id, 'capacity' => 40,
    ]);
    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID, 'subject_ID' => $curriculumSubject->subject_ID,
        'staff_ID' => $teacher->staff_id, 'SY_ID' => $year->SY_ID,
    ]);
    foreach (GradeStatus::slugs() as $offset => $status) {
        $student = Student::query()->create([
            'lrn' => (string) (123456780000 + $index * 10 + $offset),
            'first_name' => 'Dashboard', 'last_name' => 'Student '.$offset, 'status' => 'active',
        ]);
        $enrollment = Enrollment::query()->create([
            'student_ID' => $student->id, 'section_ID' => $section->section_ID,
            'SY_ID' => $year->SY_ID, 'grade_ID' => GradeLevel::idForValue('grade_11'),
            'learner_type' => 'regular', 'enrollment_status' => 'enrolled',
        ]);
        $roster = StudentSubject::query()->firstOrCreate([
            'enrollment_ID' => $enrollment->enrollment_ID, 'subject_ID' => $curriculumSubject->subject_ID,
        ]);
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => GradingTerm::query()->where('key', 'term_1')->value('term_ID'),
            'numeric_grade' => 90, 'status' => $status, 'posted_by' => $teacher->staff_id,
            'submitted_at' => now()->subDays($index),
        ]);
    }

    return $assignment;
}

test('registrar dashboard shows its modules and a useful empty state without a school year', function () {
    $response = $this->get(route('registrar.dashboard'))->assertOk();
    foreach (['Registrar Dashboard', 'Grade Approvals', 'Class Subjects', 'Student Masterlist',
        'Ready for review', 'Grade record status', 'No grades awaiting approval',
        'No school years'] as $label) {
        $response->assertSee($label);
    }
    $response->assertViewHas('studentCount', 0)->assertViewHas('subjectCount', 0)
        ->assertViewHas('pendingSubjects', 0)->assertDontSee('Gender Ratio')->assertDontSee('Age Alignment');
});

test('registrar dashboard scopes counts and review links to the selected school year', function () {
    $current = AcademicYear::query()->create([
        'school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true,
    ]);
    $old = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $currentAssignment = createRegistrarDashboardRecords($current, 1);
    $oldAssignment = createRegistrarDashboardRecords($old, 2);
    foreach ([[$current, $currentAssignment, []], [$old, $oldAssignment, ['academic_year_id' => $old->SY_ID]]] as [$year, $assignment, $params]) {
        $response = $this->get(route('registrar.dashboard', $params))->assertOk()
            ->assertViewHas('studentCount', 5)->assertViewHas('subjectCount', 1)
            ->assertViewHas('sectionCount', 1)->assertViewHas('pendingSubjects', 1);
        expect($response->viewData('selectedYear')->SY_ID)->toBe($year->SY_ID);
        expect($response->viewData('reviewQueue')->pluck('assignment_ID')->all())->toBe([$assignment->assignment_ID]);
        expect($response->viewData('reviewQueue')->first()->pending_grades_count)->toBe(1);
        expect($response->viewData('gradeCounts'))->toBe(array_fill_keys(GradeStatus::slugs(), 1));
        $response->assertSee(route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => 'submitted']));
        $response->assertSee(route('registrar.students', ['academic_year_id' => $year->SY_ID]));
        $response->assertSee(route('registrar.class-subjects.index', ['SY_ID' => $year->SY_ID]));
        $response->assertSee(route('registrar.grade-approvals', ['academic_year_id' => $year->SY_ID, 'status' => 'submitted', 'term_id' => '']));
    }
});

test('registrar dashboard rejects an invalid school year', function () {
    $this->getJson(route('registrar.dashboard', ['academic_year_id' => 999999]))
        ->assertUnprocessable()->assertJsonValidationErrors('academic_year_id');
});

test('registrar dashboard denies other staff roles', function () {
    $this->registrar->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id]);
    $this->get(route('registrar.dashboard'))->assertForbidden();
});
