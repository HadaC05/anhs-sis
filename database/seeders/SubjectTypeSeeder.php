<?php

namespace Database\Seeders;

use App\Models\SubjectType;
use Illuminate\Database\Seeder;

class SubjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['key' => 'core', 'label' => 'Core', 'sort_order' => 1],
            ['key' => 'applied', 'label' => 'Applied', 'sort_order' => 2],
            ['key' => 'specialized', 'label' => 'Specialized', 'sort_order' => 3],
        ] as $type) {
            SubjectType::query()->updateOrCreate(['key' => $type['key']], $type);
        }
    }
}
