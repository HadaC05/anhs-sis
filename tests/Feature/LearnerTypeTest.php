<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('enrollments store a foreign key to the learner types table', function () {
    expect(Schema::hasColumn('enrollments', 'learner_type_ID'))->toBeTrue()
        ->and(Schema::hasColumn('enrollments', 'learner_type'))->toBeFalse();
});

test('learner types are seeded as regular transferee and balik aral', function () {
    expect(LearnerType::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(LearnerType::slugs())
        ->and(LearnerType::options())->toMatchArray([
            'regular' => 'Regular',
            'transferee' => 'Transferee',
            'balik_aral' => 'Balik Aral',
        ]);
});

test('enrollments store a learner type that exists in the learner types table', function () {
    $enrollment = createEnrollmentWithLearnerType(LearnerType::TRANSFEREE);

    expect($enrollment->learner_type)->toBe(LearnerType::TRANSFEREE)
        ->and($enrollment->learner_type_ID)->toBe(LearnerType::idFor(LearnerType::TRANSFEREE))
        ->and($enrollment->learnerType()->first()?->name)->toBe('Transferee')
        ->and($enrollment->learner_type_label)->toBe('Transferee');
});

test('legacy returnee learner type is stored as balik aral', function () {
    $enrollment = createEnrollmentWithLearnerType('returnee');

    expect($enrollment->learner_type)->toBe(LearnerType::BALIK_ARAL)
        ->and($enrollment->learner_type_ID)->toBe(LearnerType::idFor(LearnerType::BALIK_ARAL))
        ->and($enrollment->learner_type_label)->toBe('Balik Aral')
        ->and($enrollment->requiresPreviousSchoolDetails())->toBeTrue();
});

test('enrollments cannot use a learner type that is not in the reference table', function () {
    expect(fn () => createEnrollmentWithLearnerType('new'))
        ->toThrow(InvalidArgumentException::class);
});

test('guidance enrollment filters list every learner type from the reference table', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.learner.types',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('guidance.enrollments.index'));

    $response->assertOk()
        ->assertSee('value="regular"', false)
        ->assertSee('value="transferee"', false)
        ->assertSee('value="balik_aral"', false)
        ->assertDontSee('value="returnee"', false);

    foreach (LearnerType::options() as $label) {
        $response->assertSee($label, false);
    }
});

test('guidance can filter enrollments by learner type including the returnee alias', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.learner.filter',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $regular = createEnrollmentWithLearnerType(LearnerType::REGULAR, '111111111111', 'Zamora');
    $balikAral = createEnrollmentWithLearnerType(LearnerType::BALIK_ARAL, '222222222222', 'Quirino');

    $this->actingAs($user)
        ->get(route('guidance.enrollments.index', [
            'status' => 'all',
            'learner_type' => LearnerType::BALIK_ARAL,
        ]))
        ->assertOk()
        ->assertSee('Quirino')
        ->assertDontSee('Zamora');

    $this->actingAs($user)
        ->get(route('guidance.enrollments.index', [
            'status' => 'all',
            'learner_type' => 'returnee',
        ]))
        ->assertOk()
        ->assertSee('Quirino')
        ->assertDontSee('Zamora');

    $this->actingAs($user)
        ->get(route('guidance.enrollments.index', [
            'status' => 'all',
            'learner_type' => LearnerType::REGULAR,
        ]))
        ->assertOk()
        ->assertSee('Zamora')
        ->assertDontSee('Quirino');

    expect($regular->learner_type)->toBe(LearnerType::REGULAR)
        ->and($balikAral->learner_type)->toBe(LearnerType::BALIK_ARAL);
});

test('registration form uses the canonical learner type values', function () {
    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('value="regular"', false)
        ->assertSee('value="transferee"', false)
        ->assertSee('value="balik_aral"', false)
        ->assertSee('Balik Aral')
        ->assertDontSee('value="returnee"', false);
});

function createEnrollmentWithLearnerType(string $learnerType, string $lrn = '888888888881', string $lastName = 'Learner'): Enrollment
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
        'first_name' => 'Ana',
        'last_name' => $lastName,
        'status' => 'pending',
    ]);

    return Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => $learnerType,
        'enrollment_status' => EnrollmentStatus::PENDING,
    ]);
}
