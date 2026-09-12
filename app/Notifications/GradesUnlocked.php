<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use App\Support\TeacherGradeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradesUnlocked extends Notification
{
    use Queueable;

    public function __construct(public TeacherSubjectAssignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $summary = TeacherGradeNotifier::assignmentSummary($this->assignment);

        return NotificationType::payload(NotificationType::GRADES_UNLOCKED, [
            'message' => "Grades for {$summary} were unlocked for revision and resubmission.",
            'url' => route('teacher.sections.show', $this->assignment, false),
        ]);
    }
}
