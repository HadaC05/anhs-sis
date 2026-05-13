<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Seed the application's curriculum table.
     */
    public function run(): void
    {
        foreach ([
            [
                'name' => 'DepEd SHS - ABM',
                'description' => 'Accountancy, Business, and Management strand curriculum for Senior High School.',
                'status' => true,
            ],
            [
                'name' => 'DepEd SHS - STEM',
                'description' => 'Science, Technology, Engineering, and Mathematics strand curriculum for Senior High School.',
                'status' => true,
            ],
            [
                'name' => 'DepEd SHS - HUMSS',
                'description' => 'Humanities and Social Sciences strand curriculum for Senior High School.',
                'status' => true,
            ],
            [
                'name' => 'DepEd SHS - GAS',
                'description' => 'General Academic Strand curriculum for Senior High School.',
                'status' => true,
            ],
        ] as $curriculum) {
            Curriculum::query()->updateOrCreate(
                ['name' => $curriculum['name']],
                [
                    'description' => $curriculum['description'],
                    'status' => $curriculum['status'],
                ],
            );
        }
    }
}
