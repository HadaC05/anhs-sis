<?php

namespace App\Support;

use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\Sf9Configuration;

class Sf9PerformanceScale
{
    public static function forSection(Section $section): array
    {
        return GradingTerm::isSeniorHighSection($section) || Sf9Configuration::current()->junior_high === 'jhs_2026'
            ? self::updated() : self::legacy();
    }

    public static function updated(): array
    {
        return [
            ['min' => 90, 'scale' => '90-100', 'description' => 'Advancing', 'remarks' => 'Passed'],
            ['min' => 80, 'scale' => '80-89', 'description' => 'Benchmarking', 'remarks' => 'Passed'],
            ['min' => 75, 'scale' => '75-79', 'description' => 'Connecting', 'remarks' => 'Passed'],
            ['min' => 65, 'scale' => '65-74', 'description' => 'Developing', 'remarks' => 'Failed'],
            ['min' => 0, 'scale' => '0-64', 'description' => 'Emerging', 'remarks' => 'Failed'],
        ];
    }

    public static function legacy(): array
    {
        return [
            ['min' => 90, 'scale' => '90-100', 'description' => 'Outstanding', 'remarks' => 'Passed'],
            ['min' => 85, 'scale' => '85-89', 'description' => 'Very Satisfactory', 'remarks' => 'Passed'],
            ['min' => 80, 'scale' => '80-84', 'description' => 'Satisfactory', 'remarks' => 'Passed'],
            ['min' => 75, 'scale' => '75-79', 'description' => 'Fairly Satisfactory', 'remarks' => 'Passed'],
            ['min' => 0, 'scale' => 'Below 75', 'description' => 'Did Not Meet Expectations', 'remarks' => 'Failed'],
        ];
    }

    public static function descriptor(?float $grade, array $bands): ?string
    {
        if ($grade === null || ! is_finite($grade) || $grade < 0 || $grade > 100) {
            return null;
        }
        foreach ($bands as $band) {
            if ($grade >= $band['min']) {
                return $band['description'];
            }
        }

        return null;
    }
}
