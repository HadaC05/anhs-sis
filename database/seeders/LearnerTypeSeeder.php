<?php

namespace Database\Seeders;

use App\Models\LearnerType;
use Illuminate\Database\Seeder;

class LearnerTypeSeeder extends Seeder
{
    /**
     * Seed the application's learner types table.
     */
    public function run(): void
    {
        foreach (LearnerType::definitions() as $type) {
            LearnerType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                ],
            );
        }

        LearnerType::clearOptionsCache();
    }
}
