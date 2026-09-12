<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PlacementStatus;
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
function createStudentDashboardEnrollment(string $gradeLabel, array $overrides = []): array
{
    $suffix = strtolower(str_replace(' ', '', $gradeLabel)).($overrides['suffix'] ?? '');

    $student = Student::query()->create([
        'username' => 'student.dashboard.'.$suffix,
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => $overrides['lrn'] ?? ('12'.str_pad((string) random_int(1000000000, 1999999999), 10, '0', STR_PAD_LEFT)),
        'first_name' => $overrides['first_name'] ?? 'Liza',
        'last_name' => $overrides['last_name'] ?? 'Gomez',
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
        ['name' => 'Dashboard Test Curriculum'],
        [
            'description' => 'Curriculum for student dashboard tests',
            'status' => true,
        ]
    );

    $gradeLevel = GradeLevel::query()->where('grade_label', $gradeLabel)->firstOrFail();

    $cluster = Cluster::query()->create([
        'name' => $overrides['cluster_name'] ?? 'STEM Dashboard Cluster '.$suffix,
    ]);

    $preferredCourse = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => $overrides['course_name'] ?? 'Engineering Dashboard Course '.$suffix,
    ]);

    $section = Section::query()->create([
        'name' => $overrides['section_name'] ?? 'Emerald '.$suffix,
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
        'semester' => $overrides['semester'] ?? 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('student', 'enrollment', 'cluster', 'preferredCourse', 'section');
}

test('student dashboard uses the student portal summary layout', function () {
    $student = Student::query()->create([
        'username' => 'student.dashboard.layout',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '121200000001',
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

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('>Dashboard</h1>', false);
    $response->assertDontSee('Welcome back, Liza.');
    $response->assertDontSee('Logged in as');
    $response->assertDontSee('Open →');
    $response->assertDontSee('View and update your personal details and records.');
    $response->assertSee('Quick Access');
    $response->assertSee('Student Record');
    $response->assertSee('Enrollment snapshot');
    $response->assertSee('Placement test');
    $response->assertSee('Not enrolled');
    $response->assertSee('Placement test status will appear after you enroll.');
    $response->assertSee('Student Profile');
    $response->assertSee(route('student.profile'), false);
    $response->assertSee('Subjects');
    $response->assertSee(route('student.subjects'), false);
    $response->assertSee('grid-cols-2', false);
    $response->assertDontSee('>Enrollment</span>', false);
    $response->assertSee('data-test="student-profile-menu"', false);
    $response->assertSee('data-test="student-profile-dropdown"', false);
    $response->assertSee('data-test="student-profile-photo-placeholder"', false);
    $response->assertSee('data-test="student-account-link"', false);
    $response->assertSee(route('student.account'), false);
    $response->assertSee('data-test="student-logout-button"', false);
    $response->assertSee('text-red-600', false);
    $response->assertSee('Gomez, Liza');
    $response->assertSee('LRN 121200000001');

    $html = $response->getContent();
    expect(strpos($html, 'lg:grid-cols-12'))->toBeLessThan(strpos($html, '>Dashboard</h1>'))
        ->and(strpos($html, 'Quick Access'))->toBeLessThan(strpos($html, 'Student Record'));
});

test('junior high student dashboard hides cluster and preferred course', function (string $gradeLabel) {
    $fixtures = createStudentDashboardEnrollment($gradeLabel);

    $response = $this->actingAs($fixtures['student'])->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee($gradeLabel);
    $response->assertSee($fixtures['section']->name);
    $response->assertDontSee('Cluster');
    $response->assertDontSee('Preferred Course');
    $response->assertDontSee('Semester');
    $response->assertDontSee($fixtures['cluster']->name);
    $response->assertDontSee($fixtures['preferredCourse']->name);
    expect($fixtures['enrollment']->isSeniorHigh())->toBeFalse();
})->with(['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10']);

test('senior high student dashboard shows cluster and preferred course', function (string $gradeLabel) {
    $fixtures = createStudentDashboardEnrollment($gradeLabel);

    $response = $this->actingAs($fixtures['student'])->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee($gradeLabel);
    $response->assertSee($fixtures['section']->name);
    $response->assertSee('Cluster');
    $response->assertSee('Preferred Course');
    $response->assertSee('Semester');
    $response->assertSee($fixtures['cluster']->name);
    $response->assertSee($fixtures['preferredCourse']->name);
    expect($fixtures['enrollment']->isSeniorHigh())->toBeTrue();
})->with(['Grade 11', 'Grade 12']);

test('student dashboard shows pending placement test status for enrolled students', function () {
    $fixtures = createStudentDashboardEnrollment('Grade 7', ['suffix' => 'placementpending']);

    $response = $this->actingAs($fixtures['student'])->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('Student Record');
    $response->assertSee('Placement test');
    $response->assertSee('Pending');
    $response->assertSee('No placement test is currently required for your enrollment.');
});

test('student dashboard hides placement test information when the student is age appropriate', function () {
    $fixtures = createStudentDashboardEnrollment('Grade 7', ['suffix' => 'placementageappropriate']);
    $fixtures['enrollment']->update(['placement_status' => PlacementStatus::AGE_APPROPRIATE]);

    $response = $this->actingAs($fixtures['student'])->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('Student Record');
    $response->assertDontSee('Placement test');
    $response->assertDontSee('Age Appropriate');
    $response->assertDontSee('No placement test is required.');
});

test('student dashboard shows recommended placement test status', function () {
    $fixtures = createStudentDashboardEnrollment('Grade 7', ['suffix' => 'placementrecommended']);
    $fixtures['enrollment']->update(['placement_status' => PlacementStatus::RECOMMENDED]);

    $response = $this->actingAs($fixtures['student'])->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('Student Record');
    $response->assertSee('Placement test');
    $response->assertSee('Recommended');
    $response->assertSee('The Guidance Office recommended a placement test. Please visit the Guidance Office to confirm your schedule.');
    $response->assertSee('View next steps');
    $response->assertSee('What to do next');
    $response->assertSee('Visit or contact the Guidance Office.');
    $response->assertSee('Your test schedule is confirmed by the Guidance Office.');
});
