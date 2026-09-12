<?php

namespace Database\Seeders;

use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use Illuminate\Database\Seeder;

class GradingSemesterSeeder extends Seeder
{
    /**
     * Seed the application's senior high semesters.
     */
    public function run(): void
    {
        $activeId = GradingPeriodStatus::activeId();

        foreach (GradingSemester::definitions() as $semester) {
            GradingSemester::query()->updateOrCreate(
                ['key' => $semester['key']],
                [
                    'label' => $semester['label'],
                    'sort_order' => $semester['sort_order'],
                    'grading_period_status_ID' => $activeId,
                ],
            );
        }
    }
}
