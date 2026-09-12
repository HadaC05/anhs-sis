<?php

namespace Database\Seeders;

use App\Models\EnrollmentStatus;
use Illuminate\Database\Seeder;

class EnrollmentStatusSeeder extends Seeder
{
    /**
     * Seed the application's enrollment statuses table.
     */
    public function run(): void
    {
        foreach (EnrollmentStatus::definitions() as $status) {
            EnrollmentStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        EnrollmentStatus::clearOptionsCache();
    }
}
