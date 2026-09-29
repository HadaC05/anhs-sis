<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Sf5ReportBuilder
{
    public static function rows(Section $section, ?Collection $selectedEnrollments = null): Collection
    {
        $enrollments = $selectedEnrollments ?? Enrollment::query()->with(['student', 'studentSubjects'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())->get();

        $enrollments->loadMissing(['student', 'studentSubjects']);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages(['sf5' => 'There are no active learners in this advisory class.']);
        }

        if ($enrollments->contains(fn ($enrollment) => ! in_array(strtolower($enrollment->student?->sex ?? ''), ['male', 'female'], true))) {
            throw ValidationException::withMessages(['sf5' => 'Complete the sex field for every learner before generating SF 5.']);
        }

        $assignments = TeacherSubjectAssignment::query()->with(['curriculumSubject.subject', 'curriculumSubject.gradingSemester'])
            ->where('section_ID', $section->section_ID)->where('SY_ID', $section->SY_ID)->get();
        $periodsBySemester = [];
        foreach ($assignments as $assignment) {
            $key = GradingTerm::isSeniorHighSection($section) ? ($assignment->curriculumSubject?->semester ?? '') : 'jhs';
            $periodsBySemester[$key] ??= $key === 'jhs' ? GradingTerm::configuredPeriods() : GradingTerm::seniorHighPeriods($assignment->curriculumSubject?->semester);
        }
        $grades = StudentSubjectGrade::query()->with(['studentSubject.enrollment.gradingSemester', 'term'])
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID')))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->where('grade_status_ID', GradeStatus::idFor(GradeStatus::RELEASED))->get()
            ->groupBy(fn ($grade) => $grade->studentSubject->enrollment_ID);

        return $enrollments->map(function (Enrollment $enrollment) use ($section, $assignments, $grades, $periodsBySemester): array {
            $student = $enrollment->student;
            $byAssignment = $grades->get($enrollment->enrollment_ID, collect())->groupBy('assignment_ID');
            $subjectIds = $enrollment->studentSubjects->pluck('curr_subj_ID');
            $learnerAssignments = $assignments->whereIn('curr_subj_ID', $subjectIds);
            $finals = [];
            $complete = $subjectIds->isNotEmpty() && $subjectIds->diff($learnerAssignments->pluck('curr_subj_ID'))->isEmpty();
            foreach ($learnerAssignments as $assignment) {
                $key = GradingTerm::isSeniorHighSection($section) ? ($assignment->curriculumSubject?->semester ?? '') : 'jhs';
                $periods = $periodsBySemester[$key];
                $byPeriod = $byAssignment->get($assignment->assignment_ID, collect())->keyBy('grading_period');
                $values = collect($periods)->map(fn ($period) => $byPeriod->get($period['key'])?->numeric_grade);
                if ($values->isEmpty() || $values->containsStrict(null)) {
                    $complete = false;

                    continue;
                }
                $title = $assignment->curriculumSubject?->subject?->title ?? 'Subject';
                $finals[] = ['title' => $title, 'grade' => (int) round($values->map(fn ($value) => round((float) $value))->avg())];
            }

            // SF 9 counts MAPEH as one learning area, rather than four components.
            if (! GradingTerm::isSeniorHighSection($section)) {
                $components = collect($finals)->filter(fn ($row) => in_array(LearnerPermanentRecordBuilder::subjectSlot($row['title']), ['music', 'arts', 'pe', 'health'], true));
                $finals = collect($finals)->reject(fn ($row) => in_array(LearnerPermanentRecordBuilder::subjectSlot($row['title']), ['music', 'arts', 'pe', 'health'], true));
                if ($components->isNotEmpty() && ! $finals->contains(fn ($row) => LearnerPermanentRecordBuilder::subjectSlot($row['title']) === 'mapeh')) {
                    $finals->push(['title' => 'MAPEH', 'grade' => (int) round($components->avg('grade'))]);
                }
                $finals = $finals->all();
            }

            $failed = collect($finals)->where('grade', '<', 75)->pluck('title');

            return [
                'lrn' => (string) $student->lrn,
                'name' => trim($student->last_name.', '.$student->first_name.' '.$student->middle_name),
                'sex' => strtolower($student->sex),
                'average' => $complete ? (int) round(collect($finals)->avg('grade')) : null,
                'action' => ! $complete ? '' : ($failed->isEmpty() ? 'PROMOTED' : ($failed->count() <= 2 ? 'CONDITIONAL' : 'RETAINED')),
                'failed' => $complete ? $failed->implode(', ') : 'Pending complete released grades',
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
}
