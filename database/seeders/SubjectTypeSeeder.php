<?php

namespace Database\Seeders;

use App\Models\SubjectType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['key' => 'general', 'label' => 'General', 'sort_order' => 1],
            ['key' => 'core', 'label' => 'Core', 'sort_order' => 2],
            ['key' => 'elective', 'label' => 'Elective', 'sort_order' => 3],
        ] as $type) {
            SubjectType::query()->updateOrCreate(['key' => $type['key']], $type);
        }

        $electiveId = SubjectType::idForKey('elective');
        $legacyIds = SubjectType::query()->whereNotIn('key', ['general', 'core', 'elective'])->pluck('subject_type_ID');

        if ($legacyIds->isNotEmpty()) {
            DB::table('subjects')->whereIn('subject_type_ID', $legacyIds)->update(['subject_type_ID' => $electiveId]);
        }

        SubjectType::query()->whereNotIn('key', ['general', 'core', 'elective'])->delete();
    }
}
