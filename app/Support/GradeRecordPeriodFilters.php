<?php

namespace App\Support;

use App\Models\GradeLevel;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use Illuminate\Http\Request;

class GradeRecordPeriodFilters
{
    public static function resolve(Request $request, ?GradeLevel $grade): array
    {
        $allTerms = GradingTerm::query()
            ->where(fn ($query) => $query->juniorHighAvailable()->orWhere(fn ($query) => $query->seniorHighAvailable()))
            ->with(['juniorHighStatus', 'seniorHighStatus'])
            ->orderBy('sort_order')->orderBy('term_ID')->get();
        $settings = GradingTermSetting::current();
        $defaults = [];
        foreach (['junior_high', 'senior_high'] as $schoolLevel) {
            $available = $allTerms->where('school_level', $schoolLevel);
            $open = $available->first(fn ($term) => $schoolLevel === 'senior_high' ? $term->isSeniorHighOpen() : $term->isJuniorHighOpen());
            $defaults[$schoolLevel] = $open?->term_ID
                ?? ($schoolLevel === 'senior_high' ? $available->firstWhere('term_ID', $settings->term_ID)?->term_ID : null)
                ?? $available->first()?->term_ID;
        }
        $seniorHigh = in_array($grade?->grade_label, ['Grade 11', 'Grade 12'], true);
        $schoolLevel = $grade ? ($seniorHigh ? 'senior_high' : 'junior_high') : null;
        $terms = $schoolLevel ? $allTerms->where('school_level', $schoolLevel)->values() : $allTerms;
        $default = $schoolLevel ? $defaults[$schoolLevel] : 'current';
        $selected = $request->has('term_id') ? $request->input('term_id') : $default;
        if ($selected && $selected !== 'current' && ! $terms->contains('term_ID', $selected)) {
            $selected = $default;
        }
        if ($selected === 'current' && $schoolLevel) {
            $selected = $default;
        }

        return [
            'terms' => $terms,
            'allTerms' => $allTerms,
            'termDefaults' => $defaults,
            'showSemesterFilter' => $seniorHigh,
            'activeSemester' => $settings->seniorHighSemester(),
            'term_id' => $selected,
            'term_ids' => $selected === 'current' ? array_values(array_filter($defaults)) : ($selected ? [(int) $selected] : null),
            'semester' => $seniorHigh ? ($request->has('semester') ? $request->input('semester') : $settings->seniorHighSemester()) : null,
        ];
    }
}
