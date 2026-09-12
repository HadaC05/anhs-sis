<?php

namespace Database\Seeders;

use App\Models\DocumentStatus;
use Illuminate\Database\Seeder;

class DocumentStatusSeeder extends Seeder
{
    /**
     * Seed the application's document statuses table.
     */
    public function run(): void
    {
        foreach (DocumentStatus::definitions() as $status) {
            DocumentStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                ],
            );
        }

        DocumentStatus::clearOptionsCache();
    }
}
