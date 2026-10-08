<?php

namespace App\Notifications;

use App\Models\NotificationType;
use Illuminate\Notifications\Notification;

class SubjectsAwaitingRelease extends Notification
{
    /**
     * @param  list<int>  $assignmentIds
     */
    public function __construct(public array $assignmentIds) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{notification_type_ID: int, title: string, message: string, url: string, subject_count: int, assignment_ids: list<int>}
     */
    public function toArray(object $notifiable): array
    {
        $count = count($this->assignmentIds);
        $message = $count === 1
            ? '1 subject grade is awaiting release to students.'
            : "{$count} subject grades are awaiting release to students.";

        return NotificationType::payload(NotificationType::SUBJECTS_AWAITING_RELEASE, [
            'message' => $message,
            'url' => route('principal.grade-releases', [
                'status' => 'approved',
                'academic_year_id' => '',
                'term_id' => '',
            ], false),
            'subject_count' => $count,
            'assignment_ids' => $this->assignmentIds,
        ]);
    }
}
