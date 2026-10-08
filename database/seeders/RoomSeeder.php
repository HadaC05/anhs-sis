<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Section;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Seed the rooms and assign them to existing sections.
     */
    public function run(): void
    {
        $roomNames = collect([1, 2])
            ->flatMap(fn (int $floor) => collect(range(1, 10))
                ->map(fn (int $number): string => 'Room '.(($floor * 100) + $number)))
            ->values();

        foreach ($roomNames as $roomName) {
            Room::query()->firstOrCreate(['name' => $roomName]);
        }

        Section::query()
            ->orderBy('section_ID')
            ->get()
            ->each(function (Section $section, int $index) use ($roomNames): void {
                $section->update([
                    'room' => $roomNames[$index % $roomNames->count()],
                ]);
            });
    }
}
