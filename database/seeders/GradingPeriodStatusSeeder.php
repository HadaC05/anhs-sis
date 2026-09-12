<?php

namespace Database\Seeders;

use App\Models\GradingPeriodStatus;
use Illuminate\Database\Seeder;

class GradingPeriodStatusSeeder extends Seeder
{
    /**
     * Seed the application's grading period statuses table.
     */
    public function run(): void
    {
        foreach (GradingPeriodStatus::definitions() as $status) {
            GradingPeriodStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        GradingPeriodStatus::clearOptionsCache();
    }
}
