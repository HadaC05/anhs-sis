<?php

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\CurriculumSubjectSeeder;
use Database\Seeders\SubjectSeeder;

test('subject seeder leaves junior high subjects without a cluster', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
    ]);

    expect(Subject::query()->where('code', 'MATH7')->value('cluster_ID'))->toBeNull()
        ->and(Subject::query()->where('code', 'ESP10')->value('cluster_ID'))->toBeNull()
        ->and(Subject::query()->where('code', 'ORALCOM')->value('cluster_ID'))->not->toBeNull()
        ->and(Subject::query()->where('code', 'PRECALC')->value('cluster_ID'))->not->toBeNull();
});

test('curriculum seeder creates one junior high curriculum per grade and one per cluster', function () {
    $this->seed([
        ClusterSeeder::class,
        CurriculumSeeder::class,
    ]);

    expect(Curriculum::query()->whereIn('name', array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES))->count())->toBe(4)
        ->and(Curriculum::query()->where('name', 'Arts, Social Sciences & Humanities')->exists())->toBeTrue()
        ->and(Curriculum::query()->where('name', 'Business and Entrepreneurship')->exists())->toBeTrue()
        ->and(Curriculum::query()->where('name', 'Science, Technology, Engineering and Mathematics')->exists())->toBeTrue()
        ->and(Curriculum::query()->where('name', 'DepEd SHS - GAS')->exists())->toBeFalse();
});

test('curriculum subject seeder assigns grade specific junior high sets and cluster senior high sets', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
        CurriculumSubjectSeeder::class,
    ]);

    $grade7Id = Curriculum::query()->where('name', 'Grade 7')->value('curriculum_ID');
    $grade7Codes = CurriculumSubject::query()
        ->where('curriculum_ID', $grade7Id)
        ->with('subject')
        ->get()
        ->pluck('subject.code')
        ->sort()
        ->values()
        ->all();

    expect($grade7Codes)->toBe(['AP7', 'ENG7', 'ESP7', 'FIL7', 'MAPEH7', 'MATH7', 'SCI7', 'TLE7'])
        ->and(CurriculumSubject::query()->where('curriculum_ID', $grade7Id)->whereNotNull('cluster_ID')->exists())->toBeFalse();

    $stemId = Curriculum::query()
        ->where('name', 'Science, Technology, Engineering and Mathematics')
        ->value('curriculum_ID');

    expect(CurriculumSubject::query()->where('curriculum_ID', $stemId)->whereHas('subject', fn ($query) => $query->where('code', 'PRECALC'))->exists())->toBeTrue()
        ->and(CurriculumSubject::query()->where('curriculum_ID', $stemId)->whereHas('subject', fn ($query) => $query->where('code', 'MATH7'))->exists())->toBeFalse();

    $asshId = Curriculum::query()
        ->where('name', 'Arts, Social Sciences & Humanities')
        ->value('curriculum_ID');

    expect(CurriculumSubject::query()->where('curriculum_ID', $asshId)->whereHas('subject', fn ($query) => $query->where('code', 'DISS'))->exists())->toBeTrue()
        ->and(CurriculumSubject::query()->where('curriculum_ID', $asshId)->whereHas('subject', fn ($query) => $query->where('code', 'PRECALC'))->exists())->toBeFalse();
});
