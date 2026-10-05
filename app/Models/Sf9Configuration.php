<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sf9Configuration extends Model
{
    protected $fillable = ['junior_high', 'senior_high'];

    public static function formats(): array
    {
        return [
            'junior_high' => [
                'jhs_legacy' => 'Original SF9 — Learner’s Progress Report Card',
                'jhs_2026' => 'Updated SF9 — SY 2026–2027 Performance Report',
            ],
            'senior_high' => [
                'shs_current' => 'Current SF9 — Senior High Performance Report',
            ],
        ];
    }

    public static function usesTeacherComments(?Section $section): bool
    {
        return $section !== null && ! GradingTerm::isSeniorHighSection($section)
            && self::current()->junior_high === 'jhs_2026';
    }

    public static function advisoryTabLabel(Section $section): string
    {
        return self::usesTeacherComments($section) ? 'Teacher Remarks' : 'Observed Values';
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self([
            'junior_high' => 'jhs_legacy',
            'senior_high' => 'shs_current',
        ]);
    }
}
