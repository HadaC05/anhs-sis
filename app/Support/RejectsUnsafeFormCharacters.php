<?php

namespace App\Support;

use Illuminate\Validation\Validator;

class RejectsUnsafeFormCharacters
{
    public static function apply(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($validator->getData() as $attribute => $value) {
                if (! is_string($value) || $value === '') {
                    continue;
                }

                if (preg_match('/[<>]/u', $value) !== 1) {
                    continue;
                }

                $validator->errors()->add(
                    $attribute,
                    'The '.$validator->getDisplayableAttribute($attribute).' cannot contain < or > characters.'
                );
            }
        });
    }
}
