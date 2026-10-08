<?php

use App\Models\Cluster;
use App\Models\Track;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\TrackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds tracks and assigns every configured cluster to its track', function () {
    $this->seed(TrackSeeder::class);
    $this->seed(ClusterSeeder::class);

    $academicTrack = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $technicalTrack = Track::query()->where('name', 'Technical Professional Track')->firstOrFail();

    expect($academicTrack->clusters()->pluck('name')->all())->toEqualCanonicalizing([
        'Arts, Social Sciences & Humanities',
        'Business and Entrepreneurship',
        'Science, Technology, Engineering and Mathematics',
        'Sports, Health, and Wellness',
    ])->and($technicalTrack->clusters()->pluck('name')->all())->toEqualCanonicalizing([
        'ICT Support and Computer Programming Technologies',
        'Aesthetic, Wellness, and Human Care',
        'Agri-Fishery Business and Food Innovation',
        'Artisanal and Creative Enterprise',
        'Automotive and Small Engine Technologies',
        'Construction and Building Technologies',
        'Creative Arts and Design Technologies',
        'Hospitality and Tourism',
        'Industrial Technologies',
    ])->and(Cluster::query()->whereNull('track_ID')->exists())->toBeFalse();
});
