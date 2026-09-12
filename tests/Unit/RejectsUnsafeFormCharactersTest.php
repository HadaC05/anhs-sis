<?php

use App\Support\RejectsUnsafeFormCharacters;
use Illuminate\Support\Facades\Validator;

test('it rejects values that contain angle brackets', function (string $value) {
    $validator = Validator::make(
        ['first_name' => $value],
        ['first_name' => ['required', 'string']],
    );

    RejectsUnsafeFormCharacters::apply($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('first_name'))->toContain('< or >');
})->with([
    'opening bracket' => 'Juan<script>',
    'closing bracket' => 'Cruz>',
    'both brackets' => '<img>',
]);

test('it allows values without angle brackets', function () {
    $validator = Validator::make(
        ['first_name' => 'Juan Cruz'],
        ['first_name' => ['required', 'string']],
    );

    RejectsUnsafeFormCharacters::apply($validator);

    expect($validator->fails())->toBeFalse();
});
