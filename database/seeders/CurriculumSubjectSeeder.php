<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumSubjectSeeder extends Seeder
{
    /**
     * Seed the subjects offered by every curriculum-grade-level offering.
     *
     * The offering already owns the grade, semester, and track context, so a
     * curriculum subject only needs its offering ID and subject ID.
     */
    public function run(): void
    {
        Curriculum::query()
            ->with(['gradeLevel', 'gradingSemester', 'cluster'])
            ->orderBy('curriculum_ID')
            ->each(function (Curriculum $curriculum): void {
                $subjectCodes = $this->subjectCodesFor($curriculum);
                $subjectIds = Subject::query()
                    ->whereIn('code', $subjectCodes)
                    ->pluck('subject_ID')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                $this->removeObsoleteUngradedAssignments($curriculum, $subjectIds);

                foreach ($subjectIds as $subjectId) {
                    CurriculumSubject::query()->firstOrCreate([
                        'curriculum_grade_level_ID' => $curriculum->curriculum_ID,
                        'subject_ID' => $subjectId,
                    ]);
                }
            });
    }

    /**
     * Remove obsolete curriculum offerings unless their subject already has grade
     * history. Learner elective selections live outside this table.
     *
     * @param  list<int>  $desiredSubjectIds
     */
    private function removeObsoleteUngradedAssignments(Curriculum $curriculum, array $desiredSubjectIds): void
    {
        CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)
            ->when($desiredSubjectIds !== [], fn ($query) => $query->whereNotIn('subject_ID', $desiredSubjectIds))
            ->orderBy('curr_subj_ID')
            ->each(function (CurriculumSubject $assignment): void {
                $teacherAssignmentIds = DB::table('teacher_subject_assignments')
                    ->where('subject_ID', $assignment->subject_ID)
                    ->pluck('assignment_ID');

                if ($teacherAssignmentIds->isNotEmpty()
                    && DB::table('student_subject_grades')->whereIn('assignment_ID', $teacherAssignmentIds)->exists()) {
                    return;
                }

                DB::table('teacher_subject_assignments')->whereIn('assignment_ID', $teacherAssignmentIds)->delete();
                $assignment->delete();
            });
    }

    /** @return list<string> */
    private function subjectCodesFor(Curriculum $curriculum): array
    {
        $grade = (int) preg_replace('/\D+/', '', (string) $curriculum->gradeLevel?->grade_label);

        if ($grade >= 7 && $grade <= 10) {
            return array_map(
                fn (string $prefix): string => $prefix.$grade,
                array_keys(SubjectSeeder::JUNIOR_HIGH_AREAS),
            );
        }

        if ($grade < 11 || $grade > 12) {
            return [];
        }

        $isGradeEleven = $grade === 11;

        // Grade 11 core subjects continue through both semesters. Each
        // semester offering references the same subject catalog records so
        // grades remain semester-specific without duplicating subjects.
        // Electives are attached to an individual learner after enrollment,
        // because selections may come from any cluster.
        return $isGradeEleven
            ? array_merge(
                array_keys(SubjectSeeder::SENIOR_HIGH_CORE_SUBJECTS),
                array_keys(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS),
            )
            : [];
    }
}
