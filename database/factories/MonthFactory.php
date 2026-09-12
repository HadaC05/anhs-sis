<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Month>
 */
class MonthFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monthId = fake()->unique()->numberBetween(1, 12);

        return [
            'month_ID' => $monthId,
            'name' => \App\Models\Month::names()[$monthId],
        ];
    }
}
