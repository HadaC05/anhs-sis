<?php

namespace Database\Factories;

use App\Models\PlacementStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementStatus>
 */
class PlacementStatusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => str($name)->slug('_')->toString(),
            'name' => str($name)->title()->toString(),
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
