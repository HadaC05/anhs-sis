<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('grade statuses are the canonical approval list', function () {
    expect(Schema::hasTable('grade_statuses'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_grades', 'status'))->toBeFalse()
        ->and(Schema::hasColumn('student_subject_grades', 'grade_status_ID'))->toBeTrue()
        ->and(GradeStatus::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(GradeStatus::slugs())
        ->and(GradeStatus::options())->toMatchArray([
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'released' => 'Released',
            'rejected' => 'Rejected',
        ]);
});

test('student subject grades store a foreign key to the grade statuses table', function () {
    $grade = createSubjectGradeForPeriod('term_1');

    expect($grade->status)->toBe(GradeStatus::DRAFT)
        ->and($grade->grade_status_ID)->toBe(GradeStatus::idFor(GradeStatus::DRAFT))
        ->and($grade->gradeStatus()->first()?->name)->toBe('Draft')
        ->and($grade->status_label)->toBe('Draft');
});

test('student subject grades cannot use a status that is not in the reference table', function () {
    expect(fn () => createSubjectGradeForPeriod('term_1', ['status' => 'pending']))
        ->toThrow(\InvalidArgumentException::class);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createSubjectGradeForPeriod(string $period, array $overrides = []): StudentSubjectGrade
{
    $role = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.grade.status.'.uniqid(),
        'password' => Hash::make('password'),
        'first_name' => 'Grade',
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
        'name' => 'Grade Status Curriculum '.uniqid(),
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $subject = Subject::query()->create([
        'code' => 'GS'.substr(uniqid(), -4),
        'title' => 'Grade Status Subject',
        'type' => 'core',
        'status' => 'active',
    ]);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'grade_level' => 'grade_7',
        'semester' => 'first',
    ]);
    $section = Section::query()->create([
        'name' => 'Status Section',
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
        'first_name' => 'Lina',
        'last_name' => 'Cruz',
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

    return StudentSubjectGrade::query()->create(array_merge([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'grading_period' => $period,
        'numeric_grade' => 88,
        'posted_by' => $teacher->staff_id,
    ], $overrides));
}
