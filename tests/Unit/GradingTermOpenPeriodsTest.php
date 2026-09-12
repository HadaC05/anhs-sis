<?php

use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns open grading periods based on admin setting', function () {
    GradingTermSetting::query()->where('id', 1)->update([
        'open_terms_count' => 2,
    ]);

    expect(GradingTerm::gradingOpenPeriods())->toHaveCount(2)
        ->and(GradingTerm::gradingOpenPeriods()[0]['key'])->toBe('term_1')
        ->and(GradingTerm::gradingOpenPeriods()[1]['key'])->toBe('term_2')
        ->and(GradingTerm::lockedGradingPeriodKeys())->toBe(['term_1'])
        ->and(GradingTerm::currentEditablePeriodKey())->toBe('term_2')
        ->and(GradingTerm::currentEditablePeriodLabel())->toBe('Term 2');
});

it('keeps only the current term editable when one term is open', function () {
    GradingTermSetting::query()->where('id', 1)->update([
        'open_terms_count' => 1,
    ]);

    expect(GradingTerm::gradingOpenPeriods())->toHaveCount(1)
        ->and(GradingTerm::lockedGradingPeriodKeys())->toBe([])
        ->and(GradingTerm::currentEditablePeriodKey())->toBe('term_1');
});

it('deactivates terms beyond the maximum and reactivates them when the limit is restored', function () {
    GradingTerm::syncActiveStatus(3);

    expect(GradingTerm::query()->where('key', 'term_4')->first()?->isActive())->toBeFalsy()
        ->and(GradingTerm::query()->active()->count())->toBe(3);

    GradingTerm::syncActiveStatus(4);

    expect(GradingTerm::query()->where('key', 'term_4')->first()?->isActive())->toBeTruthy()
        ->and(GradingTerm::query()->active()->count())->toBe(4);
});

it('returns senior high periods with semester and term labels', function () {
    $periods = GradingTerm::seniorHighPeriods();

    expect($periods)->toHaveCount(6)
        ->and($periods[0]['key'])->toBe('shs_sem1_term_1')
        ->and($periods[0]['semester'])->toBe('first')
        ->and($periods[0]['term'])->toBe(1)
        ->and($periods[0]['label'])->toBe('First Semester · Term 1')
        ->and($periods[2]['key'])->toBe('shs_sem1_term_3')
        ->and($periods[3]['key'])->toBe('shs_sem2_term_1')
        ->and($periods[3]['semester'])->toBe('second')
        ->and($periods[5]['key'])->toBe('shs_sem2_term_3')
        ->and($periods[5]['label'])->toBe('Second Semester · Term 3');
});

it('opens senior high periods through the configured semester and term', function () {
    GradingTermSetting::current()->setSeniorHighPeriod('second', 1);

    expect(GradingTerm::seniorHighOpenPeriods())->toHaveCount(4)
        ->and(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem2_term_1')
        ->and(GradingTerm::currentSeniorHighPeriodLabel())->toBe('Second Semester · Term 1')
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe(['shs_sem1_term_1', 'shs_sem1_term_2', 'shs_sem1_term_3']);
});

it('keeps only the first senior high term editable by default', function () {
    expect(GradingTerm::seniorHighOpenPeriods())->toHaveCount(1)
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe([])
        ->and(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem1_term_1')
        ->and(GradingTermSetting::current()->seniorHighSemester())->toBe('first')
        ->and(GradingTermSetting::current()->seniorHighTerm())->toBe(1);
});
