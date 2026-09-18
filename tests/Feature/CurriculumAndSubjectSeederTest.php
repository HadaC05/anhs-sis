<?php

use App\Models\Curriculum;
use App\Models\Subject;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CurriculumSeeder;
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

test('curriculum seeder creates one junior high curriculum per grade and one per SHS grade, semester, and track', function () {
    $this->seed([
        ClusterSeeder::class,
        CurriculumSeeder::class,
    ]);

    $seniorHighNames = collect([11, 12])
        ->flatMap(fn (int $grade) => collect(['First', 'Second'])
            ->flatMap(fn (string $semester) => collect(CurriculumSeeder::SENIOR_HIGH_TRACKS)
                ->map(fn (string $track) => CurriculumSeeder::seniorHighCurriculumName($grade, $semester, $track))))
        ->all();

    expect(Curriculum::query()->whereIn('name', array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES))->count())->toBe(4)
        ->and(Curriculum::query()->whereIn('name', $seniorHighNames)->count())->toBe(12)
        ->and(Curriculum::query()->whereIn('name', CurriculumSeeder::SENIOR_HIGH_TRACKS)->exists())->toBeFalse()
        ->and(Curriculum::query()->where('name', 'DepEd SHS - GAS')->exists())->toBeFalse();
});

test('curriculum subject assignments are not seeded', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
    ]);

    expect(\App\Models\CurriculumSubject::query()->count())->toBe(0);
});
