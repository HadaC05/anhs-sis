<?php

use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reads senior high periods from semesters and shared junior high terms', function () {
    $periods = GradingTerm::seniorHighPeriods();
    $termOne = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    expect($periods)->toHaveCount(6)
        ->and($periods[0]['key'])->toBe('shs_sem1_term_1')
        ->and($periods[0]['semester'])->toBe(GradingSemester::FIRST)
        ->and($periods[0]['term'])->toBe(1)
        ->and($periods[0]['label'])->toBe('First Semester · Term 1')
        ->and($periods[0]['is_active'])->toBeTrue()
        ->and((int) $periods[0]['term_ID'])->toBe((int) $termOne->term_ID)
        ->and($periods[5]['key'])->toBe('shs_sem2_term_3')
        ->and($periods[5]['semester_ID'])->toBe(GradingSemester::idFor(GradingSemester::SECOND));
});

it('hides inactive senior high semesters from active grading periods', function () {
    $secondSemester = GradingSemester::query()->where('key', GradingSemester::SECOND)->firstOrFail();
    $secondSemester->update(['grading_period_status_ID' => GradingPeriodStatus::inactiveId()]);
    GradingPeriodStatus::clearOptionsCache();

    $periods = GradingTerm::seniorHighPeriods(null, true);

    expect($periods)->toHaveCount(3)
        ->and(array_column($periods, 'key'))->toBe(['shs_sem1_term_1', 'shs_sem1_term_2', 'shs_sem1_term_3']);
});

it('opens senior high periods through the referenced semester and term', function () {
    GradingTermSetting::current()->setSeniorHighPeriod(GradingSemester::SECOND, 1);

    expect(GradingTerm::seniorHighOpenPeriods())->toHaveCount(4)
        ->and(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem2_term_1')
        ->and(GradingTerm::currentSeniorHighPeriodLabel())->toBe('Second Semester · Term 1')
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe(['shs_sem1_term_1', 'shs_sem1_term_2', 'shs_sem1_term_3']);
});
