<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Enrollment $enrollment,
        public string $status,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{notification_type_ID: int, title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $this->enrollment->loadMissing('academicYear');

        $statusLabel = EnrollmentStatus::nameFor($this->status);
        $schoolYear = $this->enrollment->academicYear?->school_year;

        return NotificationType::payload(NotificationType::ENROLLMENT_STATUS, [
            'message' => $schoolYear
                ? "Your enrollment for SY {$schoolYear} is now {$statusLabel}."
                : "Your enrollment status is now {$statusLabel}.",
            'url' => route('student.dashboard', absolute: false),
        ]);
    }
}
