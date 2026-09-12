<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\NotificationType;
use App\Models\PlacementStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PlacementStatusUpdated extends Notification
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

        $isRecommended = $this->status === PlacementStatus::RECOMMENDED;
        $statusLabel = PlacementStatus::nameFor($this->status);
        $schoolYear = $this->enrollment->academicYear?->school_year;
        $gradeLevelValue = $this->enrollment->grade_level;
        $gradeLabel = $gradeLevelValue !== '' ? GradeLevel::valueToLabel($gradeLevelValue) : null;
        $yearSuffix = $schoolYear ? " for SY {$schoolYear}" : '';
        $type = $isRecommended
            ? NotificationType::PLACEMENT_TEST_RECOMMENDED
            : NotificationType::PLACEMENT_STATUS;

        return NotificationType::payload($type, [
            'message' => $isRecommended
                ? 'You have been recommended to take a placement test'.$yearSuffix
                    .($gradeLabel ? " ({$gradeLabel})" : '').'.'
                : "Your placement status{$yearSuffix} is now {$statusLabel}.",
            'url' => route('student.dashboard', absolute: false),
        ]);
    }
}
