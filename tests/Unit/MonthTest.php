<?php

use App\Models\Month;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores calendar months from january to december with numeric indexes', function () {
    $months = Month::query()->orderBy('month_ID')->get();

    expect($months)->toHaveCount(12)
        ->and($months->map(fn (Month $month): int => (int) $month->month_ID)->all())->toBe(range(1, 12))
        ->and($months->pluck('name')->values()->all())->toBe(array_values(Month::names()))
        ->and(Month::ids())->toBe(range(1, 12))
        ->and(Month::labels()[1])->toBe('January')
        ->and(Month::labels()[12])->toBe('December');
});
