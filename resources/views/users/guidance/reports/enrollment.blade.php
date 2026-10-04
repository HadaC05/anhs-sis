@extends('users.guidance.layout')

@section('title', 'Enrollment Reports')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Enrollment Reports</h1>
        <p class="mt-1 text-sm text-gray-600">Enrollment totals, learner profiles, and section assignments.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('guidance.reports.enrollment', array_merge($filters, ['download' => 'summary'])) }}" class="rounded-lg border border-[#296374] bg-white px-4 py-2 text-center text-sm font-semibold text-[#296374] hover:bg-gray-50">Download summary CSV</a>
        <a href="{{ route('guidance.reports.enrollment', array_merge($filters, ['download' => 'csv'])) }}" class="rounded-lg bg-[#296374] px-4 py-2 text-center text-sm font-semibold text-white hover:bg-[#205060]">Download records CSV</a>
    </div>
</div>

<form method="GET" action="{{ route('guidance.reports.enrollment') }}" class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $filterOptions = [
                'academic_year_id' => ['School year', 'All school years', $years->pluck('school_year', 'SY_ID')],
                'grade_id' => ['Grade level', 'All grade levels', $grades->pluck('grade_label', 'grade_ID')],
                'status' => ['Enrollment status', 'All statuses', $statuses],
                'learner_type' => ['Learner type', 'All learner types', $learnerTypes],
                'section' => ['Section assignment', 'All assignments', ['assigned' => 'Assigned', 'unassigned' => 'Unassigned']],
            ];
        @endphp
        @foreach($filterOptions as $key => [$label, $placeholder, $options])
            <div>
                <label for="{{ $key }}" class="mb-1 block text-xs font-semibold text-gray-600">{{ $label }}</label>
                <select id="{{ $key }}" name="{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">{{ $placeholder }}</option>
                    @foreach($options as $value => $name)
                        <option value="{{ $value }}" @selected((string) ($filters[$key] ?? '') === (string) $value)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div>
            <label for="search" class="mb-1 block text-xs font-semibold text-gray-600">Learner name or LRN</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search name or LRN" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>
    @if($errors->any())
        <p role="alert" class="mt-3 text-sm text-red-700">{{ $errors->first() }}</p>
    @endif
    <div class="mt-4 flex items-center gap-4">
        <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060]">Apply filters</button>
        <a href="{{ route('guidance.reports.enrollment') }}" class="text-sm font-semibold text-gray-600 hover:underline">Reset</a>
    </div>
</form>

<div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-3">
    @foreach($summary as $label => $count)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($count) }}</p>
        </div>
    @endforeach
</div>
<div class="mb-6 rounded-lg border border-[#296374]/20 bg-white p-4 text-sm text-gray-600">
    <strong class="text-[#296374]">{{ $years->firstWhere('SY_ID', $filters['academic_year_id'] ?? null)?->school_year ?? 'All school years' }}</strong>
    &middot; Generated {{ now()->format('M d, Y, h:i A') }}.
    All totals and downloads follow the applied filters. Breakdowns count enrollment records; a learner may have more than one record across school years. Statuses reflect current records.
</div>

<section class="mb-8 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-gray-800">School-year comparison</h2>
        <a href="{{ route('guidance.reports.enrollment', array_merge($filters, ['download' => 'comparison'])) }}" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060]">Download comparison CSV</a>
    </div>
    <p class="text-sm text-gray-600">Shows up to five school years ending with the selected year, or the latest five when all years are selected. Other filters apply across these years. Counts reflect stored records, not enrollment at the same date in each year. Zero means no matching records.</p>
    @if($comparison['years']->count() < 2)
        <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">At least two school years are needed to compare changes. Select a later school year if available.</p>
    @endif
    @foreach(['year-trend' => 'Enrollment by school year', 'year-grade' => 'Grade-level comparison by school year'] as $key => $heading)
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <h3 class="text-sm font-bold text-gray-800">{{ $heading }}</h3>
                <a href="{{ route('guidance.reports.enrollment', array_merge($filters, ['download' => 'chart', 'chart' => $key])) }}" class="rounded-lg border border-[#296374]/30 px-3 py-2 text-xs font-semibold text-[#296374] hover:bg-gray-50" aria-label="Download {{ strtolower($heading) }} as SVG">Download SVG</a>
            </div>
            <div class="overflow-x-auto"><div class="min-w-[640px]">
                @if($key === 'year-trend')
                    @include('users.guidance.reports.partials.enrollment-chart', ['title' => $heading, 'rows' => $comparison['totals'], 'total' => $comparison['totals']->sum('total'), 'reportContext' => $comparisonContext])
                @else
                    @include('users.guidance.reports.partials.enrollment-year-grade-chart')
                @endif
            </div></div>
        </div>
    @endforeach
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full whitespace-nowrap text-left text-sm">
            <caption class="px-5 py-3 text-left text-xs text-gray-600">Change is measured against the previous listed school year. Percentage change is unavailable when its count is zero.</caption>
            <thead class="bg-gray-50 text-xs text-gray-500"><tr><th scope="col" class="px-5 py-3">School year</th><th scope="col" class="px-5 py-3 text-right">Records</th><th scope="col" class="px-5 py-3 text-right">Change</th><th scope="col" class="px-5 py-3 text-right">Change (%)</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($comparison['totals'] as $row)
                    <tr>
                        <th scope="row" class="px-5 py-3 font-semibold">{{ $row->label }}</th>
                        <td class="px-5 py-3 text-right">{{ number_format($row->total) }}</td>
                        <td class="px-5 py-3 text-right">{{ $row->change === null ? 'N/A' : ($row->change > 0 ? '+' : '').number_format($row->change) }}</td>
                        <td class="px-5 py-3 text-right">{{ $row->percent === null ? 'N/A' : ($row->percent > 0 ? '+' : '').number_format($row->percent, 1).'%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-6 text-center text-gray-500">No school years available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<div class="mb-6">
    <h2 class="text-lg font-bold text-gray-800">Enrollment charts</h2>
    <p class="mt-1 text-sm text-gray-600">Compare counts and shares for the applied filters. Download each chart as an SVG image for printing or sharing.</p>
</div>
<div class="mb-6 grid gap-5 xl:grid-cols-2">
    @foreach($chartTitles as $chartKey => $chartTitle)
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <h3 class="text-sm font-bold text-gray-800">{{ $chartTitle }}</h3>
                <a href="{{ route('guidance.reports.enrollment', array_merge($filters, ['download' => 'chart', 'chart' => $chartKey])) }}" class="rounded-lg border border-[#296374]/30 px-3 py-2 text-xs font-semibold text-[#296374] hover:bg-gray-50" aria-label="Download {{ strtolower($chartTitle) }} chart as SVG">Download SVG</a>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[540px]">
                    @include('users.guidance.reports.partials.enrollment-chart', ['title' => $chartTitle, 'rows' => $breakdowns[$chartTitle]])
                </div>
            </div>
        </section>
    @endforeach
</div>
<div class="mb-4">
    <h2 class="text-lg font-bold text-gray-800">Summary tables</h2>
    <p class="mt-1 text-sm text-gray-600">All six breakdowns and summary totals are included in the summary CSV download.</p>
</div>
<div class="mb-6 grid gap-5 lg:grid-cols-2">
    @foreach($breakdowns as $title => $rows)
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <h2 class="border-b border-gray-100 px-5 py-4 text-sm font-bold text-gray-800">{{ $title }}</h2>
            <div class="max-h-80 overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500"><tr><th scope="col" class="px-5 py-2">{{ $title }}</th><th scope="col" class="px-3 py-2 text-right">Records</th><th scope="col" class="px-5 py-2 text-right">Share</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr><td class="px-5 py-3 text-gray-700">{{ $row->label }}</td><td class="px-3 py-3 text-right font-semibold">{{ number_format($row->total) }}</td><td class="px-5 py-3 text-right text-gray-500">{{ number_format($total ? $row->total / $total * 100 : 0, 1) }}%</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-6 text-center text-gray-500">No matching enrollment records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>

<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="font-bold text-gray-800">Detailed enrollment records</h2>
        <p class="mt-1 text-xs text-gray-500">{{ number_format($records->total()) }} matching records. Download CSV includes all matching records.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500"><tr>
                @foreach(['Learner / LRN', 'School year', 'Grade / cluster', 'Section', 'Sex', 'Learner type', 'Status', 'Registered', 'Details'] as $heading)
                    <th scope="col" class="px-4 py-3">{{ $heading }}</th>
                @endforeach
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><p class="font-semibold text-gray-800">{{ $record->last_name }}, {{ $record->first_name }} {{ $record->middle_name }} {{ $record->suffix }}</p><p class="text-xs text-gray-500">{{ $record->lrn ?: 'No LRN' }}</p></td>
                        <td class="px-4 py-3">{{ $record->school_year ?? 'Unspecified' }}</td>
                        <td class="px-4 py-3">{{ $record->grade_label ?? 'Unspecified' }}<p class="text-xs text-gray-500">{{ $record->cluster_name ?? 'No cluster / not applicable' }}</p></td>
                        <td class="px-4 py-3">{{ $record->section_name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3">{{ ucfirst($record->sex ?: 'Unspecified') }}</td>
                        <td class="px-4 py-3">{{ $record->learner_type_name ?? 'Unspecified' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-[#296374]/10 px-2 py-1 text-xs font-semibold text-[#296374]">{{ $record->status_name ?? 'Unspecified' }}</span></td>
                        <td class="px-4 py-3">{{ $record->created_at ? \Illuminate\Support\Carbon::parse($record->created_at)->format('M d, Y') : 'Unavailable' }}</td>
                        <td class="px-4 py-3"><a href="{{ route('guidance.enrollments.show', $record->enrollment_ID) }}" class="font-semibold text-[#296374] hover:underline" aria-label="View enrollment for {{ $record->first_name }} {{ $record->last_name }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-10 text-center text-gray-500">No enrollment records match these filters. Try another school year or reset the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-100 px-5 py-4">{{ $records->links() }}</div>
</section>
@endsection
