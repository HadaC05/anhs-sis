<?php

namespace Database\Seeders;

use App\Models\Religion;
use Illuminate\Database\Seeder;

class ReligionSeeder extends Seeder
{
    /**
     * Seed the application's religions table.
     */
    public function run(): void
    {
        foreach (Religion::names() as $name) {
            Religion::query()->firstOrCreate(['name' => $name]);
        }
    }
}
