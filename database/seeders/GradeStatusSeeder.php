<?php

namespace Database\Seeders;

use App\Models\GradeStatus;
use Illuminate\Database\Seeder;

class GradeStatusSeeder extends Seeder
{
    /**
     * Seed the application's grade statuses table.
     */
    public function run(): void
    {
        foreach (GradeStatus::definitions() as $status) {
            GradeStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        GradeStatus::clearOptionsCache();
    }
}
