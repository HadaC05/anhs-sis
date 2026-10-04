<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class EnrollmentYearComparison
{
    public static function build(Builder $query, Collection $years, int|string $selectedYear): array
    {
        $anchor = $years->firstWhere('SY_ID', $selectedYear);
        $years = $years->filter(fn ($year) => ! $anchor || $year->start_date <= $anchor->start_date)
            ->sortByDesc('start_date')->take(5)->sortBy('start_date')->values();
        $counts = (clone $query)->whereIn('e.SY_ID', $years->pluck('SY_ID'))
            ->select('e.SY_ID', 'g.grade_label')->selectRaw('COUNT(*) as total')
            ->groupBy('e.SY_ID', 'g.grade_label')->get();
        $previous = null;
        $totals = $years->map(function ($year) use ($counts, &$previous) {
            $total = (int) $counts->where('SY_ID', $year->SY_ID)->sum('total');
            $row = (object) [
                'label' => $year->school_year, 'total' => $total,
                'change' => $previous === null ? null : $total - $previous,
                'percent' => $previous ? round(($total - $previous) / $previous * 100, 1) : null,
            ];
            $previous = $total;

            return $row;
        });
        $grades = $counts->groupBy(fn ($row) => $row->grade_label ?: 'Unspecified')
            ->map(fn ($rows, $label) => [
                'label' => $label,
                'counts' => $years->map(fn ($year) => (int) $rows->where('SY_ID', $year->SY_ID)->sum('total'))->all(),
            ])->sortBy('label', SORT_NATURAL)->values();

        return compact('years', 'totals', 'grades');
    }
}
