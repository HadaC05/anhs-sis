<?php

namespace Database\Seeders;

use App\Models\DocumentReturnReason;
use Illuminate\Database\Seeder;

class DocumentReturnReasonSeeder extends Seeder
{
    /**
     * Seed the application's document return reasons table.
     */
    public function run(): void
    {
        foreach (DocumentReturnReason::definitions() as $reason) {
            DocumentReturnReason::query()->updateOrCreate(
                ['name' => $reason['name']],
                ['description' => $reason['description']],
            );
        }
    }
}
