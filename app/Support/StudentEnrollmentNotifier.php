<?php

namespace App\Support;

use App\Mail\StudentEnrollmentStatusMail;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\Student;
use App\Notifications\EnrollmentStatusUpdated;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StudentEnrollmentNotifier
{
    public static function send(Student $student, Enrollment $enrollment, string $status): void
    {
        $enrollment->loadMissing(['academicYear', 'student']);

        $student->notify(new EnrollmentStatusUpdated($enrollment, $status));

        $email = trim((string) $student->email);

        if ($email === '') {
            return;
        }

        if (! in_array($status, [
            EnrollmentStatus::ENROLLED,
            EnrollmentStatus::TEMPORARILY_ENROLLED,
        ], true)) {
            return;
        }

        // SMTP can take longer than Render's HTTP gateway timeout. Queue this
        // work after the redirect has been sent so registration confirmation is
        // never lost even when an email provider is slow or unavailable.
        app()->terminating(function () use ($email, $student, $enrollment, $status): void {
            try {
                Mail::to($email)->send(new StudentEnrollmentStatusMail($student, $enrollment, $status));
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
