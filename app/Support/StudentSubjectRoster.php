<?php

namespace App\Support;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\StudentSubject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\DB;

class StudentSubjectRoster
{
    /** Create the immutable subject roster for an enrollment's selected offering. */
    public static function sync(Enrollment $enrollment, ?array $electiveSubjectIds = null): void
    {
        $curriculumId = (int) $enrollment->curriculum_grade_level_ID;
        $electiveSubjectIds = $electiveSubjectIds === null
            ? null
            : array_values(array_unique(array_map('intval', $electiveSubjectIds)));

        $curriculum = Curriculum::query()->with(['gradeLevel', 'gradingSemester'])->find($curriculumId);
        $grade = (int) preg_replace('/\D+/', '', (string) $curriculum?->gradeLevel?->grade_label);
        $includeCore = $grade === 11;

        $offerings = CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $curriculumId)
            ->when($electiveSubjectIds !== null, function ($query) use ($grade, $includeCore): void {
                $query->where(function ($subjects) use ($grade, $includeCore): void {
                    if ($grade >= 7 && $grade <= 10) {
                        $subjects->whereHas('subject.subjectType', fn ($type) => $type->where('key', 'general'));
                    } elseif ($includeCore) {
                        $subjects->whereHas('subject.subjectType', fn ($type) => $type->where('key', 'core'));
                    } else {
                        $subjects->whereRaw('1 = 0');
                    }

                });
            });

        $subjectIds = $offerings
            ->pluck('subject_ID')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($electiveSubjectIds !== null) {
            $subjectIds = array_values(array_unique([...$subjectIds, ...$electiveSubjectIds]));
        }

        if ($electiveSubjectIds !== null) {
            StudentSubject::query()
                ->where('enrollment_ID', $enrollment->enrollment_ID)
                ->whereDoesntHave('grades')
                ->whereNotIn('subject_ID', $subjectIds)
                ->delete();
        }

        $mapeh = \App\Models\MapehConfiguration::query()->where('curriculum_grade_level_ID', $curriculumId)
            ->where('SY_ID', $enrollment->SY_ID)->first();
        if ($mapeh) {
            $excludedSubjectIds = CurriculumSubject::query()
                ->whereIn('curr_subj_ID', [$mapeh->parent_curr_subj_ID, ...$mapeh->inactiveComponentIds()])
                ->pluck('subject_ID')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            $subjectIds = array_values(array_diff($subjectIds, $excludedSubjectIds));
        }

        if ($subjectIds === []) {
            return;
        }

        $now = now();
        $rows = array_map(fn (int $subjectId): array => [
            'enrollment_ID' => $enrollment->enrollment_ID,
            'subject_ID' => $subjectId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $subjectIds);

        // A class-list import can enroll many learners at once.  Issuing a
        // select-plus-insert for every subject of every learner makes that
        // request slow enough to exceed a production proxy timeout.  The
        // table's unique key keeps this operation idempotent.
        DB::table((new StudentSubject)->getTable())->insertOrIgnore($rows);

        self::ensureElectiveAssignments($enrollment, $subjectIds, $electiveSubjectIds);
    }

    /**
     * Give selected electives a real grade-book assignment. The teacher can be
     * attached later from Teacher Assignments, but the subject is immediately
     * visible to the learner and ready to receive grades.
     *
     * @param  list<int>  $rosterCurriculumSubjectIds
     * @param  list<int>|null  $electiveSubjectIds
     */
    private static function ensureElectiveAssignments(
        Enrollment $enrollment,
        array $rosterCurriculumSubjectIds,
        ?array $electiveSubjectIds,
    ): void {
        if (! $enrollment->section_ID) {
            return;
        }

        $electiveSubjectIds ??= $enrollment->electives()
            ->pluck('subjects.subject_ID')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($electiveSubjectIds === []) {
            return;
        }

        $assignmentSubjectIds = collect($electiveSubjectIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->intersect($rosterCurriculumSubjectIds)
            ->values();

        if ($assignmentSubjectIds->isEmpty()) {
            return;
        }

        $now = now();
        DB::table((new TeacherSubjectAssignment)->getTable())->insertOrIgnore(
            $assignmentSubjectIds->map(fn (int $subjectId): array => [
                'section_ID' => $enrollment->section_ID,
                'subject_ID' => $subjectId,
                'staff_ID' => null,
                'SY_ID' => $enrollment->SY_ID,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }
}
