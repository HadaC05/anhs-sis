<?php

namespace Database\Factories;

use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationType>
 */
class NotificationTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => str($name)->slug('_')->toString(),
            'name' => str($name)->title()->toString(),
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
