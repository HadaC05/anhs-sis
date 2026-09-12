@extends('users.teacher.layout')

@section('title', 'Attendance Record')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'attendance'])

@if(session('status'))
    <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

@php
    $totalSchoolDays = collect($schoolDays)->sum();
@endphp

<div>
    <aside class="hidden">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-3">
                <h3 class="flex items-center gap-2 text-sm font-bold text-[#296374]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    Upload SF2
                </h3>
            </div>
            <form action="{{ route('teacher.advisory.attendance.sf2', $section) }}" method="POST" enctype="multipart/form-data" class="space-y-4 p-4">
                @csrf
                <div>
                    <label for="report_month" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Month</label>
                    <select id="report_month" name="report_month" required class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20">
                        @foreach($months as $monthKey => $monthLabel)
                            <option value="{{ $monthKey }}" @selected((int) old('report_month') === (int) $monthKey)>{{ $monthLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sf2_file" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">SF2 PDF</label>
                    <input id="sf2_file" type="file" name="sf2_file" accept="application/pdf,.pdf" required class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-[#296374]/10 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#296374] hover:file:bg-[#296374]/15">
                </div>
                <p class="text-[11px] leading-relaxed text-gray-500">Upload the DepEd SF2 Daily Attendance Report. Counts will appear in the summary once SF2 import is processed.</p>
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#296374] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1e4d5c]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload SF2
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-3">
                <h3 class="text-sm font-bold text-gray-700">Recent SF2 Uploads</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($sf2Uploads as $upload)
                    <div class="px-4 py-3">
                        <p class="truncate text-xs font-semibold text-gray-800" title="{{ $upload->original_filename }}">{{ $upload->original_filename }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-[10px] text-gray-500">
                            @if($upload->report_month)
                                <span>{{ $months[$upload->report_month] ?? 'Month '.$upload->report_month }}</span>
                            @endif
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 font-semibold uppercase tracking-wide text-amber-700">{{ $upload->status }}</span>
                        </div>
                        @if($upload->parse_notes)
                            <p class="mt-1 text-[10px] leading-relaxed text-gray-400">{{ $upload->parse_notes }}</p>
                        @endif
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs text-gray-400">No SF2 files uploaded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-500">SF9 Attendance</p>
            <p class="text-xs leading-relaxed text-gray-600">Monthly counts below are derived from uploaded SF2 reports and reflected on School Form 9 when you print learner records.</p>
        </div>
    </aside>

    <div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
        <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-4 lg:px-6">
            <h2 class="hidden">
                <svg class="h-5 w-5 text-[#296374]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Monthly Attendance Summary
            </h2>
            <p class="hidden">School days are set by the administrator.</p>
            <div class="flex justify-end"><button type="button" id="attendance-upload-trigger" class="inline-flex h-10 items-center rounded-lg bg-[#296374] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-[#1f4e5c]">Upload Attendance Record</button></div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wide text-gray-500">
                        <th class="sticky left-0 z-20 min-w-[180px] bg-gray-50 px-3 py-3 text-left">Learner</th>
                        @foreach($months as $monthKey => $monthLabel)
                            <th colspan="2" class="border-l border-gray-200 px-2 py-3 text-center">{{ $monthLabel }}</th>
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
                                    {{ $presentValue > 0 ? $presentValue : '—' }}
                                </td>
                                <td class="px-2 py-2 text-center font-medium text-red-600">
                                    {{ $absentValue > 0 ? $absentValue : '—' }}
                                </td>
                            @endforeach
                            <td class="border-l border-gray-100 px-2 py-2 text-center font-semibold text-emerald-700">{{ $summary['total_present'] ?: '—' }}</td>
                            <td class="px-2 py-2 text-center font-semibold text-red-600">{{ $summary['total_absent'] ?: '—' }}</td>
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
</div>

<div id="attendance-upload-modal" class="{{ $errors->has('sf2_file') ? 'flex' : 'hidden' }} fixed inset-0 z-[100] items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-2xl"><form action="{{ route('teacher.advisory.attendance.sf2', $section) }}" method="POST" enctype="multipart/form-data" id="attendance-upload-form">@csrf
        <div class="mb-4 flex items-center justify-between"><h2 class="text-base font-bold text-gray-800">Upload Attendance Record</h2><button type="button" id="attendance-upload-cancel" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Close</button></div>
        <label for="attendance-report-month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Month</label><select id="attendance-report-month" name="report_month" required class="mb-4 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">@foreach($months as $monthKey => $monthLabel)<option value="{{ $monthKey }}" @selected((int) old('report_month') === (int) $monthKey)>{{ $monthLabel }}</option>@endforeach</select>
        <input id="attendance-sf2-file" type="file" name="sf2_file" accept="application/pdf,.pdf" required class="sr-only"><div id="attendance-dropzone" class="cursor-pointer rounded-xl border-2 border-dashed border-[#4bb878]/45 bg-[#f8fcfb] px-6 py-10 text-center transition hover:border-[#4bb878] hover:bg-[#f1faf6]"><svg class="mx-auto h-11 w-11 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 13h16v4H4z"></path></svg><p class="mt-4 text-sm font-medium text-[#4bb878]">Drag and drop the SF2 PDF here</p><p class="mt-1 text-xs text-gray-400">— OR —</p><button type="button" id="browse-attendance-file" class="mt-4 inline-flex h-9 items-center rounded-md bg-[#4bb878] px-5 text-xs font-bold text-white">Browse Files</button><p id="attendance-file-name" class="mt-4 text-xs font-medium text-gray-600">PDF only · Max 15 MB</p></div>
        @error('sf2_file')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        <div id="attendance-submit-row" class="mt-4 hidden justify-end gap-2"><button type="submit" class="h-9 rounded-lg bg-[#296374] px-4 text-xs font-bold text-white">Upload File</button></div>
    </form></div>
</div>
<script>(function(){var modal=document.getElementById('attendance-upload-modal'),trigger=document.getElementById('attendance-upload-trigger'),cancel=document.getElementById('attendance-upload-cancel'),input=document.getElementById('attendance-sf2-file'),zone=document.getElementById('attendance-dropzone'),browse=document.getElementById('browse-attendance-file'),name=document.getElementById('attendance-file-name'),submit=document.getElementById('attendance-submit-row');function fileSelected(file){if(!file)return;name.textContent=file.name;submit.classList.remove('hidden');submit.classList.add('flex');}trigger.addEventListener('click',function(){modal.classList.remove('hidden');modal.classList.add('flex');});cancel.addEventListener('click',function(){modal.classList.add('hidden');modal.classList.remove('flex');});browse.addEventListener('click',function(e){e.stopPropagation();input.click();});zone.addEventListener('click',function(){input.click();});input.addEventListener('change',function(){fileSelected(input.files[0]);});['dragenter','dragover'].forEach(function(eventName){zone.addEventListener(eventName,function(e){e.preventDefault();zone.classList.add('border-[#4bb878]','bg-[#edf9f3]');});});['dragleave','drop'].forEach(function(eventName){zone.addEventListener(eventName,function(e){e.preventDefault();zone.classList.remove('border-[#4bb878]','bg-[#edf9f3]');});});zone.addEventListener('drop',function(e){if(!e.dataTransfer.files.length)return;input.files=e.dataTransfer.files;fileSelected(input.files[0]);});})();</script>
@endsection
