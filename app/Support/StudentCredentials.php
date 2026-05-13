<?php

namespace App\Support;

use Illuminate\Support\Str;

class StudentCredentials
{
    public static function usernameFromLrn(string $lrn): string
    {
        return trim($lrn);
    }

    public static function defaultPassword(string $firstName, string $lastName, int $enrollmentYear): string
    {
        return Str::lower(
            self::nameSegment($firstName)
            .self::nameSegment($lastName)
            .$enrollmentYear
            .'anhs'
        );
    }

    private static function nameSegment(string $value): string
    {
        return Str::of($value)
            ->transliterate()
            ->lower()
            ->replaceMatches('/[^a-z]/', '')
            ->substr(0, 2)
            ->value();
    }
}
