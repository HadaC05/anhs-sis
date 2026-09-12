<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    /**
     * Seed the application's notification types table.
     */
    public function run(): void
    {
        foreach (NotificationType::definitions() as $type) {
            NotificationType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                ],
            );
        }

        NotificationType::clearOptionsCache();
    }
}
