<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeStatusId = DB::table('data_statuses')->where('key', 'active')->value('data_status_ID');
        $now = now();

        $matatagId = DB::table('curricula')->where('name', 'MATATAG')->value('curricula_ID')
            ?: DB::table('curricula')->insertGetId([
                'name' => 'MATATAG',
                'description' => 'MATATAG curriculum for Grades 7 to 10.',
                'data_status_ID' => $activeStatusId,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'curricula_ID');

        $strengthenedId = DB::table('curricula')->where('name', 'Strengthened Senior High School')->value('curricula_ID')
            ?: DB::table('curricula')->insertGetId([
                'name' => 'Strengthened Senior High School',
                'description' => 'Strengthened Senior High School curriculum for Grades 11 and 12.',
                'data_status_ID' => $activeStatusId,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'curricula_ID');

        DB::table('curricula')->where('curricula_ID', $matatagId)->update([
            'description' => 'MATATAG curriculum for Grades 7 to 10.',
            'data_status_ID' => $activeStatusId,
            'updated_at' => $now,
        ]);
        DB::table('curricula')->where('curricula_ID', $strengthenedId)->update([
            'description' => 'Strengthened Senior High School curriculum for Grades 11 and 12.',
            'data_status_ID' => $activeStatusId,
            'updated_at' => $now,
        ]);

        $juniorGradeIds = DB::table('grade_level')->whereIn('grade_label', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'])->pluck('grade_ID');
        $seniorGradeIds = DB::table('grade_level')->whereIn('grade_label', ['Grade 11', 'Grade 12'])->pluck('grade_ID');

        DB::table('curriculum_grade_levels')->whereIn('grade_ID', $juniorGradeIds)->update(['curricula_ID' => $matatagId]);
        DB::table('curriculum_grade_levels')->whereIn('grade_ID', $seniorGradeIds)->update(['curricula_ID' => $strengthenedId]);

        DB::table('curricula')
            ->whereNotIn('curricula_ID', [$matatagId, $strengthenedId])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('curriculum_grade_levels')
                    ->whereColumn('curriculum_grade_levels.curricula_ID', 'curricula.curricula_ID');
            })
            ->delete();
    }

    public function down(): void
    {
        // Offering ownership cannot be restored reliably after legacy curricula are consolidated.
    }
};
