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
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

test('principal can view the enrollment dashboard', function () {
    $role = Role::query()->create(['role_name' => 'principal']);
    $principal = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'principal.dashboard',
        'password' => Hash::make('password'),
        'first_name' => 'School',
        'last_name' => 'Principal',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($principal)->get(route('principal.dashboard'));

    $response->assertOk();
    $response->assertSee('Principal Dashboard');
    $response->assertSee('Total Enrollees');
    $response->assertSee('Officially Enrolled');
    $response->assertSee('Temporarily Enrolled');
    $response->assertSee('Transferees');
    $response->assertSee('Balik Aral');
    $response->assertSee('Gender Ratio');
    $response->assertSee('Cluster Distribution');
    $response->assertSee('Student Proficiency Distribution');
    $response->assertSee('Enrollment by Grade Level');
    $response->assertSee('Age Alignment');
    $response->assertDontSee('Recent Applications');
    $response->assertDontSee('pending review');
    $response->assertDontSee('marked for placement test');
    $response->assertSee('Open full report');
});

test('principal can view the age alignment report', function () {
    $role = Role::query()->create(['role_name' => 'principal']);
    $principal = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'principal.age-alignment',
        'password' => Hash::make('password'),
        'first_name' => 'School',
        'last_name' => 'Principal',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($principal)->get(route('principal.reports.age-for-grade'));

    $response->assertOk();
    $response->assertSee('Age Alignment Report');
    $response->assertSee('Student records');
    $response->assertSee('Expected age by grade');
    $response->assertSee('value="overage" selected', false);
    $response->assertDontSee('>Expected</th>', false);
    $response->assertDontSee('guidance/enrollments');
});

test('principal can view subject proficiency levels by section', function () {
    $role = Role::query()->create(['role_name' => 'principal']);
    $principal = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'principal.proficiency',
        'password' => Hash::make('password'),
        'first_name' => 'School',
        'last_name' => 'Principal',
        'status' => 'active',
    ]);

    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.proficiency',
        'password' => Hash::make('password'),
        'first_name' => 'Grade',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    $student = Student::query()->create([
        'username' => 'student.proficiency',
        'password' => Hash::make('password'),
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);
    $otherStudent = Student::query()->create([
        'username' => 'student.other.proficiency',
        'password' => Hash::make('password'),
        'lrn' => '210987654321',
        'first_name' => 'Ben',
        'last_name' => 'Reyes',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);
    $gradeEleven = GradeLevel::query()->firstOrCreate(['grade_label' => 'Grade 11'], ['category' => 'senior_high']);
    $gradeTwelve = GradeLevel::query()->firstOrCreate(['grade_label' => 'Grade 12'], ['category' => 'senior_high']);
    $cluster = Cluster::query()->create(['name' => 'STEM']);
    $curriculum = Curriculum::query()->create(['name' => 'Basic Education Curriculum', 'status' => true]);
    $section = Section::query()->create([
        'name' => 'Sampaguita',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeEleven->grade_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'capacity' => 40,
    ]);
    $otherSection = Section::query()->create([
        'name' => 'Mabini',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeTwelve->grade_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'capacity' => 40,
    ]);
    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'MATH7-PROF',
        'title' => 'Mathematics',
        'type' => 'core',
        'status' => 'active',
    ]);
    $otherSubject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'ENG7-PROF',
        'title' => 'English',
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
    $otherCurriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $otherSubject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_12',
        'semester' => 'first',
    ]);
    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);
    TeacherSubjectAssignment::query()->create([
        'section_ID' => $otherSection->section_ID,
        'curr_subj_ID' => $otherCurriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);
    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeEleven->grade_ID,
        'semester' => 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);
    Enrollment::query()->create([
        'student_ID' => $otherStudent->id,
        'section_ID' => $otherSection->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeTwelve->grade_ID,
        'semester' => 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    foreach (['shs_sem1_term_1' => 88, 'shs_sem1_term_2' => 86, 'shs_sem1_term_3' => 87, 'shs_sem2_term_1' => 87] as $period => $grade) {
        StudentSubjectGrade::query()->create([
            'enrollment_ID' => $enrollment->enrollment_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'grading_period' => $period,
            'numeric_grade' => $grade,
            'remarks' => 'Passed',
            'status' => 'released',
            'posted_by' => $teacher->staff_id,
        ]);
    }

    $emptyResponse = $this->actingAs($principal)->get(route('principal.proficiency-levels'));
    $emptyResponse->assertOk();
    $emptyResponse->assertSee('Choose a subject to begin');

    $response = $this->actingAs($principal)->get(route('principal.proficiency-levels', [
        'subject_id' => $subject->subject_ID,
        'academic_year_id' => $academicYear->SY_ID,
        'grade_level' => $gradeEleven->grade_ID,
        'proficiency_level' => 'Proficient',
        'search' => 'Santos',
    ]));

    $response->assertOk();
    $response->assertSee('Student Proficiency Levels');
    $response->assertSee('MATH7-PROF');
    $response->assertSee('Mathematics');
    $response->assertSee('Sampaguita');
    $response->assertSee('Santos, Ana');
    $response->assertSee('87');
    $response->assertSee('Proficient');
    $response->assertSee('Grade 11');
    $response->assertSee('Download');
    $response->assertSee('id="downloadProficiencyChart"', false);
    $response->assertSee('id="proficiencyLevelsChart"', false);
    $response->assertSee('window.proficiencyChartPayload', false);
    $response->assertSee('Distribution Chart');
    $response->assertSee('currentDevicePixelRatio', false);
    $response->assertDontSee('Math.max(chart.width, 420)', false);
    $response->assertDontSee('!!json_encode');
    $response->assertDontSee('Reyes, Ben');
    $response->assertDontSee('Mabini');
});
