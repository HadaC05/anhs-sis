@php
    $contextLines = explode("\n", wordwrap(implode(' | ', $comparisonContext), 110, "\n", true));
    $colors = ['#296374', '#b45309', '#6d28d9', '#047857', '#be123c'];
    $top = 140 + count($contextLines) * 18;
    $groupHeight = 40 + $comparison['years']->count() * 32;
    $height = $top + max(1, $comparison['grades']->count()) * $groupHeight + 45;
    $maximum = max(1, $comparison['grades']->flatMap(fn ($row) => $row['counts'])->max() ?? 0);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 {{ $height }}" role="img" aria-label="Grade-level comparison by school year" style="display:block;width:100%;height:auto;font-family:Arial,sans-serif">
    <title>Grade-level comparison by school year</title>
    <desc>{{ implode('. ', $comparisonContext) }}. Counts reflect current stored enrollment records.</desc>
    <rect width="1000" height="{{ $height }}" fill="#ffffff"/>
    <text x="28" y="36" fill="#1f2937" font-size="22" font-weight="700">Grade-level comparison by school year</text>
    <text x="28" y="60" fill="#4b5563" font-size="13">Enrollment record counts | Generated {{ $generatedAt }}</text>
    @foreach($contextLines as $line)
        <text x="28" y="{{ 82 + $loop->index * 18 }}" fill="#4b5563" font-size="12">{{ $line }}</text>
    @endforeach
    @foreach($comparison['years'] as $year)
        <rect x="{{ 28 + $loop->index * 185 }}" y="{{ $top - 40 }}" width="14" height="14" fill="{{ $colors[$loop->index] }}"/>
        <text x="{{ 49 + $loop->index * 185 }}" y="{{ $top - 28 }}" fill="#374151" font-size="13">{{ $year->school_year }}</text>
    @endforeach
    @forelse($comparison['grades'] as $grade)
        @php($groupTop = $top + $loop->index * $groupHeight)
        <text x="28" y="{{ $groupTop + 16 }}" fill="#1f2937" font-size="16" font-weight="700">{{ $grade['label'] }}</text>
        @foreach($grade['counts'] as $index => $count)
            @php($y = $groupTop + 28 + $index * 32)
            <g>
                <title>{{ $grade['label'] }}, {{ $comparison['years'][$index]->school_year }}: {{ $count }} records</title>
                <text x="28" y="{{ $y + 17 }}" fill="#4b5563" font-size="13">{{ $comparison['years'][$index]->school_year }}</text>
                <rect x="175" y="{{ $y }}" width="660" height="23" rx="3" fill="#f1f5f9"/>
                <rect x="175" y="{{ $y }}" width="{{ round($count / $maximum * 660, 2) }}" height="23" rx="3" fill="{{ $colors[$index] }}"/>
                <text x="855" y="{{ $y + 17 }}" fill="#1f2937" font-size="13">{{ number_format($count) }}</text>
            </g>
        @endforeach
    @empty
        <text x="28" y="{{ $top + 30 }}" fill="#6b7280" font-size="16">No matching enrollment records.</text>
    @endforelse
    <text x="28" y="{{ $height - 18 }}" fill="#6b7280" font-size="12">Common scale starts at zero. Counts reflect stored records, not enrollment at the same date in each year.</text>
</svg>
