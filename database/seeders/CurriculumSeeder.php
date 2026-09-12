<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Curriculum;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Junior high curricula keyed by grade level.
     *
     * @var array<string, string>
     */
    public const JUNIOR_HIGH_NAMES = [
        'grade_7' => 'Grade 7',
        'grade_8' => 'Grade 8',
        'grade_9' => 'Grade 9',
        'grade_10' => 'Grade 10',
    ];

    /**
     * Seed the application's curriculum table.
     */
    public function run(): void
    {
        foreach (self::JUNIOR_HIGH_NAMES as $gradeLevel => $name) {
            $gradeNumber = str_replace('grade_', '', $gradeLevel);

            Curriculum::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "Junior High School Grade {$gradeNumber} subject offerings.",
                    'status' => true,
                ],
            );
        }

        foreach (Cluster::query()->orderBy('name')->get(['name']) as $cluster) {
            Curriculum::query()->updateOrCreate(
                ['name' => $cluster->name],
                [
                    'description' => "Senior High School curriculum for the {$cluster->name} cluster.",
                    'status' => true,
                ],
            );
        }
    }
}
