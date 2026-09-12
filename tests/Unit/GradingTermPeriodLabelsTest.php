<?php

use App\Models\GradingTerm;

it('derives term group label from period labels', function () {
    $periods = [
        ['key' => 'term_1', 'label' => 'Term 1'],
        ['key' => 'term_2', 'label' => 'Term 2'],
        ['key' => 'term_3', 'label' => 'Term 3'],
    ];

    expect(GradingTerm::periodGroupLabel($periods))->toBe('Term');
});

it('derives quarter group label from period labels', function () {
    $periods = [
        ['key' => 'q1', 'label' => 'Quarter 1'],
        ['key' => 'q2', 'label' => 'Quarter 2'],
    ];

    expect(GradingTerm::periodGroupLabel($periods))->toBe('Quarter');
});

it('extracts numeric column labels from period names', function () {
    expect(GradingTerm::periodColumnLabel('Term 3'))->toBe('3')
        ->and(GradingTerm::periodColumnLabel('Quarter 2'))->toBe('2');
});

it('builds signature labels with ordinals and group name', function () {
    expect(GradingTerm::periodSignatureLabel('Term 1', 0))->toBe('1st Term')
        ->and(GradingTerm::periodSignatureLabel('Quarter 2', 1))->toBe('2nd Quarter')
        ->and(GradingTerm::periodSignatureLabel('Term 3', 2))->toBe('3rd Term');
});

it('builds period rating labels for terms and quarters', function () {
    expect(GradingTerm::periodRatingLabel([
        ['key' => 'term_1', 'label' => 'Term 1'],
    ]))->toBe('Term Rating')
        ->and(GradingTerm::periodRatingLabel([
            ['key' => 'q1', 'label' => 'Quarter 1'],
        ]))->toBe('Quarterly Rating');
});
