<?php

use Database\Seeders\CurriculumSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These assignments were sample data. Deleting them also removes their
        // teacher assignments through the curriculum-subject foreign key.
        DB::table('curriculum_subjects')->delete();

        $newCurriculumIds = [];

        foreach ([11, 12] as $grade) {
            foreach (['First', 'Second'] as $semester) {
                foreach (CurriculumSeeder::SENIOR_HIGH_TRACKS as $track) {
                    $name = CurriculumSeeder::seniorHighCurriculumName($grade, $semester, $track);

                    $existingId = DB::table('curriculum')->where('name', $name)->value('curriculum_ID');

                    if ($existingId) {
                        DB::table('curriculum')->where('curriculum_ID', $existingId)->update([
                            'description' => "Senior High School Grade {$grade} {$semester} Semester curriculum for {$track}.",
                            'status' => true,
                            'updated_at' => now(),
                        ]);
                    } else {
                        $existingId = DB::table('curriculum')->insertGetId([
                            'name' => $name,
                            'description' => "Senior High School Grade {$grade} {$semester} Semester curriculum for {$track}.",
                            'status' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $newCurriculumIds[$grade][$track] = $existingId;
                }
            }
        }

        $legacyCurricula = DB::table('curriculum')
            ->whereIn('name', CurriculumSeeder::SENIOR_HIGH_TRACKS)
            ->get(['curriculum_ID', 'name']);

        $gradeIds = DB::table('grade_level')
            ->whereIn('grade_label', ['Grade 11', 'Grade 12'])
            ->pluck('grade_ID', 'grade_label');

        foreach ($legacyCurricula as $legacyCurriculum) {
            foreach ([11, 12] as $grade) {
                $gradeId = $gradeIds["Grade {$grade}"] ?? null;

                if ($gradeId) {
                    DB::table('sections')
                        ->where('curriculum_ID', $legacyCurriculum->curriculum_ID)
                        ->where('grade_ID', $gradeId)
                        ->update(['curriculum_ID' => $newCurriculumIds[$grade][$legacyCurriculum->name]]);
                }
            }
        }

        DB::table('curriculum')
            ->whereIn('curriculum_ID', $legacyCurricula->pluck('curriculum_ID'))
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('sections')
                    ->whereColumn('sections.curriculum_ID', 'curriculum.curriculum_ID');
            })
            ->delete();
    }

    public function down(): void
    {
        // Data removed by this migration cannot be reconstructed safely.
    }
};
