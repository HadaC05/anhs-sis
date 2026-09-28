<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\GradeStatus;
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
            ->each(function (Student $student) use ($assignment): void {
                $enrollments = Enrollment::query()
                    ->where('student_ID', $student->getKey())
                    ->where('SY_ID', $assignment->SY_ID)
                    ->where('section_ID', $assignment->section_ID)
                    ->get();

                foreach ($enrollments as $enrollment) {
                    $terms = $enrollment->grades()
                        ->where('assignment_ID', $assignment->getKey())
                        ->whereStatus(GradeStatus::RELEASED)
                        ->with('term')->get()->pluck('term')->filter()->unique('term_ID');

                    foreach ($terms as $term) {
                        // Missing grades must also prevent the all-subjects notification.
                        $subjects = $enrollment->studentSubjects();
                        if (! $subjects->exists() || $enrollment->studentSubjects()
                            ->whereDoesntHave('grades', fn ($query) => $query
                                ->where('term_ID', $term->getKey())
                                ->whereNotNull('numeric_grade')
                                ->whereStatus(GradeStatus::RELEASED))->exists()) {
                            continue;
                        }

                        $alreadyNotified = $student->notifications()
                            ->where('type', StudentGradesReleased::class)
                            ->where('data->enrollment_ID', $enrollment->getKey())
                            ->where('data->term_ID', $term->getKey())->exists();

                        if (! $alreadyNotified) {
                            $student->notify(new StudentGradesReleased($assignment, $enrollment, $term));
                        }
                    }
                }
            });
    }
}
