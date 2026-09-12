<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradingTermSetting;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
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
    $response->assertSee($section->name);
    $response->assertSee($subject->code);
});

test('registrar can view teachers with their advisory and subject assignments', function () {
    ['registrar' => $registrar, 'teacher' => $teacher, 'section' => $section, 'subject' => $subject] = createRegistrarClassSubjectFixtures();

    $unassignedTeacher = Staff::query()->create([
        'role_id' => Role::query()->where('role_name', 'teacher')->value('id'),
        'username' => 'teacher.unassigned',
        'password' => Hash::make('password'),
        'first_name' => 'Unassigned',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    $response = $this->actingAs($registrar)->get(route('registrar.teacher-assignments'));

    $response->assertOk();
    $response->assertSee('Teacher Assignments');
    $response->assertSee(trim($teacher->last_name.', '.$teacher->first_name));
    $response->assertSee($section->name);
    $response->assertSee($subject->code);
    $response->assertSee(trim($unassignedTeacher->last_name.', '.$unassignedTeacher->first_name));
    $response->assertSee('No advisory class assigned.');
    $response->assertSee('No subjects assigned.');
});

test('registrar can view the students assigned to a class', function () {
    ['registrar' => $registrar, 'section' => $section, 'academicYear' => $academicYear, 'gradeLevel' => $gradeLevel] = createRegistrarClassSubjectFixtures();

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Class',
        'last_name' => 'Student',
        'status' => 'active',
    ]);

    Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $response = $this->actingAs($registrar)->get(route('registrar.classes.students', $section));

    $response->assertOk();
    $response->assertSee('Class roster');
    $response->assertSee('Student, Class');
    $response->assertSee('123456789012');
});

test('registrar can unlock a grading term for a class subject', function () {
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

    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'grading_period' => 'shs_sem1_term_1',
        'numeric_grade' => 88,
        'status' => 'approved',
        'posted_by' => $teacher->staff_id,
    ]);

    $response = $this->actingAs($registrar)->post(route('registrar.class-subjects.unlock-term', $assignment), [
        'grading_period' => 'shs_sem1_term_1',
        'notes' => 'Principal approved correction',
    ]);

    $response->assertRedirect(route('registrar.class-subjects.show', $assignment));
    $response->assertSessionHas('status');

    expect(AssignmentGradeTermUnlocker::unlockedPeriodKeysFor($assignment->assignment_ID))->toContain('shs_sem1_term_1');

    $grade = StudentSubjectGrade::query()->where('assignment_ID', $assignment->assignment_ID)->first();
    expect($grade?->status)->toBe('draft');
});
