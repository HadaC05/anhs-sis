<?php

namespace App\Notifications;

use App\Models\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradingTermOpened extends Notification
{
    use Queueable;

    public function __construct(public string $label) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return NotificationType::payload(NotificationType::GRADING_TERM_OPENED, [
            'message' => "{$this->label} is now open for grade entry.",
            'url' => route('teacher.sections.index', absolute: false),
        ]);
    }
}
