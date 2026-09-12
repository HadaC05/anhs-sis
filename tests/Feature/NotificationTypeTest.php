<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\NotificationType;
use App\Models\PlacementStatus;
use App\Models\Student;
use App\Notifications\EnrollmentStatusUpdated;
use App\Notifications\PlacementStatusUpdated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('notification types are seeded as the canonical reference list', function () {
    expect(Schema::hasTable('notification_types'))->toBeTrue()
        ->and(Schema::hasColumn('notifications', 'notification_type_ID'))->toBeTrue()
        ->and(NotificationType::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(NotificationType::slugs())
        ->and(NotificationType::options())->toMatchArray([
            'enrollment_status' => 'Enrollment status updated',
            'placement_status' => 'Placement status updated',
            'placement_test_recommended' => 'Placement test recommended',
            'grades_approved' => 'Grades approved',
            'grades_released' => 'Grades released',
            'grading_term_opened' => 'Grading term opened',
            'grades_unlocked' => 'Grades unlocked',
            'document_status_updated' => 'Document status updated',
        ]);
});

test('in-app notifications store a foreign key to the notification types table', function () {
    $student = createNotificationTypeStudent();
    $enrollment = createNotificationTypeEnrollment($student);

    $student->notify(new EnrollmentStatusUpdated($enrollment, EnrollmentStatus::ENROLLED));

    $notification = $student->notifications()->with('notificationType')->first();

    expect($notification?->notification_type_ID)->toBe(NotificationType::idFor(NotificationType::ENROLLMENT_STATUS))
        ->and($notification?->notificationType?->slug)->toBe(NotificationType::ENROLLMENT_STATUS)
        ->and($notification?->typeName())->toBe('Enrollment status updated')
        ->and($notification?->data['notification_type_ID'] ?? null)->toBe(NotificationType::idFor(NotificationType::ENROLLMENT_STATUS));
});

test('placement test recommendations use the dedicated notification type', function () {
    $student = createNotificationTypeStudent();
    $enrollment = createNotificationTypeEnrollment($student);

    $student->notify(new PlacementStatusUpdated($enrollment, PlacementStatus::RECOMMENDED));
    $student->notify(new PlacementStatusUpdated($enrollment, PlacementStatus::PASSED));

    $notifications = $student->notifications()->with('notificationType')->get();

    expect($notifications)->toHaveCount(2)
        ->and($notifications->pluck('notificationType.slug')->all())
        ->toEqualCanonicalizing([
            NotificationType::PLACEMENT_TEST_RECOMMENDED,
            NotificationType::PLACEMENT_STATUS,
        ]);
});

test('unknown notification types cannot be stored', function () {
    expect(fn () => NotificationType::requireIdFor('unknown'))
        ->toThrow(\InvalidArgumentException::class);
});

function createNotificationTypeStudent(): Student
{
    return Student::query()->create([
        'username' => 'student.notify.type.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Nora',
        'last_name' => 'Diaz',
        'status' => 'active',
    ]);
}

function createNotificationTypeEnrollment(Student $student): Enrollment
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

    return Enrollment::query()->create([
        'student_ID' => $student->id,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::ENROLLED,
    ]);
}
