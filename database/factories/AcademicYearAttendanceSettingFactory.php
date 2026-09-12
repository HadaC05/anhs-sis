<?php

namespace Database\Factories;

use App\Models\Month;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYearAttendanceSetting>
 */
class AcademicYearAttendanceSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'month' => fake()->randomElement(Month::ids()),
            'school_days' => fake()->numberBetween(0, 23),
        ];
    }
}
