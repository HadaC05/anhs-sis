<?php

namespace Database\Seeders;

use App\Models\PromotionStatus;
use Illuminate\Database\Seeder;

class PromotionStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PromotionStatus::definitions() as $status) {
            PromotionStatus::query()->updateOrCreate(['slug' => $status['slug']], $status);
        }

        PromotionStatus::clearCache();
    }
}
