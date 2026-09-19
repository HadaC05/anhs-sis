<?php

use App\Livewire\NotificationDropdown;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Notifications\EnrollmentStatusUpdated;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('student dashboard shows the notification bell dropdown', function () {
    $student = createNotificationDropdownStudent();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="notification-bell"', false)
        ->assertSee('data-test="notification-dropdown"', false)
        ->assertSee('You have no notifications yet.');
});

test('teacher dashboard shows the notification bell dropdown', function () {
    $teacher = createNotificationDropdownTeacher();

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('data-test="notification-bell"', false)
        ->assertSee('data-test="notification-dropdown"', false);
});

test('the notification dropdown lists unread student notifications and can mark them as read', function () {
    $student = createNotificationDropdownStudent();
    $enrollment = createNotificationDropdownEnrollment($student);

    $student->notify(new EnrollmentStatusUpdated($enrollment, EnrollmentStatus::ENROLLED));

    Livewire::actingAs($student)
        ->test(NotificationDropdown::class)
        ->assertSee('Enrollment status updated')
        ->assertSee('now Enrolled')
        ->assertSee('data-test="notification-unread-count"', false)
        ->assertSee('data-test="notification-mark-read"', false)
        ->call('markAllAsRead')
        ->assertDontSee('data-test="notification-unread-count"', false)
        ->assertDontSee('data-test="notification-mark-read"', false)
        ->assertSee('Enrollment status updated');

    expect($student->unreadNotifications()->count())->toBe(0)
        ->and($student->notifications()->count())->toBe(1);
});

test('marking a single notification as read leaves other unread items', function () {
    $student = createNotificationDropdownStudent();
    $enrollment = createNotificationDropdownEnrollment($student);

    $student->notify(new EnrollmentStatusUpdated($enrollment, EnrollmentStatus::TEMPORARILY_ENROLLED));
    $student->notify(new EnrollmentStatusUpdated($enrollment, EnrollmentStatus::ENROLLED));

    $firstNotificationId = $student->notifications()->oldest()->first()?->id;

    expect($firstNotificationId)->not->toBeNull();

    Livewire::actingAs($student)
        ->test(NotificationDropdown::class)
        ->call('markAsRead', $firstNotificationId)
        ->assertSee('data-test="notification-unread-count"', false);

    expect($student->unreadNotifications()->count())->toBe(1);
});

function createNotificationDropdownStudent(): Student
{
    return Student::query()->create([
        'username' => 'student.notify.ui.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Nina',
        'last_name' => 'Lopez',
        'status' => 'active',
    ]);
}

function createNotificationDropdownTeacher(): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'teacher']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.notify.ui.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Marco',
        'last_name' => 'Reyes',
        'status' => 'active',
    ]);
}

function createNotificationDropdownEnrollment(Student $student): Enrollment
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
