<?php

use App\Support\LearnerPermanentRecordBuilder;

it('maps subjects to sf10 learning area slots', function () {
    expect(LearnerPermanentRecordBuilder::subjectSlot('Filipino'))->toBe('filipino')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Values Education'))->toBe('values_education')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Edukasyon sa Pagpapakatao'))->toBe('values_education')
        ->and(LearnerPermanentRecordBuilder::subjectSlot('Technology and Livelihood Education'))->toBe('tle')
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

it('pads student cards to six scholastic record blocks', function () {
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

    expect($card['scholastic_records'])->toHaveCount(6)
        ->and($card['full_name'])->toBe('Juan Dela Cruz');
});
