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
use Illuminate\Support\Facades\Hash;

function createAdvisorySf9Fixtures(bool $isSeniorHigh = true): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => $isSeniorHigh ? 'teacher.sf9.shs' : 'teacher.sf9.jhs',
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
    $cluster = Cluster::query()->create([
        'name' => $isSeniorHigh ? 'Science, Technology, Engineering and Mathematics' : 'General',
    ]);
    $course = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'STEM',
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => $isSeniorHigh ? 'ORALCOM' : 'ENG7',
        'title' => $isSeniorHigh ? 'Oral Communication' : 'English',
        'type' => 'core',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
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

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

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

        TeacherSubjectAssignment::query()->create([
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

    StudentSubjectGrade::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'grading_period' => $isSeniorHigh ? 'shs_sem1_term_1' : 'term_1',
        'numeric_grade' => 91,
        'posted_by' => $teacher->staff_id,
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('advisory teacher can print the junior high sf9 layout', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures(isSeniorHigh: false);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section));

    $response->assertOk();
    $response->assertSee('SF 9 - JHS');
    $response->assertSee('Learning Areas');
    $response->assertSee('Filipino');
    $response->assertSee('MAPEH');
    $response->assertSee('General Average');
    $response->assertDontSee('SF9-SHS');
    $response->assertDontSee('Track / Strand');
    $response->assertDontSee('Semester Final Grade');
});

test('advisory teacher can print the senior high sf9 performance report layout', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf9Fixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.sf9', $section));

    $response->assertOk();
    $response->assertSee("Learner's Performance Report", false);
    $response->assertSee('Learning Progress and Achievement');
    $response->assertSee('Learning Areas');
    $response->assertSee('Core Subjects');
    $response->assertSee('Elective Subjects');
    $response->assertSee('Effective Communication');
    $response->assertSee('Mabisang Komunikasyon');
    $response->assertSee('General Mathematics');
    $response->assertSee('Pre-Calculus');
    $response->assertSee('Final Grade');
    $response->assertSee('General Average');
    $response->assertSee('Performance Descriptors');
    $response->assertSee('Advancing');
    $response->assertSee('Benchmarking');
    $response->assertSee("Teacher's Comments/ Remarks", false);
    $response->assertSee('Track (SHS only):');
    $response->assertSee('ACADEMIC');
    $response->assertSee('Term 1');
    $response->assertSee('Term 3');
    $response->assertSee('No. of Class Days');
    $response->assertSee('Santos');
    $response->assertSee('91');
    $response->assertDontSee('SF 9 - JHS');
    $response->assertDontSee('SF9-SHS');
    $response->assertDontSee('First Semester');
    $response->assertDontSee('Semester Final Grade');
    $response->assertDontSee('Applied and Specialized Subjects');
    $response->assertDontSee('Track / Strand');
    $response->assertDontSee("Report on Learner's Observed Values", false);
    $response->assertDontSee('>MAPEH</td>', false);
    $response->assertDontSee('Quarter 1');
    $response->assertDontSee('1st Quarter');
});
