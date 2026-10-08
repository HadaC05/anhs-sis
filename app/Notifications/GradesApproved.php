<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradesApproved extends Notification
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
        $subject = $count === 1 ? 'subject was' : 'subjects were';

        return NotificationType::payload(NotificationType::GRADES_APPROVED, [
            'message' => "{$count} {$subject} approved and sent to the principal for release.",
            'url' => route('teacher.sections.index', absolute: false),
            'subject_count' => $count,
            'assignment_ids' => $this->assignmentIds,
        ]);
    }
}
