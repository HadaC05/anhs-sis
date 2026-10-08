<?php

use App\Support\LearnerPermanentRecordBuilder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('maps subjects to sf10 learning area slots', function () {
    expect(LearnerPermanentRecordBuilder::subjectSlot('Filipino'))->toBe('filipino')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Values Education'))->toBe('values_education')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Edukasyon sa Pagpapakatao'))->toBe('values_education')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Technology and Livelihood Education'))->toBe('tle')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('TLE 7'))->toBe('tle')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('TLE 8'))->toBe('tle')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('TLE 9'))->toBe('tle')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('EPP 7'))->toBe('tle')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Music'))->toBe('music')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Arts'))->toBe('arts');
});

it('builds scholastic rows for configured term count', function () {
    $periods = [
        ['key' => 'term_1', 'label' => 'Term 1'],
        ['key' => 'term_2', 'label' => 'Term 2'],
        ['key' => 'term_3', 'label' => 'Term 3'],
    ];

    $record = LearnerPermanentRecordBuilder::emptyScholasticRecord($periods);

    expect($record['subjects'])->toHaveCount(count(LearnerPermanentRecordBuilder::officialSubjectRows()))
        ->and(array_keys($record['subjects'][0]['quarters']))->toBe(['term_1', 'term_2', 'term_3']);
});

it('pads student cards to the five scholastic blocks in the JHS form', function () {
    $periods = [
        ['key' => 'term_1', 'label' => 'Term 1'],
        ['key' => 'term_2', 'label' => 'Term 2'],
        ['key' => 'term_3', 'label' => 'Term 3'],
    ];

    $student = new \App\Models\Student([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'lrn' => '123456789012',
    ]);

    $card = LearnerPermanentRecordBuilder::buildStudentCard($student, collect(), [], []);
    $html = view('users.teacher.advisory.sf10-print', [
        'cards' => collect([$card]),
        'periods' => $periods,
    ])->render();

    expect($card['scholastic_records'])->toHaveCount(5)
        ->and($card['full_name'])->toBe('Juan Dela Cruz')
        ->and($html)->toContain('sf10-deped-seal.png', 'sf10-deped-wordmark.png', 'SF10-JHS Page 2 of 2');
});

it('uses recorded fourth-quarter grades for an older JHS record', function () {
    $grade = new \App\Models\StudentSubjectGrade(['numeric_grade' => 88]);
    $grade->setRelation('term', new \App\Models\GradingTerm(['key' => 'term_4', 'label' => 'Term 4']));

    $record = LearnerPermanentRecordBuilder::buildScholasticRecord(
        new \App\Models\Enrollment,
        null,
        collect(),
        collect([$grade]),
        [
            ['key' => 'term_1', 'label' => 'Term 1'],
            ['key' => 'term_2', 'label' => 'Term 2'],
            ['key' => 'term_3', 'label' => 'Term 3'],
        ],
    );

    $html = view('users.teacher.advisory.partials.sf10-scholastic-record', [
        'record' => $record,
        'periods' => [['key' => 'term_1', 'label' => 'Term 1']],
    ])->render();

    expect(array_column($record['periods'], 'label'))->toBe(['Quarter 1', 'Quarter 2', 'Quarter 3', 'Quarter 4'])
        ->and($html)->toContain('Quarterly Rating', 'style="width: 7%">4</th>');
});
