<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use App\Support\TeacherGradeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentGradesReleased extends Notification
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
            'message' => "Your grades for {$summary} have been released.",
            'url' => route('student.grades', [], false),
        ]);
    }
}
