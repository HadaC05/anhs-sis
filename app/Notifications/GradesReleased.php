<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradesReleased extends Notification
{
    use Queueable;

    public int $subjectCount = 1;

    /** @var list<int> */
    public array $assignmentIds;

    public function __construct(public TeacherSubjectAssignment $assignment)
    {
        $this->assignmentIds = [(int) $assignment->getKey()];
    }

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
        $count = max(1, $this->subjectCount);
        $subject = $count === 1 ? 'subject has' : 'subjects have';

        return NotificationType::payload(NotificationType::GRADES_RELEASED, [
            'message' => "{$count} {$subject} been released to students.",
            'url' => route('teacher.sections.index', absolute: false),
            'subject_count' => $count,
            'assignment_ids' => $this->assignmentIds,
        ]);
    }
}
