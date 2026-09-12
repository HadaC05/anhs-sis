<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('enrollments store a foreign key to the enrollment statuses table', function () {
    expect(Schema::hasColumn('enrollments', 'enrollment_status_ID'))->toBeTrue()
        ->and(Schema::hasColumn('enrollments', 'enrollment_status'))->toBeFalse();
});

test('enrollment statuses are seeded as the canonical reference list', function () {
    expect(EnrollmentStatus::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(EnrollmentStatus::slugs())
        ->and(EnrollmentStatus::options())->toMatchArray([
            'pending' => 'Pending',
            'enrolled' => 'Enrolled',
            'temporarily_enrolled' => 'Temporarily Enrolled',
            'transferred_out' => 'Transferred Out',
            'dropped_out' => 'Dropped Out',
            'withdrawn' => 'Withdrawn',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
        ]);
});

test('enrollments store a status that exists in the enrollment statuses table', function () {
    $enrollment = createEnrollmentWithStatus(EnrollmentStatus::PENDING);

    expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::PENDING)
        ->and($enrollment->enrollment_status_ID)->toBe(EnrollmentStatus::idFor(EnrollmentStatus::PENDING))
        ->and($enrollment->enrollmentStatus()->first()?->name)->toBe('Pending')
        ->and($enrollment->enrollment_status_label)->toBe('Pending');
});

test('enrollments cannot use a status that is not in the reference table', function () {
    expect(fn () => createEnrollmentWithStatus('completed'))
        ->toThrow(InvalidArgumentException::class);
});

test('guidance enrollment filters list every status from the reference table', function () {
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.statuses',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('guidance.enrollments.index'));

    $response->assertOk()
        ->assertSee('value="transferred_out"', false)
        ->assertSee('value="dropped_out"', false)
        ->assertSee('value="no_show"', false)
        ->assertDontSee('value="completed"', false);

    foreach (EnrollmentStatus::options() as $label) {
        $response->assertSee($label, false);
    }
});

function createEnrollmentWithStatus(string $status): Enrollment
{
    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'lrn' => '888888888888',
        'first_name' => 'Status',
        'last_name' => 'Learner',
        'status' => 'pending',
    ]);

    return Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => $status,
    ]);
}
