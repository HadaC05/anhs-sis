<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['grade_label' => 'Grade 7', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 8', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 9', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 10', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 11', 'category' => 'Senior High School'],
            ['grade_label' => 'Grade 12', 'category' => 'Senior High School'],
        ] as $grade) {
            GradeLevel::query()->updateOrCreate(
                ['grade_label' => $grade['grade_label']],
                ['category' => $grade['category']],
            );
        }
    }
}
