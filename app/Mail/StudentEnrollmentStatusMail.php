<?php

namespace App\Mail;

use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\Student;
use App\Support\StudentCredentials;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentEnrollmentStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Student $student,
        public Enrollment $enrollment,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === EnrollmentStatus::ENROLLED
            ? 'You are now enrolled at Agusan National High School'
            : 'You are temporarily enrolled at Agusan National High School';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $enrollmentYear = StudentCredentials::enrollmentYear($this->enrollment);

        return new Content(
            view: 'mail.student-enrollment-status',
            with: [
                'studentName' => trim($this->student->first_name.' '.$this->student->last_name) ?: 'Student',
                'statusLabel' => EnrollmentStatus::nameFor($this->status),
                'isEnrolled' => $this->status === EnrollmentStatus::ENROLLED,
                'loginUrl' => route('login'),
                'enrollmentYear' => $enrollmentYear,
                'passwordExample' => StudentCredentials::passwordFormatExample($enrollmentYear),
                'schoolYear' => $this->enrollment->academicYear?->school_year,
            ],
        );
    }
}
