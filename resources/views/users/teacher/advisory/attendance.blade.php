@extends('users.teacher.layout')

@section('title', 'Attendance Record')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'attendance'])

@push('toasts')
    <x-password-reset-toasts test-prefix="attendance-upload" />
@endpush

@php
    $totalSchoolDays = collect($schoolDays)->sum();
    $sf2Format = $sf2Configuration->selectedFormat();
@endphp

<div>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Attendance views" class="inline-flex gap-1 rounded-lg border border-gray-200 bg-white p-1">
            @foreach(['overview' => 'Overview', 'detailed' => 'Detailed Monthly Record'] as $viewKey => $viewLabel)
                <a href="{{ route('teacher.advisory.attendance', ['section' => $section, 'view' => $viewKey, 'month' => $selectedMonth]) }}" @if($attendanceView === $viewKey) aria-current="page" @endif class="rounded-md px-4 py-2 text-sm font-semibold {{ $attendanceView === $viewKey ? 'bg-[#296374] text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $viewLabel }}</a>
            @endforeach
        </nav>
        <button type="button" id="attendance-upload-trigger" class="inline-flex h-10 items-center rounded-lg bg-[#296374] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-[#1f4e5c]">Upload Attendance Record</button>
    </div>

    @if($attendanceView === 'detailed')
        @include('users.teacher.advisory.partials.attendance-detail')
    @else
    <div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
        <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-4 lg:px-6">
            <h2 class="text-base font-bold text-[#296374]">
                <svg class="hidden h-5 w-5 text-[#296374]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Monthly Attendance Summary
            </h2>
            <p class="mt-1 text-xs text-gray-500">School days are set by the administrator until an SF2 is imported for this section and month. Imported class days and learner counts are also used in SF9.</p>

        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wide text-gray-500">
                        <th class="sticky left-0 z-20 min-w-[180px] bg-gray-50 px-3 py-3 text-left">Learner</th>
                        @foreach($months as $monthKey => $monthLabel)
                            <th colspan="2" class="border-l border-gray-200 px-2 py-3 text-center"><a class="underline decoration-dotted underline-offset-4 hover:text-[#296374]" href="{{ route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => $monthKey]) }}" title="View {{ $monthLabel }} attendance records">{{ $monthLabel }}</a></th>
                        @endforeach
                        <th colspan="2" class="border-l border-gray-200 px-2 py-3 text-center">Total</th>
                    </tr>
                    <tr class="border-b border-gray-200 bg-white text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                        <th class="sticky left-0 z-20 bg-white px-3 py-2 text-left"></th>
                        @foreach($months as $monthKey => $monthLabel)
                            <th class="border-l border-gray-100 px-1 py-2 text-center text-emerald-600">P</th>
                            <th class="px-1 py-2 text-center text-red-500">A</th>
                        @endforeach
                        <th class="border-l border-gray-100 px-1 py-2 text-center text-emerald-600">P</th>
                        <th class="px-1 py-2 text-center text-red-500">A</th>
                    </tr>
                    <tr class="border-b border-gray-200 bg-[#296374]/5 text-[10px] font-bold uppercase tracking-wide text-[#296374]">
                        <td class="sticky left-0 z-20 bg-[#eef5f7] px-3 py-2">School Days</td>
                        @foreach($months as $monthKey => $monthLabel)
                            @php $schoolDayValue = (int) ($schoolDays[$monthKey] ?? 0); @endphp
                            <td colspan="2" class="border-l border-gray-200 px-2 py-2 text-center text-sm font-semibold text-[#296374]">
                                {{ $schoolDayValue > 0 ? $schoolDayValue : '—' }}
                            </td>
                        @endforeach
                        <td colspan="2" class="border-l border-gray-200 px-2 py-2 text-center text-sm font-bold text-[#296374]">
                            {{ $totalSchoolDays > 0 ? $totalSchoolDays : '—' }}
                        </td>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $currentSexGroup = null;
                    @endphp
                    @forelse($rows as $row)
                        @php
                            $summary = $row['summary'];
                            $sexGroup = match (strtolower((string) $row['enrollment']?->student?->sex)) {
                                'male' => 'Male learners',
                                'female' => 'Female learners',
                                default => 'Sex unspecified',
                            };
                        @endphp
                        @if($currentSexGroup !== $sexGroup)
                            @php
                                $currentSexGroup = $sexGroup;
                            @endphp
                            <tr class="bg-[#296374]/10"><td colspan="{{ (count($months) * 2) + 3 }}" class="border-y border-[#296374]/20 px-3 py-2 text-xs font-bold uppercase tracking-widest text-[#296374]">{{ $sexGroup }}</td></tr>
                        @endif
                        <tr class="hover:bg-gray-50/60">
                            <td class="sticky left-0 z-10 bg-white px-3 py-3">
                                <p class="font-semibold text-gray-800">{{ $row['name'] }}</p>
                            </td>
                            @foreach($months as $monthKey => $monthLabel)
                                @php
                                    $record = $row['records']->get($monthKey);
                                    $presentValue = (int) ($record?->days_present ?? 0);
                                    $absentValue = (int) ($record?->days_absent ?? 0);
                                @endphp
                                <td class="border-l border-gray-100 px-2 py-2 text-center font-medium text-emerald-700">
                                    {{ $record ? $presentValue : '—' }}
                                </td>
                                <td class="px-2 py-2 text-center font-medium text-red-600">
                                    {{ $record ? $absentValue : '—' }}
                                </td>
                            @endforeach
                            <td class="border-l border-gray-100 px-2 py-2 text-center font-semibold text-emerald-700">{{ $summary['total_present'] ?: '—' }}</td>
                            <td class="px-2 py-2 text-center font-semibold text-red-600" data-test="attendance-total-absent">{{ $summary['total_absent'] > 0 || $row['has_complete_attendance'] ? $summary['total_absent'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (count($months) * 2) + 3 }}" class="px-4 py-12 text-center text-sm text-gray-400">No enrolled learners in this advisory section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div id="attendance-upload-modal" class="{{ $errors->hasAny(['sf2_file', 'report_month']) ? 'flex' : 'hidden' }} fixed inset-0 z-[100] items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-2xl"><form action="{{ route('teacher.advisory.attendance.sf2', $section) }}" method="POST" enctype="multipart/form-data" id="attendance-upload-form">@csrf
        <div class="mb-4 flex items-center justify-between"><h2 class="text-base font-bold text-gray-800">Upload SF2 Attendance Record</h2><button type="button" id="attendance-upload-cancel" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Close</button></div>
        <label for="attendance-report-month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Month</label><select id="attendance-report-month" name="report_month" required class="mb-4 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">@foreach($months as $monthKey => $monthLabel)<option value="{{ $monthKey }}" @selected((int) old('report_month', $selectedMonth) === (int) $monthKey)>{{ $monthLabel }}</option>@endforeach</select>
        <p class="mb-3 text-xs text-gray-500">{{ $sf2Format['label'] }}: {{ $sf2Format['description'] }} Monthly counts are matched to enrolled learners by name and used in SF9. Scanned images and fractional totals are not supported. @if($sf2Configuration->format === 'lis') In Excel, enter tardiness as T, L, or C; shaded cells are not read. @endif</p>
        <input id="attendance-sf2-file" type="file" name="sf2_file" accept="{{ $sf2Format['accept'] }}" required class="sr-only"><div id="attendance-dropzone" class="cursor-pointer rounded-xl border-2 border-dashed border-[#4bb878]/45 bg-[#f8fcfb] px-6 py-10 text-center transition hover:border-[#4bb878] hover:bg-[#f1faf6]"><svg class="mx-auto h-11 w-11 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 13h16v4H4z"></path></svg><p class="mt-4 text-sm font-medium text-[#4bb878]">Drag and drop the SF2 file here</p><p class="mt-1 text-xs text-gray-400">— OR —</p><button type="button" id="browse-attendance-file" class="mt-4 inline-flex h-9 items-center rounded-md bg-[#4bb878] px-5 text-xs font-bold text-white">Browse Files</button><p id="attendance-file-name" class="mt-4 text-xs font-medium text-gray-600">PDF or Excel (.xls, .xlsx) &middot; Max 10 MB</p></div>
        @error('sf2_file')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        <div id="attendance-submit-row" class="mt-4 hidden justify-end gap-2"><button type="submit" class="h-9 rounded-lg bg-[#296374] px-4 text-xs font-bold text-white">Upload File</button></div>
    </form></div>
</div>
<script>(function(){var modal=document.getElementById('attendance-upload-modal'),trigger=document.getElementById('attendance-upload-trigger'),cancel=document.getElementById('attendance-upload-cancel'),input=document.getElementById('attendance-sf2-file'),zone=document.getElementById('attendance-dropzone'),browse=document.getElementById('browse-attendance-file'),name=document.getElementById('attendance-file-name'),submit=document.getElementById('attendance-submit-row');function fileSelected(file){if(!file)return;name.textContent=file.name;submit.classList.remove('hidden');submit.classList.add('flex');}trigger.addEventListener('click',function(){modal.classList.remove('hidden');modal.classList.add('flex');});cancel.addEventListener('click',function(){modal.classList.add('hidden');modal.classList.remove('flex');});browse.addEventListener('click',function(e){e.stopPropagation();input.click();});zone.addEventListener('click',function(){input.click();});input.addEventListener('change',function(){fileSelected(input.files[0]);});['dragenter','dragover'].forEach(function(eventName){zone.addEventListener(eventName,function(e){e.preventDefault();zone.classList.add('border-[#4bb878]','bg-[#edf9f3]');});});['dragleave','drop'].forEach(function(eventName){zone.addEventListener(eventName,function(e){e.preventDefault();zone.classList.remove('border-[#4bb878]','bg-[#edf9f3]');});});zone.addEventListener('drop',function(e){if(!e.dataTransfer.files.length)return;input.files=e.dataTransfer.files;fileSelected(input.files[0]);});})();</script>
@endsection
