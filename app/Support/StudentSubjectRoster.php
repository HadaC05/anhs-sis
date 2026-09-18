<?php

namespace App\Support;

use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\StudentSubject;

class StudentSubjectRoster
{
    /** Create the immutable subject roster for an enrollment's selected offering. */
    public static function sync(Enrollment $enrollment): void
    {
        $subjectIds = CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
            ->pluck('curr_subj_ID');

        foreach ($subjectIds as $subjectId) {
            StudentSubject::query()->firstOrCreate([
                'enrollment_ID' => $enrollment->enrollment_ID,
                'curr_subj_ID' => $subjectId,
            ]);
        }
    }
}
