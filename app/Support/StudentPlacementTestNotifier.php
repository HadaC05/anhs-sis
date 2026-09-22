<?php

namespace App\Support;

use App\Mail\StudentPlacementTestMail;
use App\Models\Enrollment;
use App\Models\PlacementStatus;
use App\Models\Student;
use App\Notifications\PlacementStatusUpdated;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StudentPlacementTestNotifier
{
    public static function send(Student $student, Enrollment $enrollment): void
    {
        $email = trim((string) $student->email);

        if ($email === '') {
            return;
        }

        $enrollment->loadMissing(['academicYear', 'gradeLevel', 'student']);

        // Do not let a slow SMTP connection delay the enrollment response.
        app()->terminating(function () use ($email, $student, $enrollment): void {
            try {
                Mail::to($email)->send(new StudentPlacementTestMail($student, $enrollment));
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    public static function sendIfNewlyRecommended(Enrollment $enrollment, ?string $previousStatus = null): void
    {
        $enrollment->loadMissing(['student', 'academicYear', 'gradeLevel']);

        $student = $enrollment->student;

        if (! $student) {
            return;
        }

        $currentStatus = $enrollment->placement_status;

        if ($previousStatus === $currentStatus) {
            return;
        }

        $isInitialNonRecommendation = $previousStatus === null && $currentStatus !== PlacementStatus::RECOMMENDED;

        if (! $isInitialNonRecommendation && $currentStatus !== '') {
            $student->notify(new PlacementStatusUpdated($enrollment, $currentStatus));
        }

        if ($previousStatus === PlacementStatus::RECOMMENDED) {
            return;
        }

        if (! $enrollment->isPlacementRecommended()) {
            return;
        }

        self::send($student, $enrollment);
    }
}
