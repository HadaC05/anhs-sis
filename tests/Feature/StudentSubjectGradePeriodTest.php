<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('student subject grades can reference a junior high term or a senior high semester term', function () {
    expect(Schema::hasColumn('student_subject_grades', 'term_ID'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_grades', 'semester_ID'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_grades', 'quarter_ID'))->toBeFalse();
});

test('junior high grades store a term foreign key and no senior high period', function () {
    $grade = createPeriodGrade('Grade 7', 'term_1');
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    expect($grade->isJuniorHigh())->toBeTrue()
        ->and($grade->isSeniorHigh())->toBeFalse()
        ->and((int) $grade->term_ID)->toBe((int) $term->term_ID)
        ->and($grade->semester_ID)->toBeNull()
        ->and($grade->term?->label)->toBe('Term 1')
        ->and($grade->status)->toBe(GradeStatus::DRAFT);
});

test('senior high grades store semester and term foreign keys', function () {
    $grade = createPeriodGrade('Grade 11', 'shs_sem1_term_1', 'first');
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    expect($grade->isSeniorHigh())->toBeTrue()
        ->and($grade->isJuniorHigh())->toBeFalse()
        ->and((int) $grade->term_ID)->toBe((int) $term->term_ID)
        ->and((int) $grade->semester_ID)->toBe((int) GradingSemester::idFor(GradingSemester::FIRST))
        ->and($grade->term?->label)->toBe('Term 1')
        ->and($grade->semester?->key)->toBe('first')
        ->and($grade->grading_period)->toBe('shs_sem1_term_1');
});

test('senior high second semester grades reference the matching term', function () {
    $grade = createPeriodGrade('Grade 11', 'shs_sem2_term_2', 'second');
    $term = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    expect((int) $grade->term_ID)->toBe((int) $term->term_ID)
        ->and($grade->semester?->key)->toBe('second')
        ->and($grade->grading_period)->toBe('shs_sem2_term_2');
});

function createPeriodGrade(string $gradeLabel, string $period, ?string $semester = null): StudentSubjectGrade
{
    $role = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.period.'.uniqid(),
        'password' => Hash::make('password'),
        'first_name' => 'Period',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027-'.uniqid(),
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'Period Curriculum '.uniqid(),
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', $gradeLabel)->firstOrFail();
    $gradeValue = strtolower(str_replace(' ', '_', $gradeLabel));
    $subject = Subject::query()->create([
        'code' => 'PD'.substr(uniqid(), -4),
        'title' => 'Period Subject',
        'type' => 'core',
        'status' => 'active',
    ]);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'grade_level' => $gradeValue,
        'semester' => $semester ?? 'first',
    ]);
    $section = Section::query()->create([
        'name' => 'Period Section',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'capacity' => 40,
    ]);
    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);
    $student = Student::query()->create([
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Paolo',
        'last_name' => 'Reyes',
        'status' => 'active',
    ]);
    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => $semester,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return StudentSubjectGrade::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'grading_period' => $period,
        'numeric_grade' => 90,
        'posted_by' => $teacher->staff_id,
    ]);
}
