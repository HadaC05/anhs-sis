<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradesApproved extends Notification
{
    use Queueable;

    public function __construct(
        public TeacherSubjectAssignment $assignment,
        public int $gradeCount = 1,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{notification_type_ID: int, title: string, message: string, url: string, grade_count: int}
     */
    public function toArray(object $notifiable): array
    {
        $count = max(1, $this->gradeCount);
        $grade = $count === 1 ? 'grade was' : 'grades were';

        return NotificationType::payload(NotificationType::GRADES_APPROVED, [
            'message' => "{$count} {$grade} approved and sent to the principal for release.",
            'url' => route('teacher.sections.index', absolute: false),
            'grade_count' => $count,
        ]);
    }
}
