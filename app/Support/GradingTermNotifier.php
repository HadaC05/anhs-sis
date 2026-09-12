<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\TeacherSubjectAssignment;
use App\Notifications\GradingTermOpened;

class GradingTermNotifier
{
    public static function opened(string $label, bool $seniorHigh): void
    {
        $teacherIds = TeacherSubjectAssignment::query()
            ->whereHas('section.gradeLevel', fn ($query) => $seniorHigh
                ? $query->whereIn('grade_label', ['Grade 11', 'Grade 12'])
                : $query->whereNotIn('grade_label', ['Grade 11', 'Grade 12']))
            ->distinct()
            ->pluck('staff_ID');

        Staff::query()
            ->whereIn('staff_id', $teacherIds)
            ->where('status', 'active')
            ->each(fn (Staff $teacher) => $teacher->notify(new GradingTermOpened($label)));
    }
}
