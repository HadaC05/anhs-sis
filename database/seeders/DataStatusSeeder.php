<?php

namespace Database\Seeders;

use App\Models\DataStatus;
use Illuminate\Database\Seeder;

class DataStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['key' => 'active', 'label' => 'Active', 'sort_order' => 1],
            ['key' => 'archived', 'label' => 'Archived', 'sort_order' => 2],
        ] as $status) {
            DataStatus::query()->updateOrCreate(['key' => $status['key']], $status);
        }
    }
}
