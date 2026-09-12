@extends('users.guidance.layout')

@section('title', 'Enrollment Management')

@section('content')
@php
    $statusParam = request('status');
    $showAll = !request()->has('status') ? false : ($statusParam === '' || $statusParam === 'all');
    $statusOptions = ['all' => 'All status'] + \App\Models\EnrollmentStatus::options();
    $learnerOptions = ['' => 'All learners'] + \App\Models\LearnerType::options();
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="mt-1 text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Enrollment Management</h1>
        @if (!isset($activeYear) || !$activeYear)
            <p class="mt-2 text-sm font-medium text-amber-600">No active school year set. Enrollments will appear once an admin sets the current school year.</p>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('guidance.enrollments.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Register Student
        </a>
        <div class="rounded-lg border border-gray-200 bg-white/90 px-4 py-3 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Showing</p>
            <p class="text-sm font-semibold text-gray-700">{{ $enrollments?->total() ?? 0 }} enrollment records</p>
        </div>
    </div>
</div>

@if (session('status') && ! session('enrollment_result'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ $errors->first() }}
    </div>
@endif

<form method="GET" action="{{ route('guidance.enrollments.index') }}" class="mb-5">
    <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">

    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
        <div class="relative min-w-[220px] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
            </svg>
            <input type="search" name="search" id="search" value="{{ request('search') }}" placeholder="Search name or LRN"
                class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
        </div>

        <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            @foreach ($statusOptions as $value => $label)
                @php
                    $selected = $value === 'all'
                        ? $showAll
                        : (!$showAll && ($statusParam === $value || ($statusParam === null && $value === 'temporarily_enrolled')));
                @endphp
                <option value="{{ $value }}" {{ $selected ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="learner_type" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            @foreach ($learnerOptions as $value => $label)
                <option value="{{ $value }}" {{ request('learner_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">All grades</option>
            @foreach ($gradeLevels ?? [] as $level)
                <option value="{{ $level['value'] }}" {{ request('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
            @endforeach
        </select>

        <select name="academic_year_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">Current year</option>
            <option value="all" {{ request('academic_year_id') === 'all' ? 'selected' : '' }}>All years</option>
            @foreach ($academicYears ?? [] as $ay)
                <option value="{{ $ay->SY_ID }}" {{ request('academic_year_id') == $ay->SY_ID ? 'selected' : '' }}>{{ $ay->school_year }}</option>
            @endforeach
        </select>

        <details class="relative">
            <summary class="flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:border-[#296374]/40">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10m-7 6h4"></path>
                </svg>
                Dates
            </summary>
            <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl">
                <div class="grid gap-3">
                    <div>
                        <label for="date_from" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Date from</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    </div>
                    <div>
                        <label for="date_to" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Date to</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    </div>
                </div>
            </div>
        </details>

        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"></path>
            </svg>
            Apply
        </button>
        <a href="{{ route('guidance.enrollments.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
    </div>
</form>

<form method="POST" action="{{ route('guidance.enrollments.bulk-approve') }}" class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    @csrf
    <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600">
                <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]">
                Select all
            </label>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500" id="selected-count">0 selected</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <input type="hidden" name="status" id="bulk-status" value="enrolled">
            <button type="button" data-bulk-status="enrolled" class="bulk-enroll-trigger inline-flex h-9 items-center gap-2 rounded-lg px-3 text-xs font-bold uppercase tracking-wide text-white shadow-sm" style="background-color: #296374;" title="Mark selected as enrolled">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
            </button>
            <button type="button" data-bulk-status="temporarily_enrolled" class="bulk-enroll-trigger inline-flex h-9 items-center gap-2 rounded-lg bg-amber-500 px-3 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-amber-600" title="Mark selected as temporarily enrolled">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
            </button>
            <select id="bulk-status-choice" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">Set status…</option>
                @foreach (\App\Models\EnrollmentStatus::options() as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" id="bulk-status-apply" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold uppercase tracking-wide text-gray-700 shadow-sm transition hover:bg-gray-50">
                Apply
            </button>
            <button type="submit" formaction="{{ route('guidance.enrollments.print-multiple') }}" formtarget="_blank" class="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-600 px-3 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-12 0h12v4H6v-4z"></path></svg>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="w-12 border-r border-gray-200 px-4 py-4"><span class="sr-only">Select</span></th>
                    <th class="border-r border-gray-200 px-5 py-4">Student</th>
                    <th class="border-r border-gray-200 px-5 py-4">Grade</th>
                    <th class="border-r border-gray-200 px-5 py-4">LRN</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="border-r border-gray-200 px-5 py-4">Submitted</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($enrollments ?? [] as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $firstName = $application?->first_name ?? $student?->first_name;
                        $lastName = $application?->last_name ?? $student?->last_name;
                        $middleName = $application?->middle_name ?? $student?->middle_name;
                        $name = trim(($lastName ? $lastName.', ' : '').($firstName ?? '').($middleName ? ' '.$middleName : '')) ?: 'Unnamed student';
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                        $status = $enrollment->enrollment_status ?? '';
                        $statusClasses = match ($status) {
                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'enrolled' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                            'temporarily_enrolled' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'transferred_out' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                            'dropped_out' => 'bg-red-50 text-red-700 ring-red-200',
                            'withdrawn' => 'bg-orange-50 text-orange-700 ring-orange-200',
                            'cancelled' => 'bg-gray-100 text-gray-600 ring-gray-200',
                            'no_show' => 'bg-rose-50 text-rose-700 ring-rose-200',
                            default => 'bg-gray-100 text-gray-700 ring-gray-200',
                        };
                        $placementRecommended = $enrollment->placementAssessmentRecommendation();
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06] {{ $enrollment->hasPlacementStatusMark() ? 'ring-1 ring-inset ring-[#296374]/20' : '' }}">
                        <td class="border-r border-gray-100 px-4 py-4">
                            <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" class="row-checkbox h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]">
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $name }}</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @if ($enrollment->hasPlacementStatusMark())
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ \App\Models\PlacementStatus::badgeClasses($enrollment->placement_status) }}" title="Placement test: {{ $enrollment->placement_status_label }}">
                                        {{ $enrollment->placement_status_label }}
                                    </span>
                                @elseif ($placementRecommended)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200" title="{{ $placementRecommended['summary'] ?? $placementRecommended['message'] }}">
                                        Age review
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-700">{{ $gradeLabel }}</p>
                            @if(!empty($enrollment->semester))
                                <p class="text-xs text-gray-400">Sem {{ ucfirst($enrollment->semester) }}</p>
                            @endif
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $student?->lrn ?? '-' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses }}">
                                {{ $enrollment->enrollment_status_label ?: '-' }}
                            </span>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 font-medium text-gray-700">{{ $enrollment->created_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-1.5">
                                <a href="{{ route('guidance.enrollments.show', $enrollment) }}" title="View enrollment" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 hover:text-[#296374]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7S3.73 16.06 2.46 12z"></path></svg>
                                </a>
                                <a href="{{ route('guidance.enrollments.edit', $enrollment) }}" title="Edit enrollment details" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 hover:text-[#296374]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                                <a href="{{ route('guidance.enrollments.print', $enrollment) }}" target="_blank" title="Print enrollment" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-12 0h12v4H6v-4z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3 text-gray-500">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l3.414 3.414A1 1 0 0 1 17 7.414V19a2 2 0 0 1-2 2z"></path>
                                    </svg>
                                </div>
                                <p class="font-semibold text-gray-700">No enrollments found</p>
                                <p class="text-sm">Try adjusting the filters or search term, or register a student.</p>
                                <a href="{{ route('guidance.enrollments.create') }}" class="mt-1 text-sm font-semibold text-[#296374] hover:underline">Register Student</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @isset($enrollments)
        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-500 md:flex-row md:items-center md:justify-between">
            <span>
                Showing {{ $enrollments->firstItem() ?? 0 }} to {{ $enrollments->lastItem() ?? 0 }} of {{ $enrollments->total() }} records
            </span>
            <div>
                {{ $enrollments->links() }}
            </div>
        </div>
    @endisset
</form>

<script>
    (function () {
        var selectAll = document.getElementById('select-all');
        var checkboxes = Array.from(document.querySelectorAll('.row-checkbox'));
        var countEl = document.getElementById('selected-count');

        function updateCount() {
            var selected = checkboxes.filter(function (cb) { return cb.checked; }).length;
            countEl.textContent = selected + ' selected';
            if (selectAll) {
                selectAll.checked = selected > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                updateCount();
            });
        }

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', updateCount);
        });

        updateCount();
    })();
</script>

@include('users.guidance.enrollments.partials.enrollment-flow-modals', [
    'confirmContext' => 'bulk',
])
@endsection
