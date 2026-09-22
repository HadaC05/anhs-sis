<?php

use App\Support\CapitalizesFormText;

it('capitalizes each word in the configured text fields', function () {
    $result = CapitalizesFormText::apply([
        'first_name' => 'juan miguel',
        'middle_name' => 'dela',
        'last_name' => 'cruz santos',
        'birthplace' => 'butuan city',
        'mother_tongue' => 'cebuano',
        'last_school_attended' => 'agusan elementary school',
        'father_fname' => 'pedro jose',
        'email' => 'juan.cruz@example.com',
        'suffix' => 'Jr.',
    ]);

    expect($result['first_name'])->toBe('Juan Miguel')
        ->and($result['middle_name'])->toBe('Dela')
        ->and($result['last_name'])->toBe('Cruz Santos')
        ->and($result['birthplace'])->toBe('Butuan City')
        ->and($result['mother_tongue'])->toBe('Cebuano')
        ->and($result['last_school_attended'])->toBe('Agusan Elementary School')
        ->and($result['father_fname'])->toBe('Pedro Jose')
        ->and($result['email'])->toBe('juan.cruz@example.com')
        ->and($result['suffix'])->toBe('Jr.');
});

it('leaves empty, missing, and non-string values unchanged', function () {
    $result = CapitalizesFormText::apply([
        'first_name' => '',
        'middle_name' => null,
        'last_name' => '  ',
        'grade_level' => 7,
    ]);

    expect($result['first_name'])->toBe('')
        ->and($result['middle_name'])->toBeNull()
        ->and($result['last_name'])->toBe('  ')
        ->and($result['grade_level'])->toBe(7);
});

it('preserves words with consecutive uppercase letters', function () {
    $result = CapitalizesFormText::apply([
        'first_name' => 'maria ANNE',
        'last_name' => 'mcdonald MCDONALD',
        'last_school_attended' => 'agusan NHS',
    ]);

    expect($result['first_name'])->toBe('Maria ANNE')
        ->and($result['last_name'])->toBe('Mcdonald MCDONALD')
        ->and($result['last_school_attended'])->toBe('Agusan NHS');
});
