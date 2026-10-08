<?php

namespace Database\Seeders;

use App\Models\Track;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    /**
     * Seed the application's tracks table.
     */
    public function run(): void
    {
        foreach ([
            'Academic Track',
            'Technical Professional Track',
        ] as $name) {
            Track::query()->firstOrCreate(['name' => $name]);
        }
    }
}
