<?php

namespace App\Support;

use App\Models\Student;
use App\Models\TeacherSubjectAssignment;
use App\Notifications\StudentGradesReleased;
use Illuminate\Support\Collection;

class StudentGradeNotifier
{
    /**
     * @param  Collection<int, Student>  $students
     */
    public static function released(TeacherSubjectAssignment $assignment, Collection $students): void
    {
        $students
            ->unique(fn (Student $student): int => $student->getKey())
            ->each(fn (Student $student): mixed => $student->notify(new StudentGradesReleased($assignment)));
    }
}
