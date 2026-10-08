<?php

use App\Models\Subject;
use App\Models\SubjectType;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\SubjectTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds only general core and elective subject types', function () {
    $this->seed(SubjectTypeSeeder::class);

    expect(SubjectType::query()->orderBy('sort_order')->pluck('key')->all())
        ->toBe(['general', 'core', 'elective']);
});

it('seeds general junior high subjects and clustered senior high electives', function () {
    $this->seed([
        SubjectTypeSeeder::class,
        ClusterSeeder::class,
        SubjectSeeder::class,
    ]);

    $juniorHigh = Subject::query()->where('school_level', 'Junior High School')->with('subjectType')->get();
    $core = Subject::query()->where('school_level', 'Senior High School')->whereHas('subjectType', fn ($query) => $query->where('key', 'core'))->where('status', 'active')->get();
    $electives = Subject::query()->whereHas('subjectType', fn ($query) => $query->where('key', 'elective'))->where('status', 'active')->get();

    expect($juniorHigh)->toHaveCount(32)
        ->and($juniorHigh->every(fn (Subject $subject): bool => $subject->type === 'general' && $subject->cluster_ID === null))->toBeTrue()
        ->and($core->pluck('title')->all())->toEqualCanonicalizing(array_merge(
            array_values(SubjectSeeder::SENIOR_HIGH_CORE_SUBJECTS),
            array_values(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS),
        ))
        ->and($core->every(fn (Subject $subject): bool => $subject->cluster_ID === null))->toBeTrue()
        ->and($electives)->toHaveCount(collect(SubjectSeeder::ELECTIVES_BY_CLUSTER)->flatten()->count())
        ->and($electives->every(fn (Subject $subject): bool => $subject->cluster_ID !== null))->toBeTrue();
});
