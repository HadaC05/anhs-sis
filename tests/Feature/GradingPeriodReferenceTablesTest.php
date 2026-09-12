<?php

use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use Illuminate\Support\Facades\Schema;

test('grading period statuses are the shared active inactive list', function () {
    expect(Schema::hasTable('grading_period_statuses'))->toBeTrue()
        ->and(Schema::hasColumn('grading_terms', 'is_active'))->toBeFalse()
        ->and(Schema::hasColumn('grading_terms', 'grading_period_status_ID'))->toBeTrue()
        ->and(Schema::hasColumn('grading_semesters', 'grading_period_status_ID'))->toBeTrue()
        ->and(Schema::hasTable('grading_quarters'))->toBeFalse()
        ->and(GradingPeriodStatus::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe([GradingPeriodStatus::ACTIVE, GradingPeriodStatus::INACTIVE]);
});

test('senior high periods reuse junior high terms across two semesters', function () {
    expect(GradingSemester::query()->orderBy('sort_order')->pluck('key')->all())
        ->toBe([GradingSemester::FIRST, GradingSemester::SECOND])
        ->and(array_column(GradingTerm::seniorHighTerms(), 'key'))
        ->toBe(['term_1', 'term_2', 'term_3'])
        ->and(array_column(GradingTerm::seniorHighPeriods(), 'key'))
        ->toBe([
            'shs_sem1_term_1',
            'shs_sem1_term_2',
            'shs_sem1_term_3',
            'shs_sem2_term_1',
            'shs_sem2_term_2',
            'shs_sem2_term_3',
        ]);
});

test('grading term settings keep max terms and reference the current senior high period', function () {
    $settings = GradingTermSetting::current();
    $firstTerm = GradingTerm::query()->where('key', 'term_1')->firstOrFail();
    $firstSemester = GradingSemester::query()->where('key', GradingSemester::FIRST)->firstOrFail();

    expect(Schema::hasColumn('grading_term_settings', 'max_terms'))->toBeTrue()
        ->and(Schema::hasColumn('grading_term_settings', 'open_terms_count'))->toBeTrue()
        ->and(Schema::hasColumn('grading_term_settings', 'shs_semester'))->toBeFalse()
        ->and(Schema::hasColumn('grading_term_settings', 'shs_quarter'))->toBeFalse()
        ->and(Schema::hasColumn('grading_term_settings', 'semester_ID'))->toBeTrue()
        ->and(Schema::hasColumn('grading_term_settings', 'term_ID'))->toBeTrue()
        ->and(Schema::hasColumn('grading_term_settings', 'quarter_ID'))->toBeFalse()
        ->and($settings->max_terms)->toBe(4)
        ->and((int) $settings->semester_ID)->toBe((int) $firstSemester->semester_ID)
        ->and((int) $settings->term_ID)->toBe((int) $firstTerm->term_ID)
        ->and($settings->seniorHighSemester())->toBe(GradingSemester::FIRST)
        ->and($settings->seniorHighTerm())->toBe(1);
});

test('junior high terms use the shared status table instead of a boolean column', function () {
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    expect($term->grading_period_status_ID)->toBe(GradingPeriodStatus::activeId())
        ->and($term->isActive())->toBeTrue()
        ->and($term->status?->name)->toBe('Active')
        ->and(GradingTerm::query()->active()->count())->toBe(4);
});
