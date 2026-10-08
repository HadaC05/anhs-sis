<?php

namespace App\Support;

use App\Models\GradeStatus;
use App\Models\Staff;
use App\Models\TeacherSubjectAssignment;
use App\Notifications\SubjectsAwaitingRelease;

class PrincipalGradeReleaseNotifier
{
    public static function sync(): void
    {
        $assignmentIds = TeacherSubjectAssignment::query()
            ->withoutMapehParents()
            ->whereHas('grades', fn ($query) => $query->whereStatus(GradeStatus::APPROVED))
            ->orderBy('assignment_ID')
            ->pluck('assignment_ID')
            ->map(fn ($assignmentId): int => (int) $assignmentId)
            ->all();

        $principals = Staff::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'principal'))
            ->get();

        foreach ($principals as $principal) {
            if ($assignmentIds !== []) {
                $principal->notify(new SubjectsAwaitingRelease($assignmentIds));

                continue;
            }

            $principal->notifications()
                ->where('type', SubjectsAwaitingRelease::class)
                ->whereNull('read_at')
                ->update(['read_at' => now(), 'seen_at' => now()]);
        }
    }
}
