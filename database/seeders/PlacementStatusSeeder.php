<?php

namespace Database\Seeders;

use App\Models\PlacementStatus;
use Illuminate\Database\Seeder;

class PlacementStatusSeeder extends Seeder
{
    /**
     * Seed the application's placement statuses table.
     */
    public function run(): void
    {
        foreach (PlacementStatus::definitions() as $status) {
            PlacementStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        PlacementStatus::clearOptionsCache();
    }
}
