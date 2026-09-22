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
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

function createTeacherSectionGradeFixtures(): array
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

function teacherSectionGradeField(int $enrollmentId): string
{
    return "grades.{$enrollmentId}.shs_sem1_term_1.grade";
}

test('teacher subject list identifies subjects with no grade records as ungraded', function () {
    ['teacher' => $teacher] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('ENG11 - English 11')
        ->assertSee('Grade status: Ungraded');
});

test('teacher subject list displays the configured grade status from its status ID', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $studentSubject = \App\Models\StudentSubject::query()->firstOrCreate([
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

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('Grade status: Submitted');
});

test('teacher subject list uses term rather than semester for junior high sections', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $juniorHighGrade = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $assignment->section->update(['grade_ID' => $juniorHighGrade->grade_ID]);

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('Term 1')
        ->assertDontSee('Full year');
});

test('teacher grade input only accepts numeric values up to 100', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment));

    $response->assertOk();
    $response->assertSee('Newton');
    $response->assertSee('ENG11 - English 11');
    $response->assertSee('Grade Sheet');
    $response->assertSee('type="number"', false);
    $response->assertSee('min="0"', false);
    $response->assertSee('max="100"', false);
    $response->assertSee('data-grade-input', false);
});

test('second-semester subjects use the section roster after the active semester changes', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();

    $assignment->curriculumSubject->update(['semester' => 'second']);
    GradingTermSetting::current()->setSeniorHighPeriod('second', 1);

    $response = $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment));

    $response->assertOk();
    $response->assertSee('Santos, Ana');
    $response->assertSee('Second Semester');
});

test('teacher can save a numeric grade that does not exceed 100', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => 88],
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Grades saved successfully.');

    $grade = StudentSubjectGrade::query()
        ->where('enrollment_ID', $enrollment->enrollment_ID)
        ->where('assignment_ID', $assignment->assignment_ID)
        ->where('grading_period', 'shs_sem1_term_1')
        ->first();

    expect($grade?->numeric_grade)->toEqual(88)
        ->and($grade->isSeniorHigh())->toBeTrue()
        ->and($grade->term_ID)->not->toBeNull()
        ->and($grade->semester_ID)->not->toBeNull()
        ->and($grade->status)->toBe('draft');
});

test('teacher can save a grade of 100', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => 100],
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect(
        StudentSubjectGrade::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->where('assignment_ID', $assignment->assignment_ID)
            ->value('numeric_grade')
    )->toEqual(100);
});

it('rejects invalid teacher grade values', function (mixed $grade) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->from(route('teacher.sections.show', $assignment))->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => $grade],
            ],
        ],
    ]);

    $response->assertRedirect(route('teacher.sections.show', $assignment));
    $response->assertSessionHasErrors(teacherSectionGradeField($enrollment->enrollment_ID));

    expect(
        StudentSubjectGrade::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->where('assignment_ID', $assignment->assignment_ID)
            ->exists()
    )->toBeFalse();
})->with([
    'letters' => 'abc',
    'over one hundred' => 101,
    'negative' => -1,
    'over one hundred decimal' => 100.01,
]);
