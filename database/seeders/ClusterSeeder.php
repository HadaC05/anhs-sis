<?php

namespace Database\Seeders;

use App\Models\Cluster;
use Illuminate\Database\Seeder;

class ClusterSeeder extends Seeder
{
    /**
     * Seed the application's clusters table.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Arts, Social Sciences & Humanities'],
            ['name' => 'Business and Entrepreneurship'],
            ['name' => 'Science, Technology, Engineering and Mathematics'],
        ] as $cluster) {
            Cluster::query()->firstOrCreate(['name' => $cluster['name']]);
        }
    }
}
