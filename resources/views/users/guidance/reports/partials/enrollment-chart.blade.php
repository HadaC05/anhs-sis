@php
    $contextLines = explode("\n", wordwrap(implode(' | ', $reportContext), 105, "\n", true));
    $chartTop = 95 + count($contextLines) * 18;
    $chartHeight = max(1, $rows->count()) * 58;
    $height = $chartTop + $chartHeight + 60;
    $maximum = max(1, $rows->max('total') ?? 0);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 {{ $height }}" role="img" aria-label="{{ $title }}: {{ $total }} enrollment records" style="display:block;width:100%;height:auto;font-family:Arial,sans-serif">
    <title>{{ $title }} — {{ $reportName ?? 'Enrollment report' }}</title>
    <desc>Horizontal bars show enrollment record counts. {{ implode('. ', $reportContext) }}. Generated {{ $generatedAt }}.</desc>
    <rect width="900" height="{{ $height }}" fill="#ffffff"/>
    <text x="28" y="36" fill="#1f2937" font-size="22" font-weight="700">{{ $title }}</text>
    <text x="28" y="60" fill="#4b5563" font-size="13">{{ number_format($total) }} enrollment records · Generated {{ $generatedAt }}</text>
    @foreach($contextLines as $line)
        <text x="28" y="{{ 82 + $loop->index * 18 }}" fill="#4b5563" font-size="12">{{ $line }}</text>
    @endforeach
    @forelse($rows as $row)
        @php($y = $chartTop + $loop->index * 58)
        <g>
            <title>{{ $row->label }}: {{ $row->total }} records ({{ number_format($total ? $row->total / $total * 100 : 0, 1) }}%)</title>
            <text x="28" y="{{ $y + 23 }}" fill="#374151" font-size="14">{{ $row->label }}</text>
            <rect x="225" y="{{ $y }}" width="490" height="34" rx="5" fill="#f1f5f9"/>
            <rect x="225" y="{{ $y }}" width="{{ round($row->total / $maximum * 490, 2) }}" height="34" rx="5" fill="#296374"/>
            <text x="735" y="{{ $y + 23 }}" fill="#1f2937" font-size="14" font-weight="700">{{ number_format($row->total) }} ({{ number_format($total ? $row->total / $total * 100 : 0, 1) }}%)</text>
        </g>
    @empty
        <text x="28" y="{{ $chartTop + 25 }}" fill="#6b7280" font-size="16">No matching enrollment records.</text>
    @endforelse
    <text x="28" y="{{ $height - 22 }}" fill="#6b7280" font-size="12">Bar length represents record count, starting at zero. Percentages use the filtered total.</text>
</svg>
