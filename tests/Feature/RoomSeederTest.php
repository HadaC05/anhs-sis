<?php

use App\Models\Room;
use App\Models\Section;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\SectionSeeder;

test('room seeder creates twenty rooms and assigns them to existing sections', function () {
    $this->seed([
        ClusterSeeder::class,
        AcademicYearSeeder::class,
        CurriculumSeeder::class,
        GradeLevelSeeder::class,
        SectionSeeder::class,
        RoomSeeder::class,
    ]);

    $expectedRooms = collect([1, 2])
        ->flatMap(fn (int $floor) => collect(range(1, 10))
            ->map(fn (int $number): string => 'Room '.(($floor * 100) + $number)))
        ->values();

    $sections = Section::query()->orderBy('section_ID')->get();

    expect(Room::query()->orderBy('name')->pluck('name')->all())
        ->toBe($expectedRooms->sort()->values()->all())
        ->and($sections)->not->toBeEmpty()
        ->and($sections->pluck('room')->contains(null))->toBeFalse();

    foreach ($sections as $index => $section) {
        expect($section->room)->toBe($expectedRooms[$index % $expectedRooms->count()]);
    }

    $this->seed(RoomSeeder::class);

    expect(Room::query()->count())->toBe(20);
});
