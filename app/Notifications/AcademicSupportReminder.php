<?php

namespace App\Notifications;

use App\Models\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcademicSupportReminder extends Notification
{
    public function __construct(public int $enrollmentId, public string $periodLabel, public string $schoolYear, public string $periodKey, public int $failedSubjectCount) {}

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Academic support reminder')
            ->view('mail.academic-support-reminder', [
                'studentName' => $notifiable->first_name,
                'periodLabel' => $this->periodLabel,
                'schoolYear' => $this->schoolYear,
                'failedSubjectCount' => $this->failedSubjectCount,
                'gradesUrl' => route('student.grades'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return NotificationType::payload(NotificationType::ACADEMIC_SUPPORT, [
            'message' => "Your adviser would like to discuss academic support for {$this->periodLabel}, SY {$this->schoolYear}. Please contact your adviser to review your progress and plan your next steps.",
            'enrollment_ID' => $this->enrollmentId,
            'period_key' => $this->periodKey,
            'url' => route('student.grades', [], false),
        ]);
    }
}
