<?php

namespace App\Support;

use App\Models\EnrollmentStatus;
use App\Models\GradeStatus;
use App\Models\MapehConfiguration;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\DB;

class SectionGradeSubmissionProgress
{
    /**
     * @param  iterable<TeacherSubjectAssignment>  $assignments
     * @param  array<int, list<array<string, mixed>>>  $termsByAssignment
     * @return array<int, array{submitted: int, expected: int}>
     */
    public static function forAssignments(iterable $assignments, array $termsByAssignment): array
    {
        $assignments = collect($assignments);
        if ($assignments->isEmpty()) {
            return [];
        }
        $sections = $assignments->map(fn ($assignment) => $assignment->section)->filter()->unique('section_ID');
        $mapehConfigurations = MapehConfiguration::query()
            ->with(['components.curriculumSubject.subject', 'parentSubject.subject'])
            ->whereIn('curriculum_grade_level_ID', $sections->pluck('curriculum_grade_level_ID')->filter()->unique())
            ->whereIn('SY_ID', $sections->pluck('SY_ID')->filter()->unique())
            ->get()
            ->keyBy(fn (MapehConfiguration $configuration): string => $configuration->curriculum_grade_level_ID.':'.$configuration->SY_ID);
        foreach ($sections as $section) {
            $section->setRelation('mapehConfiguration', $mapehConfigurations->get($section->curriculum_grade_level_ID.':'.$section->SY_ID));
        }
        $assignments = $assignments->reject(fn ($assignment) => MapehGrades::inputBlocked($assignment));

        $roster = DB::table('teacher_subject_assignments as assignments')
            ->join('enrollments', function ($join): void {
                $join->on('enrollments.section_ID', '=', 'assignments.section_ID')
                    ->on('enrollments.SY_ID', '=', 'assignments.SY_ID');
            })
            ->join('student_subjects as roster', function ($join): void {
                $join->on('roster.enrollment_ID', '=', 'enrollments.enrollment_ID')
                    ->on('roster.subject_ID', '=', 'assignments.subject_ID');
            })
            ->whereIn('assignments.assignment_ID', $assignments->pluck('assignment_ID'))
            ->whereIn('enrollments.enrollment_status_ID', EnrollmentStatus::activeIds());

        $studentSubjectColumn = $roster->getGrammar()->wrap('roster.student_subject_ID');

        $rosterCounts = (clone $roster)
            ->select('assignments.assignment_ID')
            ->selectRaw("COUNT(DISTINCT {$studentSubjectColumn}) as total")
            ->groupBy('assignments.assignment_ID')
            ->pluck('total', 'assignment_ID');

        $submitted = (clone $roster)
            ->join('student_subject_grades as grades', function ($join): void {
                $join->on('grades.student_subject_ID', '=', 'roster.student_subject_ID')
                    ->on('grades.assignment_ID', '=', 'assignments.assignment_ID');
            })
            ->whereIn('grades.grade_status_ID', GradeStatus::idsFor(GradeStatus::teacherLockedSlugs()))
            ->select('assignments.assignment_ID', 'grades.term_ID')
            ->selectRaw("COUNT(DISTINCT {$studentSubjectColumn}) as total")
            ->groupBy('assignments.assignment_ID', 'grades.term_ID')
            ->get()->groupBy('assignment_ID');

        $progress = [];
        foreach ($assignments as $assignment) {
            $sectionId = $assignment->section_ID;
            $progress[$sectionId] ??= ['submitted' => 0, 'expected' => 0];
            $terms = $termsByAssignment[$assignment->assignment_ID] ?? [];
            $progress[$sectionId]['expected']++;
            $rosterCount = (int) $rosterCounts->get($assignment->assignment_ID, 0);
            $termCounts = $submitted->get($assignment->assignment_ID, collect())->pluck('total', 'term_ID');
            $assignment->subject_students_count = $rosterCount;
            $activeTerm = $assignment->active_term_summary;
            $assignment->active_submitted_count = $activeTerm ? (int) $termCounts->get($activeTerm['term_ID'], 0) : 0;
            $fullySubmitted = $rosterCount > 0 && count($terms) > 0;
            foreach ($terms as $term) {
                if ((int) $termCounts->get($term['term_ID'], 0) < $rosterCount) {
                    $fullySubmitted = false;
                    break;
                }
            }
            if ($fullySubmitted) {
                $progress[$sectionId]['submitted']++;
            }
        }

        foreach ($sections as $section) {
            if ($configuration = \App\Models\MapehConfiguration::forSection($section)) {
                $missing = $configuration->components->pluck('curriculumSubject.subject_ID')
                    ->diff($assignments->where('section_ID', $section->section_ID)->pluck('subject_ID'))->count();
                $progress[$section->section_ID] ??= ['submitted' => 0, 'expected' => 0];
                $progress[$section->section_ID]['expected'] += $missing;
            }
        }

        return $progress;
    }
}
