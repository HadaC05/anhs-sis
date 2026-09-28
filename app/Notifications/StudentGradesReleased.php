<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\GradingTerm;
use App\Models\NotificationType;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentGradesReleased extends Notification
{
    use Queueable;

    public function __construct(
        public TeacherSubjectAssignment $assignment,
        public Enrollment $enrollment,
        public GradingTerm $term,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $schoolYear = $this->enrollment->academicYear?->school_year;
        $period = $this->term->label;
        $semester = $this->enrollment->semester;
        $context = $semester ? $period.' ('.ucfirst($semester).' semester)' : $period;
        $context .= $schoolYear ? ', SY '.$schoolYear : '';

        return NotificationType::payload(NotificationType::GRADES_RELEASED, [
            'message' => "All your grades for {$context} have been released. You can now view them.",
            'enrollment_ID' => $this->enrollment->getKey(),
            'term_ID' => $this->term->getKey(),
            'url' => route('student.grades', [], false),
        ]);
    }
}
