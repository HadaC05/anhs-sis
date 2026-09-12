<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\PlacementStatus;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('enrollments store a foreign key to the placement statuses table', function () {
    expect(Schema::hasColumn('enrollments', 'placement_status_ID'))->toBeTrue()
        ->and(Schema::hasColumn('enrollments', 'placement_test_recommended'))->toBeFalse();
});

test('placement statuses are seeded as the canonical reference list', function () {
    expect(PlacementStatus::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(PlacementStatus::slugs())
        ->and(PlacementStatus::options())->toMatchArray([
            'pending' => 'Pending',
            'age_appropriate' => 'Age Appropriate',
            'recommended' => 'Recommended',
            'passed' => 'Passed',
            'failed' => 'Failed',
            'resolved' => 'Resolved',
        ]);
});

test('new enrollments default to the pending placement status', function () {
    $enrollment = createEnrollmentWithPlacementStatus();

    expect($enrollment->placement_status)->toBe(PlacementStatus::PENDING)
        ->and($enrollment->placement_status_ID)->toBe(PlacementStatus::idFor(PlacementStatus::PENDING))
        ->and($enrollment->placement_status_label)->toBe('Pending')
        ->and($enrollment->isPlacementRecommended())->toBeFalse()
        ->and($enrollment->hasPlacementStatusMark())->toBeFalse();
});

test('enrollments store a placement status that exists in the reference table', function () {
    $enrollment = createEnrollmentWithPlacementStatus(PlacementStatus::PASSED);

    expect($enrollment->placement_status)->toBe(PlacementStatus::PASSED)
        ->and($enrollment->placement_status_ID)->toBe(PlacementStatus::idFor(PlacementStatus::PASSED))
        ->and($enrollment->placementStatus()->first()?->name)->toBe('Passed')
        ->and($enrollment->placement_status_label)->toBe('Passed')
        ->and($enrollment->hasPlacementStatusMark())->toBeTrue();
});

test('enrollments cannot use a placement status that is not in the reference table', function () {
    expect(fn () => createEnrollmentWithPlacementStatus('completed'))
        ->toThrow(InvalidArgumentException::class);
});

test('overage registration applications are marked as recommended for placement', function () {
    createPlacementRegistrationFixtures();

    $this->post(route('register.store'), placementRegistrationPayload([
        'LRN' => '333333333333',
        'email' => 'overage.learner@example.com',
        'birthdate' => '2010-01-01',
    ]))->assertRedirect(route('register'));

    $enrollment = Enrollment::query()
        ->whereHas('student', fn ($query) => $query->where('lrn', '333333333333'))
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->placement_status)->toBe(PlacementStatus::RECOMMENDED)
        ->and($enrollment->isPlacementRecommended())->toBeTrue();
});

test('age appropriate registration applications are marked as age appropriate for placement', function () {
    createPlacementRegistrationFixtures();

    $this->post(route('register.store'), placementRegistrationPayload([
        'LRN' => '444444444445',
        'email' => 'age.ok@example.com',
        'birthdate' => '2014-03-01',
    ]))->assertRedirect(route('register'));

    $enrollment = Enrollment::query()
        ->whereHas('student', fn ($query) => $query->where('lrn', '444444444445'))
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->placement_status)->toBe(PlacementStatus::AGE_APPROPRIATE)
        ->and($enrollment->placement_status_label)->toBe('Age Appropriate')
        ->and($enrollment->isPlacementRecommended())->toBeFalse()
        ->and($enrollment->hasPlacementStatusMark())->toBeFalse();
});

test('guidance counselor can update a student placement test status', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.placement.status',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $enrollment = createEnrollmentWithPlacementStatus(PlacementStatus::RECOMMENDED);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::PASSED,
        ])
        ->assertRedirect(route('guidance.enrollments.show', $enrollment))
        ->assertSessionHas('success');

    expect($enrollment->fresh()->placement_status)->toBe(PlacementStatus::PASSED)
        ->and($enrollment->fresh()->placement_status_label)->toBe('Passed');
});

test('updating placement test status keeps the current enrollment details tab', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.placement.tab',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $enrollment = createEnrollmentWithPlacementStatus(PlacementStatus::RECOMMENDED);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', ['enrollment' => $enrollment, 'step' => 'personal']))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::PASSED,
            'step' => 'personal',
        ])
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'personal',
        ]));
});

test('guidance counselor cannot save an invalid placement test status', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.placement.invalid',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $enrollment = createEnrollmentWithPlacementStatus();

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => 'completed',
        ])
        ->assertRedirect(route('guidance.enrollments.show', $enrollment))
        ->assertSessionHasErrors('placement_status');

    expect($enrollment->fresh()->placement_status)->toBe(PlacementStatus::PENDING);
});

function createPlacementRegistrationFixtures(): AcademicYear
{
    return AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function placementRegistrationPayload(array $overrides = []): array
{
    GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    return array_merge([
        'grade_level' => '7',
        'LRN' => '123456789012',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan Elementary School',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'birthdate' => '2014-03-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Male',
        'contact_no' => '+639123456789',
        'email' => 'juan.cruz@example.com',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'ip_community' => 'No',
        'four_ps_beneficiary' => 'No',
        'pwd' => 'No',
        'curr_barangay' => 'Doongan',
        'curr_municipality_city' => 'Butuan City',
        'curr_province' => 'Agusan del Norte',
        'curr_country' => 'Philippines',
        'curr_zip_code' => '8600',
        'perm_barangay' => 'Doongan',
        'perm_municipality_city' => 'Butuan City',
        'perm_province' => 'Agusan del Norte',
        'perm_country' => 'Philippines',
        'perm_zip_code' => '8600',
        'same_address' => '1',
        'father_lname' => 'Cruz',
        'father_fname' => 'Pedro',
        'mother_lname' => 'Santos',
        'mother_fname' => 'Maria',
    ], $overrides);
}

function createEnrollmentWithPlacementStatus(?string $status = null, string $lrn = '888888888887'): Enrollment
{
    $academicYear = AcademicYear::query()->firstOrCreate(
        ['school_year' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]
    );

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'lrn' => $lrn,
        'first_name' => 'Placement',
        'last_name' => 'Learner',
        'status' => 'pending',
    ]);

    $attributes = [
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::PENDING,
    ];

    if ($status !== null) {
        $attributes['placement_status'] = $status;
    }

    return Enrollment::query()->create($attributes);
}
