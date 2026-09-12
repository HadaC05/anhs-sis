<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{
 *     student: Student,
 *     enrollment: Enrollment,
 *     cluster: Cluster,
 *     preferredCourse: PreferredCourse,
 *     section: Section
 * }
 */
function createStudentSummaryVisibilityEnrollment(string $gradeLabel): array
{
    $suffix = strtolower(str_replace(' ', '', $gradeLabel));

    $student = Student::query()->create([
        'username' => 'student.summary.'.$suffix,
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '13'.str_pad(preg_replace('/\D+/', '', $gradeLabel), 10, '0', STR_PAD_LEFT),
        'first_name' => 'Liza',
        'last_name' => 'Gomez',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->firstOrCreate(
        ['school_year' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]
    );

    $curriculum = Curriculum::query()->firstOrCreate(
        ['name' => 'Summary Visibility Curriculum'],
        [
            'description' => 'Curriculum for enrollment summary visibility tests',
            'status' => true,
        ]
    );

    $gradeLevel = GradeLevel::query()->where('grade_label', $gradeLabel)->firstOrFail();

    $cluster = Cluster::query()->create([
        'name' => 'STEM Track '.$suffix,
    ]);

    $preferredCourse = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'Engineering Program '.$suffix,
    ]);

    $section = Section::query()->create([
        'name' => 'Emerald '.$suffix,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'room' => 'Room 201',
        'capacity' => 40,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'course_ID' => $preferredCourse->course_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('student', 'enrollment', 'cluster', 'preferredCourse', 'section');
}

test('junior high student pages hide semester cluster and preferred course', function (string $routeName) {
    $fixtures = createStudentSummaryVisibilityEnrollment('Grade 7');

    $response = $this->actingAs($fixtures['student'])->get(route($routeName));

    $response->assertOk();
    $response->assertSee('Grade 7');
    $response->assertSee($fixtures['section']->name);
    $response->assertDontSee('Semester');
    $response->assertDontSee('Cluster');
    $response->assertDontSee('Preferred Course');
    $response->assertDontSee($fixtures['cluster']->name);
    $response->assertDontSee($fixtures['preferredCourse']->name);
    $response->assertDontSee('First Semester');
})->with([
    'profile' => 'student.profile',
    'grades' => 'student.grades',
    'documents' => 'student.documents',
    'subjects' => 'student.subjects',
]);

test('senior high student pages show semester cluster and preferred course', function (string $routeName, string $gradeLabel) {
    $fixtures = createStudentSummaryVisibilityEnrollment($gradeLabel);

    $response = $this->actingAs($fixtures['student'])->get(route($routeName));

    $response->assertOk();
    $response->assertSee($gradeLabel);
    $response->assertSee($fixtures['section']->name);
    $response->assertSee('Semester');
    $response->assertSee('First Semester');
    $response->assertSee('Cluster');
    $response->assertSee('Preferred Course');
    $response->assertSee($fixtures['cluster']->name);
    $response->assertSee($fixtures['preferredCourse']->name);
})->with([
    'profile grade 11' => ['student.profile', 'Grade 11'],
    'profile grade 12' => ['student.profile', 'Grade 12'],
    'grades grade 11' => ['student.grades', 'Grade 11'],
    'grades grade 12' => ['student.grades', 'Grade 12'],
    'documents grade 11' => ['student.documents', 'Grade 11'],
    'documents grade 12' => ['student.documents', 'Grade 12'],
    'subjects grade 11' => ['student.subjects', 'Grade 11'],
    'subjects grade 12' => ['student.subjects', 'Grade 12'],
]);
