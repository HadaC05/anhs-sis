<?php

namespace Database\Factories;

use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradingSemester>
 */
class GradingSemesterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => str($name)->slug('_')->toString(),
            'label' => str($name)->title()->toString(),
            'sort_order' => fake()->numberBetween(10, 99),
            'grading_period_status_ID' => GradingPeriodStatus::idFor(GradingPeriodStatus::ACTIVE)
                ?? GradingPeriodStatus::factory(),
        ];
    }
}
