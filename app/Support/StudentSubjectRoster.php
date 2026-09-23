<?php

namespace App\Support;

use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\StudentSubject;
use Illuminate\Support\Facades\DB;

class StudentSubjectRoster
{
    /** Create the immutable subject roster for an enrollment's selected offering. */
    public static function sync(Enrollment $enrollment): void
    {
        $curriculumId = (int) $enrollment->curriculum_grade_level_ID;
        $subjectIds = CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $curriculumId)
            ->pluck('curr_subj_ID')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($subjectIds === []) {
            return;
        }

        $now = now();
        $rows = array_map(fn (int $subjectId): array => [
            'enrollment_ID' => $enrollment->enrollment_ID,
            'curr_subj_ID' => $subjectId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $subjectIds);

        // A class-list import can enroll many learners at once.  Issuing a
        // select-plus-insert for every subject of every learner makes that
        // request slow enough to exceed a production proxy timeout.  The
        // table's unique key keeps this operation idempotent.
        DB::table((new StudentSubject)->getTable())->insertOrIgnore($rows);
    }
}
