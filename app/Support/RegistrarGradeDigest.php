<?php

namespace App\Support;

use App\Models\GradeStatus;
use App\Models\Staff;
use App\Notifications\GradeSubmissionsDigest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrarGradeDigest
{
    public static function record(int $assignmentId, iterable $termIds): void
    {
        foreach ($termIds as $termId) {
            DB::table('pending_grade_submissions')->upsert([
                'assignment_ID' => $assignmentId,
                'term_ID' => $termId,
                'submitted_at' => now(),
            ], ['assignment_ID', 'term_ID'], ['submitted_at']);
        }
    }

    public static function sendWhenDue(): int
    {
        $localTime = now('Asia/Manila');
        if ($localTime->isWeekend() || $localTime->hour < 8 || $localTime->hour >= 18) {
            return 0;
        }

        return DB::transaction(function (): int {
            // A durable lock and timestamp prevent duplicate digests across workers/restarts.
            $state = DB::table('registrar_grade_digest_state')->where('id', 1)->lockForUpdate()->first();
            if (! $state || Carbon::parse($state->last_sent_at)->addHours(2)->isFuture()) {
                return 0;
            }
            $recipients = Staff::query()->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->where('role_name', 'registrar'))->get();
            if ($recipients->isEmpty()) {
                return 0;
            }

            $pending = DB::table('pending_grade_submissions')->orderBy('id')->lockForUpdate()->get();
            if ($pending->isEmpty()) {
                return 0;
            }
            // Skip submissions already approved, returned, or unlocked before the digest.
            $count = DB::table('pending_grade_submissions as pending')
                ->whereIn('pending.id', $pending->pluck('id'))
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')->from('student_subject_grades as grades')
                        ->whereColumn('grades.assignment_ID', 'pending.assignment_ID')
                        ->whereColumn('grades.term_ID', 'pending.term_ID')
                        ->where('grades.grade_status_ID', GradeStatus::idFor(GradeStatus::SUBMITTED));
                })
                ->distinct()
                ->count('pending.assignment_ID');

            if ($count > 0) {
                foreach ($recipients as $registrar) {
                    $registrar->notify(new GradeSubmissionsDigest($count));
                }
                DB::table('registrar_grade_digest_state')->where('id', 1)->update(['last_sent_at' => now()]);
            }
            DB::table('pending_grade_submissions')->whereIn('id', $pending->pluck('id'))->delete();

            return $count;
        });
    }
}
