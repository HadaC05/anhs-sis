<?php

namespace App\Notifications;

use App\Models\NotificationType;
use Illuminate\Notifications\Notification;

class GradeSubmissionsDigest extends Notification
{
    public function __construct(public int $submissionCount) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return NotificationType::payload(NotificationType::GRADE_SUBMISSIONS_DIGEST, [
            'message' => $this->submissionCount === 1
                ? 'There is 1 new subject submission awaiting review.'
                : "There are {$this->submissionCount} new subject submissions awaiting review.",
            'submission_count' => $this->submissionCount,
            'url' => route('registrar.grade-approvals', ['status' => 'submitted', 'academic_year_id' => '', 'term_id' => ''], false),
        ]);
    }
}
