<?php

namespace App\Mail;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Support\PlacementAssessmentAdvisor;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentPlacementTestMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Student $student,
        public Enrollment $enrollment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Placement test recommended at Agusan National High School',
        );
    }

    public function content(): Content
    {
        $this->enrollment->loadMissing(['academicYear', 'gradeLevel', 'student']);

        $recommendation = PlacementAssessmentAdvisor::forEnrollment($this->enrollment)
            ?? PlacementAssessmentAdvisor::assessmentForEnrollment($this->enrollment);
        $gradeLevelValue = $recommendation['grade_level'] ?? null;
        $gradeLabel = $this->enrollment->gradeLevel?->grade_label
            ?? ($gradeLevelValue ? GradeLevel::valueToLabel($gradeLevelValue) : null);

        return new Content(
            view: 'mail.student-placement-test',
            with: [
                'studentName' => trim($this->student->first_name.' '.$this->student->last_name) ?: 'Student',
                'schoolYear' => $this->enrollment->academicYear?->school_year,
                'gradeLabel' => $gradeLabel,
                'age' => $recommendation['age'] ?? null,
                'expectedRange' => isset($recommendation['minimum_age'], $recommendation['maximum_age'])
                    ? $recommendation['minimum_age'].'–'.$recommendation['maximum_age']
                    : null,
                'summary' => $recommendation['summary'] ?? null,
                'detail' => $recommendation['detail'] ?? null,
                'loginUrl' => route('login'),
            ],
        );
    }
}
