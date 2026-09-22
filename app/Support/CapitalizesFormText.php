<?php

namespace App\Support;

class CapitalizesFormText
{
    /**
     * @var list<string>
     */
    public const FIELDS = [
        'first_name',
        'middle_name',
        'last_name',
        'birthplace',
        'mother_tongue',
        'last_school_attended',
        'father_fname',
        'father_mname',
        'father_lname',
        'mother_fname',
        'mother_mname',
        'mother_lname',
        'guardian_fname',
        'guardian_mname',
        'guardian_lname',
    ];

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    public static function apply(array $values, ?array $fields = null): array
    {
        foreach ($fields ?? self::FIELDS as $field) {
            if (! array_key_exists($field, $values) || ! is_string($values[$field])) {
                continue;
            }

            if (trim($values[$field]) === '') {
                continue;
            }

            $values[$field] = self::titleCasePreservingUppercaseRuns($values[$field]);
        }

        return $values;
    }

    /**
     * Title-case ordinary words while retaining deliberate uppercase sequences,
     * such as initials and acronyms (for example, "ANHS" or "McDONALD").
     */
    private static function titleCasePreservingUppercaseRuns(string $value): string
    {
        return preg_replace_callback(
            '/\S+/u',
            static function (array $matches): string {
                $word = $matches[0];

                return preg_match('/\p{Lu}{2,}/u', $word)
                    ? $word
                    : mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            },
            $value,
        ) ?? $value;
    }
}
