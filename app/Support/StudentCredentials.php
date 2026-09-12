<?php

namespace App\Support;

use App\Models\Enrollment;
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

    public static function enrollmentYear(?Enrollment $enrollment): int
    {
        $enrollment?->loadMissing('academicYear');

        return (int) ($enrollment?->academicYear?->start_date?->year ?? now()->year);
    }

    public static function passwordFormatExample(int $enrollmentYear): string
    {
        return 'jado'.$enrollmentYear.'anhs';
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
