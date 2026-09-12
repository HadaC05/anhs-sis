<?php

namespace Database\Factories;

use App\Models\GradingPeriodStatus;
use App\Models\GradingQuarter;
use App\Models\GradingSemester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradingQuarter>
 */
class GradingQuarterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quarterNumber = fake()->randomElement([1, 2]);

        return [
            'semester_ID' => GradingSemester::factory(),
            'key' => 'shs_'.fake()->unique()->lexify('??????').'_q'.$quarterNumber,
            'label' => 'Quarter '.$quarterNumber,
            'quarter_number' => $quarterNumber,
            'sort_order' => $quarterNumber,
            'grading_period_status_ID' => GradingPeriodStatus::idFor(GradingPeriodStatus::ACTIVE)
                ?? GradingPeriodStatus::factory(),
        ];
    }
}
