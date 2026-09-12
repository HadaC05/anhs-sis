<?php

namespace App\Support;

use App\Models\TeacherSubjectAssignment;
use App\Notifications\GradesApproved;
use App\Notifications\GradesReleased;
use App\Notifications\GradesUnlocked;
use Illuminate\Notifications\Notification;

class TeacherGradeNotifier
{
    public static function approved(TeacherSubjectAssignment $assignment): void
    {
        self::notifyTeacher($assignment, new GradesApproved($assignment));
    }

    public static function released(TeacherSubjectAssignment $assignment): void
    {
        self::notifyTeacher($assignment, new GradesReleased($assignment));
    }

    public static function unlocked(TeacherSubjectAssignment $assignment): void
    {
        self::notifyTeacher($assignment, new GradesUnlocked($assignment));
    }

    public static function assignmentSummary(TeacherSubjectAssignment $assignment): string
    {
        $assignment->loadMissing(['section', 'curriculumSubject.subject']);

        $subject = $assignment->curriculumSubject?->subject;
        $code = trim((string) ($subject?->code ?? ''));
        $title = trim((string) ($subject?->title ?? ''));
        $subjectName = match (true) {
            $code !== '' && $title !== '' => "{$code} - {$title}",
            $title !== '' => $title,
            $code !== '' => $code,
            default => 'your subject',
        };
        $sectionName = trim((string) ($assignment->section?->name ?? ''));

        return $sectionName !== '' ? "{$subjectName} ({$sectionName})" : $subjectName;
    }

    private static function notifyTeacher(TeacherSubjectAssignment $assignment, Notification $notification): void
    {
        $assignment->loadMissing('staff');

        $teacher = $assignment->staff;

        if (! $teacher) {
            return;
        }

        $teacher->notify($notification);
    }
}
