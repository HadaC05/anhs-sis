<?php

namespace Database\Seeders;

use App\Models\GradeReturnReason;
use Illuminate\Database\Seeder;

class GradeReturnReasonSeeder extends Seeder
{
    /** Seed the selectable reasons for returning submitted grades. */
    public function run(): void
    {
        foreach (GradeReturnReason::definitions() as $reason) {
            GradeReturnReason::query()->updateOrCreate(
                ['name' => $reason['name']],
                ['description' => $reason['description']],
            );
        }
    }
}
