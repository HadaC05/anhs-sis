<?php

namespace App\Jobs;

use App\Models\GradeStatus;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use App\Support\StudentGradeNotifier;
use App\Support\TeacherGradeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyReleasedGrades implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $assignmentId, public int $releasedCount) {}

    public function handle(): void
    {
        $assignment = TeacherSubjectAssignment::query()->find($this->assignmentId);

        if (! $assignment) {
            return;
        }

        TeacherGradeNotifier::released($assignment, $this->releasedCount);

        $students = StudentSubjectGrade::query()
            ->where('assignment_ID', $this->assignmentId)
            ->whereStatus(GradeStatus::RELEASED)
            ->with('studentSubject.enrollment.student')
            ->get()
            ->map(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment?->student)
            ->filter()
            ->unique(fn ($student) => $student->getKey())
            ->values();

        StudentGradeNotifier::released($assignment, $students);
    }
}
