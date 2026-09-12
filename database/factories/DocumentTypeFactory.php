<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
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
            'description' => fake()->sentence(),
            'is_required' => false,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
