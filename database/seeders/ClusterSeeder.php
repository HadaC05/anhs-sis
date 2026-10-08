<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Track;
use Illuminate\Database\Seeder;

class ClusterSeeder extends Seeder
{
    /**
     * Seed the application's clusters table.
     */
    public function run(): void
    {
        $trackIds = Track::query()->pluck('track_ID', 'name');

        foreach (
            [
                ['track' => 'Academic Track', 'name' => 'Arts, Social Sciences & Humanities'],
                ['track' => 'Academic Track', 'name' => 'Business and Entrepreneurship'],
                ['track' => 'Academic Track', 'name' => 'Science, Technology, Engineering and Mathematics'],
                ['track' => 'Academic Track', 'name' => 'Sports, Health, and Wellness'],
                ['track' => 'Technical Professional Track', 'name' => 'ICT Support and Computer Programming Technologies'],
                ['track' => 'Technical Professional Track', 'name' => 'Aesthetic, Wellness, and Human Care'],
                ['track' => 'Technical Professional Track', 'name' => 'Agri-Fishery Business and Food Innovation'],
                ['track' => 'Technical Professional Track', 'name' => 'Artisanal and Creative Enterprise'],
                ['track' => 'Technical Professional Track', 'name' => 'Automotive and Small Engine Technologies'],
                ['track' => 'Technical Professional Track', 'name' => 'Construction and Building Technologies'],
                ['track' => 'Technical Professional Track', 'name' => 'Creative Arts and Design Technologies'],
                ['track' => 'Technical Professional Track', 'name' => 'Hospitality and Tourism'],
                ['track' => 'Technical Professional Track', 'name' => 'Industrial Technologies'],
            ] as $cluster
        ) {
            Cluster::query()->updateOrCreate(
                ['name' => $cluster['name']],
                ['track_ID' => $trackIds[$cluster['track']]],
            );
        }
    }
}
