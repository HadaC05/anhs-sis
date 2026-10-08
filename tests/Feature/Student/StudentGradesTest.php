<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
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
    // Profile completion is covered separately; these tests exercise grade access.
    $this->withoutMiddleware(\App\Http\Middleware\EnsureStudent::class);
});

/**
 * @return array{
 *     student: Student,
 *     teacher: Staff,
 *     academicYear: AcademicYear,
 *     section: Section,
 *     enrollment: Enrollment,
 *     assignment: TeacherSubjectAssignment
 * }
 */
function createStudentGradesFixtures(bool $withReleasedGrade = true): array
{
    $suffix = fake()->unique()->numerify('####');

    $student = Student::query()->create([
        'username' => 'student.grades.'.$suffix,
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '12'.str_pad($suffix, 10, '0', STR_PAD_LEFT),
        'first_name' => 'Liza',
        'last_name' => 'Gomez',
        'status' => 'active',
    ]);

    $teacherRole = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.grades.'.$suffix,
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
        'name' => 'Junior High Curriculum',
        'description' => 'JHS curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();
    $curriculum->update([
        'grade_ID' => $gradeLevel->getKey(),
        'semester_ID' => \App\Models\GradingSemester::query()->where('key', 'first')->value('semester_ID'),
    ]);

    $cluster = Cluster::query()->create(['name' => 'General '.$suffix]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'MATH8',
        'title' => 'Mathematics 8',
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
        'name' => 'Emerald',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'room' => 'Room 201',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'subject_ID' => $curriculumSubject->subject_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    if ($withReleasedGrade) {
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $enrollment->studentSubjects()->firstOrFail()->getKey(),
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => \App\Models\GradingTerm::query()->where('key', 'term_1')->value('term_ID'),
            'numeric_grade' => 90,
            'status' => 'released',
            'posted_by' => $teacher->staff_id,
        ]);
    }

    return [
        'student' => $student,
        'teacher' => $teacher,
        'academicYear' => $academicYear,
        'section' => $section,
        'enrollment' => $enrollment,
        'assignment' => $assignment,
    ];
}

test('student grades page shows session filter and released grades table', function () {
    $fixtures = createStudentGradesFixtures();

    $response = $this->actingAs($fixtures['student'])->get(route('student.grades'));

    $response->assertOk();
    $response->assertSee('Academic Session');
    $response->assertSee('Grade Report');
    $response->assertSee($fixtures['student']->lrn);
    $response->assertSee('Emerald');
    $response->assertSee('MATH8');
    $response->assertSee('Mathematics 8');
    $response->assertSee('Subject Type');
    $response->assertSee('Final Grade');
    $response->assertSee('PASSED');
});

test('student grades page includes the learners elective and excludes electives selected only by classmates', function () {
    $fixtures = createStudentGradesFixtures(withReleasedGrade: false);

    $selectedElective = Subject::query()->create([
        'cluster_ID' => $fixtures['enrollment']->cluster_ID,
        'code' => 'ELEC-OWN',
        'title' => 'Selected Elective',
        'type' => 'elective',
        'status' => 'active',
    ]);
    $otherElective = Subject::query()->create([
        'cluster_ID' => $fixtures['enrollment']->cluster_ID,
        'code' => 'ELEC-OTHER',
        'title' => 'Classmate Elective',
        'type' => 'elective',
        'status' => 'active',
    ]);

    foreach ([$selectedElective, $otherElective] as $subject) {
        $offering = CurriculumSubject::query()->create([
            'curriculum_grade_level_ID' => $fixtures['enrollment']->curriculum_grade_level_ID,
            'subject_ID' => $subject->subject_ID,
        ]);

        TeacherSubjectAssignment::query()->create([
            'section_ID' => $fixtures['section']->section_ID,
            'subject_ID' => $offering->subject_ID,
            'staff_ID' => null,
            'SY_ID' => $fixtures['academicYear']->SY_ID,
        ]);

        if ($subject->is($selectedElective)) {
            StudentSubject::query()->create([
                'enrollment_ID' => $fixtures['enrollment']->enrollment_ID,
                'subject_ID' => $offering->subject_ID,
            ]);
        }
    }

    $this->actingAs($fixtures['student'])->get(route('student.grades'))
        ->assertOk()
        ->assertSee('ELEC-OWN')
        ->assertSee('Selected Elective')
        ->assertDontSee('ELEC-OTHER')
        ->assertDontSee('Classmate Elective');
});

test('student grade report shows released grades and supports printing', function () {
    $fixtures = createStudentGradesFixtures();
    $url = route('student.grades', ['session' => $fixtures['enrollment']->getKey(), 'report' => 1]);
    $this->actingAs($fixtures['student'])->get(route('student.grades'))
        ->assertOk()->assertSee(e($url), false);
    $this->get($url)->assertOk()
        ->assertSee('Print / Save as PDF')
        ->assertSee('window.print()', false)
        ->assertSee('Mathematics 8')
        ->assertSee('>90<', false)
        ->assertSee('Pending');

    $fixtures['assignment']->grades()->firstOrFail()->update(['status' => 'approved']);
    $this->get($url)->assertOk()->assertDontSee('>90<', false);
});

test('student grade report rejects an enrollment belonging to another student', function () {
    $fixtures = createStudentGradesFixtures();
    $otherStudent = $fixtures['student']->replicate();
    $otherStudent->username = 'other.grade.report';
    $otherStudent->lrn = '999999999999';
    $otherStudent->save();
    $otherEnrollment = $fixtures['enrollment']->replicate();
    $otherEnrollment->student_ID = $otherStudent->getKey();
    $otherEnrollment->save();
    $this->actingAs($fixtures['student'])
        ->get(route('student.grades', ['session' => $otherEnrollment->getKey(), 'report' => 1]))
        ->assertNotFound();
});

test('student grades page keeps collapsible subject rows when no grades are released', function () {
    $fixtures = createStudentGradesFixtures(withReleasedGrade: false);

    $response = $this->actingAs($fixtures['student'])->get(route('student.grades'));

    $response->assertOk();
    $response->assertSee('MATH8');
    $response->assertSee('Mathematics 8');
    $response->assertSee('grade-expand', false);
    $response->assertSee('There are no grades yet.');
    $response->assertDontSee('No Released Grades');
    $response->assertDontSee('PASSED');
});

test('student grades page keeps unreleased terms visible without exposing their grades', function () {
    $fixtures = createStudentGradesFixtures();
    $term = \App\Models\GradingTerm::query()
        ->seniorHigh()
        ->where('key', 'term_2')
        ->firstOrFail();

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $fixtures['enrollment']->studentSubjects()->firstOrFail()->getKey(),
        'assignment_ID' => $fixtures['assignment']->assignment_ID,
        'term_ID' => $term->term_ID,
        'numeric_grade' => 67,
        'status' => 'submitted',
        'posted_by' => $fixtures['teacher']->staff_id,
    ]);

    $response = $this->actingAs($fixtures['student'])->get(route('student.grades'));

    $response->assertOk()
        ->assertSee('data-test="grade-period-shs_sem1_term_2"', false)
        ->assertSee('data-test="mobile-grade-period-shs_sem1_term_2"', false)
        ->assertSee('Not released')
        ->assertDontSee('>67<', false);
});
