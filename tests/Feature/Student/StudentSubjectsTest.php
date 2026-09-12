<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{
 *     student: Student,
 *     teacher: Staff,
 *     academicYear: AcademicYear,
 *     curriculum: Curriculum,
 *     cluster: Cluster,
 *     section: Section,
 *     enrollment: Enrollment
 * }
 */
function createStudentSubjectsContext(string $gradeLabel, array $overrides = []): array
{
    $suffix = $overrides['suffix'] ?? strtolower(str_replace(' ', '', $gradeLabel)).fake()->unique()->numerify('###');

    $student = Student::query()->create([
        'username' => 'student.subjects.'.$suffix,
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => $overrides['lrn'] ?? ('14'.str_pad((string) random_int(1000000000, 1999999999), 10, '0', STR_PAD_LEFT)),
        'first_name' => 'Liza',
        'last_name' => 'Gomez',
        'status' => 'active',
    ]);

    $teacherRole = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.subjects.'.$suffix,
        'password' => Hash::make('password'),
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'status' => 'active',
    ]);

    $academicYear = $overrides['academicYear'] ?? AcademicYear::query()->firstOrCreate(
        ['school_year' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]
    );

    $curriculum = Curriculum::query()->firstOrCreate(
        ['name' => 'Student Subjects Curriculum'],
        [
            'description' => 'Curriculum for student subjects tests',
            'status' => true,
        ]
    );

    $gradeLevel = GradeLevel::query()->where('grade_label', $gradeLabel)->firstOrFail();
    $cluster = Cluster::query()->create(['name' => 'Subjects Cluster '.$suffix]);

    $section = Section::query()->create([
        'name' => $overrides['section_name'] ?? 'Ruby '.$suffix,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'cluster_ID' => $cluster->cluster_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => $overrides['semester'] ?? (in_array($gradeLabel, ['Grade 11', 'Grade 12'], true) ? 'first' : null),
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('student', 'teacher', 'academicYear', 'curriculum', 'cluster', 'section', 'enrollment');
}

function createAssignedSubject(
    array $context,
    string $code,
    string $title,
    string $semester = 'first',
    string $type = 'core'
): TeacherSubjectAssignment {
    $subject = Subject::query()->create([
        'cluster_ID' => $context['cluster']->cluster_ID,
        'code' => $code,
        'title' => $title,
        'type' => $type,
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $context['curriculum']->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $context['cluster']->cluster_ID,
        'grade_level' => $context['enrollment']->grade_level,
        'semester' => $semester,
    ]);

    return TeacherSubjectAssignment::query()->create([
        'section_ID' => $context['section']->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $context['teacher']->staff_id,
        'SY_ID' => $context['academicYear']->SY_ID,
    ]);
}

test('junior high student subjects page uses term filters from lookup tables', function () {
    $context = createStudentSubjectsContext('Grade 8');
    createAssignedSubject($context, 'SCI8', 'Science 8');

    $term = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    $response = $this->actingAs($context['student'])->get(route('student.subjects', [
        'SY_ID' => $context['academicYear']->SY_ID,
        'term_ID' => $term->term_ID,
    ]));

    $response->assertOk();
    $response->assertSee('School Year');
    $response->assertSee('Term');
    $response->assertSee($term->label);
    $response->assertSee('2026-2027');
    $response->assertSee('SCI8');
    $response->assertSee('Science 8');
    $response->assertSee('Reyes, Maria');
    $response->assertSee('Core');
    $response->assertDontSee('name="semester_ID"', false);
    $response->assertDontSee('name="quarter_ID"', false);
    $response->assertDontSee('>Semester</th>', false);
    $response->assertDontSee('>Quarter</label>', false);
});

test('senior high student subjects page uses semester and term filters from lookup tables', function () {
    $context = createStudentSubjectsContext('Grade 11');
    createAssignedSubject($context, 'GENMATH', 'General Mathematics', 'first');

    $semester = GradingSemester::query()->where('key', GradingSemester::FIRST)->firstOrFail();
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    $response = $this->actingAs($context['student'])->get(route('student.subjects', [
        'SY_ID' => $context['academicYear']->SY_ID,
        'semester_ID' => $semester->semester_ID,
        'term_ID' => $term->term_ID,
    ]));

    $response->assertOk();
    $response->assertSee('School Year');
    $response->assertSee('Semester');
    $response->assertSee('Term');
    $response->assertSee($semester->label);
    $response->assertSee($term->label);
    $response->assertSee('GENMATH');
    $response->assertSee('General Mathematics');
    $response->assertSee('Reyes, Maria');
    $response->assertDontSee('name="quarter_ID"', false);
    $response->assertDontSee('>Quarter</label>', false);
});

test('senior high student subjects page filters curriculum subjects by selected semester', function () {
    $context = createStudentSubjectsContext('Grade 12');
    createAssignedSubject($context, 'STATPROB', 'Statistics and Probability', 'first');
    createAssignedSubject($context, 'PHYSCI', 'Physical Science', 'second');

    $secondSemester = GradingSemester::query()->where('key', GradingSemester::SECOND)->firstOrFail();
    $secondTerm = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    $response = $this->actingAs($context['student'])->get(route('student.subjects', [
        'SY_ID' => $context['academicYear']->SY_ID,
        'semester_ID' => $secondSemester->semester_ID,
        'term_ID' => $secondTerm->term_ID,
    ]));

    $response->assertOk();
    $response->assertSee('PHYSCI');
    $response->assertSee('Physical Science');
    $response->assertDontSee('STATPROB');
    $response->assertDontSee('Statistics and Probability');
});

test('senior high student subjects default to the configured active semester', function () {
    $context = createStudentSubjectsContext('Grade 12');
    createAssignedSubject($context, 'STATPROB', 'Statistics and Probability', 'first');
    createAssignedSubject($context, 'PHYSCI', 'Physical Science', 'second');

    GradingTermSetting::current()->setSeniorHighPeriod('second', 1);

    $response = $this->actingAs($context['student'])->get(route('student.subjects', [
        'SY_ID' => $context['academicYear']->SY_ID,
    ]));

    $response->assertOk();
    $response->assertSee('Second Semester');
    $response->assertSee('PHYSCI');
    $response->assertSee('Physical Science');
    $response->assertDontSee('STATPROB');
    $response->assertDontSee('Statistics and Probability');
});

test('student subjects page filters assignments by selected school year', function () {
    $yearOne = AcademicYear::query()->create([
        'school_year' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => false,
    ]);
    $yearTwo = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $firstYear = createStudentSubjectsContext('Grade 9', [
        'suffix' => 'yearone',
        'academicYear' => $yearOne,
        'section_name' => 'Pearl Year One',
    ]);
    createAssignedSubject($firstYear, 'MATH9A', 'Mathematics 9 A');

    $secondYearEnrollment = Enrollment::query()->create([
        'student_ID' => $firstYear['student']->id,
        'section_ID' => Section::query()->create([
            'name' => 'Pearl Year Two',
            'grade_ID' => $firstYear['enrollment']->grade_ID,
            'SY_ID' => $yearTwo->SY_ID,
            'curriculum_ID' => $firstYear['curriculum']->curriculum_ID,
            'staff_ID' => $firstYear['teacher']->staff_id,
            'cluster_ID' => $firstYear['cluster']->cluster_ID,
            'room' => 'Room 102',
            'capacity' => 40,
        ])->section_ID,
        'SY_ID' => $yearTwo->SY_ID,
        'cluster_ID' => $firstYear['cluster']->cluster_ID,
        'grade_ID' => $firstYear['enrollment']->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $yearTwoSection = $secondYearEnrollment->section;
    $yearTwoSubject = Subject::query()->create([
        'cluster_ID' => $firstYear['cluster']->cluster_ID,
        'code' => 'MATH9B',
        'title' => 'Mathematics 9 B',
        'type' => 'core',
        'status' => 'active',
    ]);
    $yearTwoCurriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $firstYear['curriculum']->curriculum_ID,
        'subject_ID' => $yearTwoSubject->subject_ID,
        'cluster_ID' => $firstYear['cluster']->cluster_ID,
        'grade_level' => 'grade_9',
        'semester' => 'first',
    ]);
    TeacherSubjectAssignment::query()->create([
        'section_ID' => $yearTwoSection->section_ID,
        'curr_subj_ID' => $yearTwoCurriculumSubject->curr_subj_ID,
        'staff_ID' => $firstYear['teacher']->staff_id,
        'SY_ID' => $yearTwo->SY_ID,
    ]);

    $response = $this->actingAs($firstYear['student'])->get(route('student.subjects', [
        'SY_ID' => $yearOne->SY_ID,
    ]));

    $response->assertOk();
    $response->assertSee('MATH9A');
    $response->assertSee('Mathematics 9 A');
    $response->assertDontSee('MATH9B');
    $response->assertDontSee('Mathematics 9 B');
});

test('student subjects page shows assigned subjects even when grades are not released', function () {
    $context = createStudentSubjectsContext('Grade 7');
    createAssignedSubject($context, 'ENG7', 'English 7');

    $response = $this->actingAs($context['student'])->get(route('student.subjects'));

    $response->assertOk();
    $response->assertSee('ENG7');
    $response->assertSee('English 7');
    $response->assertDontSee('No Released Grades');
});

test('student subjects page shows an empty state when the student has no enrollment', function () {
    $student = Student::query()->create([
        'username' => 'student.subjects.empty',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '141400000001',
        'first_name' => 'Liza',
        'last_name' => 'Gomez',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($student)->get(route('student.subjects'));

    $response->assertOk();
    $response->assertSee('No Enrollments Found');
    $response->assertSee('School Year');
    $response->assertDontSee('name="term_ID"', false);
    $response->assertDontSee('name="semester_ID"', false);
});

test('student subjects page rejects invalid lookup filter ids', function () {
    $context = createStudentSubjectsContext('Grade 10');

    $this->actingAs($context['student'])
        ->from(route('student.subjects'))
        ->get(route('student.subjects', ['SY_ID' => 999999]))
        ->assertRedirect(route('student.subjects'))
        ->assertSessionHasErrors('SY_ID');
});
