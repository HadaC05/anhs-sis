<?php

use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all included grading periods while only the open status is editable', function () {
    expect(GradingTerm::gradingOpenPeriods())->toHaveCount(4)
        ->and(GradingTerm::gradingOpenPeriods()[0]['key'])->toBe('term_1')
        ->and(GradingTerm::gradingOpenPeriods()[1]['key'])->toBe('term_2')
        ->and(GradingTerm::lockedGradingPeriodKeys())->toBe(['term_2', 'term_3', 'term_4'])
        ->and(GradingTerm::currentEditablePeriodKey())->toBe('term_1')
        ->and(GradingTerm::currentEditablePeriodLabel())->toBe('Term 1');
});

it('keeps only one junior high term open at a time', function () {
    $termTwo = GradingTerm::query()->where('key', 'term_2')->firstOrFail();
    $termOne = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    $termOne->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::activeId()]);
    $termTwo->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);

    expect(GradingTerm::currentEditablePeriodKey())->toBe('term_2')
        ->and(GradingTerm::isCurrentJuniorHighPeriodOpen())->toBeTrue();
});

it('archives terms beyond the maximum and restores them when the limit is raised', function () {
    GradingTerm::syncActiveStatus(3);

    expect(GradingTerm::query()->where('key', 'term_4')->first()?->isJuniorHighArchived())->toBeTrue()
        ->and(GradingTerm::query()->juniorHighAvailable()->count())->toBe(3);

    GradingTerm::syncActiveStatus(4);

    expect(GradingTerm::query()->where('key', 'term_4')->first()?->isJuniorHighActive())->toBeTruthy()
        ->and(GradingTerm::query()->juniorHighAvailable()->count())->toBe(4);
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
