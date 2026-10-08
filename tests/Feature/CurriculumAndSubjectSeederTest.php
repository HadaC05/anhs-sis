<?php

use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CombinedSubjectConfigurationSeeder;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\SubjectSeeder;

test('subject seeder assigns clusters only to senior high electives', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
    ]);

    expect(Subject::query()->where('code', 'MATH7')->value('cluster_ID'))->toBeNull()
        ->and(Subject::query()->where('code', 'ESP10')->value('cluster_ID'))->toBeNull()
        ->and(Subject::query()->where('code', 'EFFCOM')->value('cluster_ID'))->toBeNull()
        ->and(Subject::query()->where('code', 'STEM-E26')->value('cluster_ID'))->not->toBeNull();
});

test('curriculum seeder creates one junior high curriculum per grade and one per SHS grade semester and cluster', function () {
    $this->seed([
        ClusterSeeder::class,
        CurriculumSeeder::class,
    ]);

    $seniorHighNames = collect([11, 12])
        ->flatMap(fn (int $grade) => collect(['First', 'Second'])
            ->flatMap(fn (string $semester) => collect(CurriculumSeeder::SENIOR_HIGH_CLUSTERS)
                ->map(fn (string $track) => CurriculumSeeder::seniorHighCurriculumName($grade, $semester, $track))))
        ->all();

    expect(Curriculum::query()->whereIn('name', array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES))->count())->toBe(4)
        ->and(Curriculum::query()->whereIn('name', $seniorHighNames)->count())->toBe(count($seniorHighNames))
        ->and(Curricula::query()->orderBy('name')->pluck('name')->all())->toBe([
            'MATATAG',
            'Strengthened Senior High School',
        ])
        ->and(Curriculum::query()->whereIn('name', array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES))->whereHas('curricula', fn ($query) => $query->where('name', 'MATATAG'))->count())->toBe(4)
        ->and(Curriculum::query()->whereIn('name', $seniorHighNames)->whereHas('curricula', fn ($query) => $query->where('name', 'Strengthened Senior High School'))->count())->toBe(count($seniorHighNames))
        ->and(Curriculum::query()->whereIn('name', CurriculumSeeder::SENIOR_HIGH_CLUSTERS)->exists())->toBeFalse()
        ->and(Curriculum::query()->where('name', 'DepEd SHS - GAS')->exists())->toBeFalse();
});

test('curriculum subjects seed junior high areas and grade 11 core subjects in both semesters', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
        \Database\Seeders\CurriculumSubjectSeeder::class,
    ]);

    $gradeElevenIds = Curriculum::query()
        ->whereHas('gradeLevel', fn ($query) => $query->where('grade_label', 'Grade 11'))
        ->pluck('curriculum_ID');
    $gradeTwelveIds = Curriculum::query()
        ->whereHas('gradeLevel', fn ($query) => $query->where('grade_label', 'Grade 12'))
        ->pluck('curriculum_ID');

    expect(CurriculumSubject::query()->whereHas('curriculumGradeLevel.gradeLevel', fn ($query) => $query->whereIn('grade_label', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10']))->count())->toBe(32)
        ->and(CurriculumSubject::query()->whereIn('curriculum_grade_level_ID', $gradeElevenIds)->whereHas('subject.subjectType', fn ($query) => $query->where('key', 'core'))->count())->toBe($gradeElevenIds->count() * (count(SubjectSeeder::SENIOR_HIGH_CORE_SUBJECTS) + count(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS)))
        ->and(CurriculumSubject::query()->whereIn('curriculum_grade_level_ID', $gradeTwelveIds)->whereHas('subject.subjectType', fn ($query) => $query->where('key', 'core'))->exists())->toBeFalse()
        ->and(CurriculumSubject::query()->whereHas('subject.subjectType', fn ($query) => $query->where('key', 'elective'))->exists())->toBeFalse();
});

test('effective communication is automatically configured as one subject with two components', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
        \Database\Seeders\CurriculumSubjectSeeder::class,
        \Database\Seeders\AcademicYearSeeder::class,
        CombinedSubjectConfigurationSeeder::class,
    ]);

    $configuration = \App\Models\MapehConfiguration::query()
        ->with(['parentSubject.subject', 'components.curriculumSubject.subject'])
        ->whereHas('parentSubject.subject', fn ($query) => $query->where('code', 'EFFCOM'))
        ->firstOrFail();

    expect($configuration->parentSubject->subject->title)->toBe('Effective Communication & Mabisang Communication')
        ->and($configuration->mode)->toBe('paired')
        ->and($configuration->components)->toHaveCount(2)
        ->and($configuration->components->pluck('key')->all())->toEqualCanonicalizing([
            'effective_communication',
            'mabisang_communication',
        ])
        ->and($configuration->components->pluck('curriculumSubject.subject.title')->all())->toEqualCanonicalizing([
            'Effective Communication',
            'Mabisang Communication',
        ]);

    $admin = \App\Models\Staff::query()->create([
        'role_id' => \App\Models\Role::query()->firstOrCreate(['role_name' => 'admin'])->id,
        'username' => 'communication.config.admin',
        'password' => 'password',
        'first_name' => 'Configuration',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.curriculum-config.mapeh.edit', [
            'curriculum' => $configuration->curriculum_grade_level_ID,
            'SY_ID' => $configuration->SY_ID,
        ]))
        ->assertOk()
        ->assertSee('Effective Communication &amp; Mabisang Communication Components', false)
        ->assertSee('Mabisang Communication');
});

test('junior high MAPEH is automatically configured by pair', function () {
    $this->seed([
        ClusterSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
        \Database\Seeders\CurriculumSubjectSeeder::class,
        \Database\Seeders\AcademicYearSeeder::class,
        CombinedSubjectConfigurationSeeder::class,
    ]);

    $configurations = \App\Models\MapehConfiguration::query()
        ->with(['parentSubject.subject', 'components.curriculumSubject.subject'])
        ->whereHas('parentSubject.subject', fn ($query) => $query->where('code', 'like', 'MAPEH%'))
        ->get();

    expect($configurations)->toHaveCount(12)
        ->and($configurations->every(fn ($configuration): bool => $configuration->mode === 'paired'))->toBeTrue()
        ->and($configurations->every(fn ($configuration): bool => $configuration->components->pluck('key')->sort()->values()->all() === [
            'music_arts',
            'pe_health',
        ]))->toBeTrue();
});
