<?php

use App\Support\Sf9PerformanceScale;

it('maps SF9 descriptor boundaries including fractional averages and missing grades', function () {
    expect(Sf9PerformanceScale::descriptor(85, Sf9PerformanceScale::legacy()))->toBe('Very Satisfactory')
        ->and(Sf9PerformanceScale::descriptor(85, Sf9PerformanceScale::updated()))->toBe('Benchmarking')
        ->and(Sf9PerformanceScale::descriptor(74.99, Sf9PerformanceScale::updated()))->toBe('Developing')
        ->and(Sf9PerformanceScale::descriptor(75, Sf9PerformanceScale::updated()))->toBe('Connecting')
        ->and(Sf9PerformanceScale::descriptor(65, Sf9PerformanceScale::updated()))->toBe('Developing')
        ->and(Sf9PerformanceScale::descriptor(64, Sf9PerformanceScale::updated()))->toBe('Emerging')
        ->and(Sf9PerformanceScale::descriptor(90, Sf9PerformanceScale::legacy()))->toBe('Outstanding')
        ->and(Sf9PerformanceScale::descriptor(90, Sf9PerformanceScale::updated()))->toBe('Advancing')
        ->and(Sf9PerformanceScale::descriptor(null, Sf9PerformanceScale::updated()))->toBeNull();
});
