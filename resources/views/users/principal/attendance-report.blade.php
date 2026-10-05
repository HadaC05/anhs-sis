@extends('users.principal.layout')
@section('title', 'Attendance and Learner Movement')
@section('content')
<style>
    .attendance-table th, .attendance-table td { padding: .65rem .8rem; border-bottom: 1px solid #e5e7eb; text-align: right; white-space: nowrap; }
    .attendance-table th:first-child, .attendance-table td:first-child { text-align: left; }
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        aside, header, #sidebar-backdrop, .report-actions, .report-filters { display: none !important; }
        .principal-layout > div { padding-top: 0 !important; background: white !important; min-height: 0 !important; }
        .principal-content { container-type: normal; }
        .app-main, .principal-content { margin: 0 !important; padding: 0 !important; max-width: none !important; }
        .report-scroll { overflow: visible !important; }
        .attendance-table { font-size: 8pt !important; width: 100%; }
        .attendance-table th, .attendance-table td { white-space: normal; padding: 4px; }
        tr { break-inside: avoid; } thead { display: table-header-group; }
    }
</style>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Attendance and Learner Movement</h1>
        <p class="mt-1 text-sm text-gray-600">SF4-inspired section summary &middot; {{ $school?->name ?? config('app.name') }}</p>
        <p class="mt-1 text-sm font-semibold text-[#296374]">{{ $year?->school_year ?? 'No school year configured' }} &middot; {{ $months[$month] ?? 'No reporting month' }}</p>
    </div>
    <div class="report-actions flex gap-2">
        <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold">Print report</button>
        <a href="{{ route('principal.reports.attendance', array_merge($filters, ['download' => 'csv'])) }}" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Download CSV</a>
    </div>
</div>
<form method="GET" action="{{ route('principal.reports.attendance') }}" class="report-filters mb-6 flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div><label for="academic_year_id" class="mb-1 block text-xs font-semibold text-gray-600">School year</label>
        <select id="academic_year_id" name="academic_year_id" onchange="document.getElementById('report-month').value = ''" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            @foreach($years as $option)<option value="{{ $option->SY_ID }}" @selected($year?->SY_ID === $option->SY_ID)>{{ $option->school_year }}</option>@endforeach
        </select>
    </div>
    <div><label for="report-month" class="mb-1 block text-xs font-semibold text-gray-600">Attendance month</label>
        <select id="report-month" name="month" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Default month</option>
            @foreach($months as $id => $label)<option value="{{ $id }}" @selected($month === $id)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div><label for="grade_id" class="mb-1 block text-xs font-semibold text-gray-600">Grade level</label>
        <select id="grade_id" name="grade_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">All grade levels</option>
            @foreach($grades as $grade)<option value="{{ $grade->grade_ID }}" @selected((string) $filters['grade_id'] === (string) $grade->grade_ID)>{{ $grade->grade_label }}</option>@endforeach
        </select>
    </div>
    <button class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Apply</button>
    <a href="{{ route('principal.reports.attendance') }}" class="px-3 py-2 text-sm text-gray-600">Reset</a>
    @if($errors->any())<p role="alert" class="w-full text-sm text-red-700">{{ $errors->first() }}</p>@endif
</form>
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach(['Currently registered' => number_format($summary['registered']), 'Attendance records' => $summary['recorded'].' / '.$summary['learners'], 'Recorded attendance rate' => $summary['rate'] === null ? '—' : number_format($summary['rate'], 2).'%', 'Sections' => $rows->where('sex', 'Total')->count()] as $label => $value)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-[#296374]">{{ $value }}</p></div>
    @endforeach
</div>
<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
    <p><strong>Reading this report:</strong> Attendance is for the selected month. Registered learners and movement counts reflect the latest saved status in the selected school year; dated monthly and cumulative movement history is not available.</p>
    <p class="mt-2">Daily average = recorded days present / school days. Attendance rate = recorded days present / recorded present and absent days. Missing records are not treated as absences. Partial records produce partial averages; a dash means data or school days are unavailable. Transferee learners are identified by learner type, not a dated transfer-in event.</p>
</div>
@php
    $groups = [
        ['title' => 'Monthly attendance', 'columns' => ['registered' => 'Registered now', 'recorded' => 'Records / learners', 'school_days' => 'School days', 'present' => 'Days present', 'absent' => 'Days absent', 'average' => 'Daily average', 'rate' => 'Attendance %']],
        ['title' => 'Learner movement — current status', 'columns' => ['transferees' => 'Transferee learners', 'transferred_out' => 'Transferred out', 'dropped_out' => 'Dropped out', 'withdrawn' => 'Withdrawn']],
    ];
@endphp
@foreach($groups as $group)
<section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <h2 class="border-b border-gray-200 px-5 py-4 text-lg font-bold text-gray-800">{{ $group['title'] }}</h2>
    <div class="report-scroll overflow-x-auto">
        <table class="attendance-table w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-600"><tr><th scope="col">Grade / Section / Adviser</th><th scope="col">Sex</th>@foreach($group['columns'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="{{ $row['sex'] === 'Total' ? 'bg-gray-50 font-semibold' : '' }}">
                        <td>@if($row['sex'] === 'Male')<span class="font-semibold">{{ $row['grade'] }} / {{ $row['section'] }}</span><span class="block text-xs text-gray-500">{{ $row['adviser'] }}</span>@elseif($row['sex'] === 'Total')Section total @endif</td>
                        <td>{{ $row['sex'] }}</td>
                        @foreach($group['columns'] as $key => $label)
                            <td>@if($key === 'recorded'){{ $row['recorded'] }} / {{ $row['learners'] }}@elseif($row[$key] === null || ($key === 'school_days' && !$row[$key]))—@elseif(in_array($key, ['average', 'rate'])){{ number_format($row[$key], 2) }}{{ $key === 'rate' ? '%' : '' }}@else{{ number_format($row[$key]) }}@endif</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($group['columns']) + 2 }}" class="py-10 text-gray-500">No sections found for the selected school year and grade level.</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot class="bg-gray-100 font-bold"><tr><td>Report total</td><td>All</td>
                @foreach($group['columns'] as $key => $label)
                    <td>@if($key === 'recorded'){{ $summary['recorded'] }} / {{ $summary['learners'] }}@elseif($summary[$key] === null)—@elseif(in_array($key, ['average', 'rate'])){{ number_format($summary[$key], 2) }}{{ $key === 'rate' ? '%' : '' }}@else{{ number_format($summary[$key]) }}@endif</td>
                @endforeach
            </tr></tfoot>
            @endif
        </table>
    </div>
</section>
@endforeach
<p class="text-xs text-gray-500">Totals include learners whose sex is unspecified. Attendance scope includes current learners and departed learners with retained enrollment records. This report follows the SF4 summary structure and is not an official SF4 export.</p>
@endsection
