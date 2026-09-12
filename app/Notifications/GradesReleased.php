<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use App\Support\TeacherGradeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradesReleased extends Notification
{
    use Queueable;

    public function __construct(public TeacherSubjectAssignment $assignment) {}

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
        $summary = TeacherGradeNotifier::assignmentSummary($this->assignment);

        return NotificationType::payload(NotificationType::GRADES_RELEASED, [
            'message' => "Grades for {$summary} have been released to students.",
            'url' => route('teacher.sections.show', $this->assignment, false),
        ]);
    }
}
