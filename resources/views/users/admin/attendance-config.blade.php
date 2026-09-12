@extends('users.admin.layout')

@section('title', 'Attendance Configuration')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $editing = $errors->any();
@endphp

<style>
    #attendanceEditor[data-editing="false"] .is-editing {
        display: none !important;
    }

    #attendanceEditor[data-editing="true"] .is-viewing {
        display: none !important;
    }
</style>

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Attendance Configuration</h1>
        <p class="mt-1 text-sm text-gray-500">Set the total school days per month. These counts appear on teacher advisory attendance records and SF9.</p>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Current Year</p>
        <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $currentYear?->school_year ?? 'Not set' }}</p>
        <p class="mt-1 text-xs text-gray-500">
            @if ($currentYear)
                {{ $currentYear->start_date?->format('M d, Y') }} – {{ $currentYear->end_date?->format('M d, Y') }}
            @else
                No active school year
            @endif
        </p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Selected Year</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ $selectedYear?->school_year ?? '—' }}</p>
        <p class="mt-1 text-xs text-gray-500">School year being configured</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total School Days</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalSchoolDays) }}</p>
        <p class="mt-1 text-xs text-gray-500">January through December</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Months Configured</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $configuredMonths }} / {{ count($months) }}</p>
        <p class="mt-1 text-xs text-gray-500">Months with school days set</p>
    </div>
</div>

<div id="attendanceEditor" data-editing="{{ $editing ? 'true' : 'false' }}" class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Monthly School Days</h2>
            <p class="mt-1 text-sm text-gray-500">These values apply to all advisory sections for the selected school year.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($academicYears->isNotEmpty())
                <form method="GET" action="{{ route('admin.attendance-config.index') }}" class="flex flex-wrap items-center gap-2">
                    <label for="sy_id" class="text-xs font-bold uppercase tracking-wide text-gray-500">Academic Year</label>
                    <select id="sy_id" name="sy_id" onchange="this.form.submit()"
                        class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" @selected($selectedYear && $selectedYear->SY_ID === $year->SY_ID)>
                                {{ $year->school_year }}{{ $year->status ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
            @if ($selectedYear)
                <button type="button" id="attendanceEditButton" onclick="startAttendanceEdit()" title="Edit"
                    class="is-viewing inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </button>
            @endif
        </div>
    </div>

    @if (! $selectedYear)
        <div class="px-6 py-16 text-center text-sm text-gray-500">
            Add an academic year first to configure monthly school days.
        </div>
    @else
        <form id="attendanceForm" action="{{ route('admin.attendance-config.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="SY_ID" value="{{ $selectedYear->SY_ID }}">

            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="border-r border-gray-200 px-5 py-4">Month</th>
                            <th class="px-5 py-4">School Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @foreach ($months as $monthKey => $monthLabel)
                            @php
                                $inputName = 'school_days.'.$monthKey;
                                $storedDays = (int) ($schoolDays[$monthKey] ?? 0);
                                $oldValue = old($inputName);
                                $inputValue = $oldValue !== null ? $oldValue : ($storedDays > 0 ? (string) $storedDays : '');
                            @endphp
                            <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                                <td class="border-r border-gray-100 px-5 py-4">
                                    <p class="font-semibold text-gray-900">{{ $monthLabel }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="is-viewing text-sm font-semibold text-gray-800">{{ $storedDays > 0 ? $storedDays : '—' }}</p>
                                    <div class="is-editing">
                                        <label for="school_days_{{ $monthKey }}" class="sr-only">{{ $monthLabel }} school days</label>
                                        <input id="school_days_{{ $monthKey }}" type="text" inputmode="numeric" pattern="[0-9]{0,2}" maxlength="2" autocomplete="off"
                                            name="school_days[{{ $monthKey }}]" value="{{ $inputValue }}" data-original="{{ $inputValue }}"
                                            class="school-days-input {{ $fieldClass }} max-w-[8rem] {{ $errors->has($inputName) ? 'border-red-300' : 'border-gray-200' }}">
                                        @error($inputName)
                                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-[#296374]/5 text-sm font-bold text-[#296374]">
                            <td class="border-r border-gray-100 px-5 py-4">Total</td>
                            <td class="px-5 py-4">{{ number_format($totalSchoolDays) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="is-editing flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="cancelAttendanceEdit()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save School Days
                </button>
            </div>
        </form>
    @endif
</div>

<script>
    function startAttendanceEdit() {
        document.getElementById('attendanceEditor').setAttribute('data-editing', 'true');
        const firstInput = document.querySelector('.school-days-input');
        if (firstInput) {
            firstInput.focus();
        }
    }

    function cancelAttendanceEdit() {
        document.querySelectorAll('.school-days-input').forEach(function (input) {
            input.value = input.getAttribute('data-original') || '';
        });
        document.getElementById('attendanceEditor').setAttribute('data-editing', 'false');
    }

    document.querySelectorAll('.school-days-input').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2);

            if (this.value !== '' && Number(this.value) > 31) {
                this.value = '31';
            }
        });
    });
</script>
@endsection
