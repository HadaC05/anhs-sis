@php
    $monthlySummary = $selectedUpload?->sf2_layout['summary'] ?? [];
    $averageDailyAttendance = $monthlySummary['average_daily_attendance'][2] ?? null;
    $attendancePercentage = $monthlySummary['attendance_percentage'][2] ?? null;
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Monthly attendance highlights">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Currently viewing</p>
            <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $months[$selectedMonth] }}</p>
            <p class="mt-1 text-xs text-gray-500">School year {{ $section->academicYear->school_year }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">School days</p>
            <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $selectedUpload?->school_days ?? $schoolDays[$selectedMonth] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500">For {{ $months[$selectedMonth] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Average daily attendance</p>
            <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $averageDailyAttendance ?? '?' }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $averageDailyAttendance !== null ? 'Combined total from uploaded SF2' : 'No SF2 summary available' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Attendance for the month</p>
            <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $attendancePercentage !== null ? $attendancePercentage.'%' : '?' }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $attendancePercentage !== null ? 'Combined percentage from uploaded SF2' : 'No SF2 summary available' }}</p>
        </div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-[#296374]">Detailed Monthly Attendance Record</h2>
                <p class="mt-1 text-sm text-gray-500">Review the original SF2 daily marks alongside the monthly counts shown in the overview.</p>
            </div>
            <form method="GET" action="{{ route('teacher.advisory.attendance', $section) }}" class="flex items-end gap-2">
                <input type="hidden" name="view" value="detailed">
                <div>
                    <label for="attendance-detail-month" class="mb-1 block text-xs font-semibold text-gray-600">Report month</label>
                    <select id="attendance-detail-month" name="month" onchange="this.form.requestSubmit()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                        @foreach($months as $monthKey => $monthLabel)
                            <option value="{{ $monthKey }}" @selected($selectedMonth === (int) $monthKey)>{{ $monthLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedUpload?->import_rows)
        @include('users.teacher.advisory.partials.sf2-sheet')
    @elseif($selectedUpload)
        <p class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-500">No readable attendance data is available for this upload. Upload a completed SF2 to display the monthly record.</p>
    @else
        <p class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-500">No attendance record uploaded for {{ $months[$selectedMonth] }}. Use Upload Attendance Record to add this month's SF2.</p>
    @endif
</div>
