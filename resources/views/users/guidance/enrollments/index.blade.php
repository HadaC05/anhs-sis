@extends('users.guidance.layout')

@section('title', 'Enrollment Management')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Enrollment Management</h1>
    <p class="text-gray-600 text-sm md:text-base">Manage and review student enrollment applications for the current school year</p>
    @if (!isset($activeYear) || !$activeYear)
        <p class="mt-2 text-amber-600 text-sm font-medium">No active school year set. Enrollments will appear here once an admin sets the current school year.</p>
    @endif
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg bg-red-100 border border-red-400 text-red-700 px-4 py-3">
        {{ $errors->first() }}
    </div>
@endif

<form method="GET" action="{{ route('guidance.enrollments.index') }}" class="mb-6 rounded-xl border border-gray-200 bg-white/80 p-8 shadow-sm">
    <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">

    <div class="mb-6">
        <label for="search" class="block text-xs font-semibold text-gray-500 mb-1.5">Search</label>
        <input type="text" name="search" id="search" value="{{ request('search') }}"
               placeholder="Student name or LRN..."
               class="w-full max-w-md border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-x-12 gap-y-6">
        <div class="px-2 sm:px-3">
            <label for="status" class="block text-xs font-semibold text-gray-500 mb-1.5">Status</label>
            <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                @php $statusParam = request('status'); $showAll = !request()->has('status') ? false : ($statusParam === '' || $statusParam === 'all'); @endphp
                <option value="all" {{ $showAll ? 'selected' : '' }}>All</option>
                <option value="pending" {{ (!$showAll && ($statusParam === null || $statusParam === 'pending')) ? 'selected' : '' }}>Pending</option>
                <option value="enrolled" {{ $statusParam === 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                <option value="temporarily_enrolled" {{ $statusParam === 'temporarily_enrolled' ? 'selected' : '' }}>Temporarily Enrolled</option>
                <option value="completed" {{ $statusParam === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="withdrawn" {{ $statusParam === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                <option value="cancelled" {{ $statusParam === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="learner_type" class="block text-xs font-semibold text-gray-500 mb-1.5">Learner Type</label>
            <select name="learner_type" id="learner_type" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                <option value="">All</option>
                <option value="regular" {{ request('learner_type') === 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="transferee" {{ request('learner_type') === 'transferee' ? 'selected' : '' }}>Transferee</option>
                <option value="returnee" {{ request('learner_type') === 'returnee' ? 'selected' : '' }}>Returning Learner</option>
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="grade_level" class="block text-xs font-semibold text-gray-500 mb-1.5">Grade Level</label>
            <select name="grade_level" id="grade_level" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                <option value="">All</option>
                @foreach ($gradeLevels ?? [] as $level)
                    <option value="{{ $level['value'] }}" {{ request('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="academic_year_id" class="block text-xs font-semibold text-gray-500 mb-1.5">School Year</label>
            <select name="academic_year_id" id="academic_year_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                <option value="">Current</option>
                <option value="all" {{ request('academic_year_id') === 'all' ? 'selected' : '' }}>All years</option>
                @foreach ($academicYears ?? [] as $ay)
                    <option value="{{ $ay->SY_ID }}" {{ request('academic_year_id') == $ay->SY_ID ? 'selected' : '' }}>{{ $ay->school_year }}</option>
                @endforeach
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="date_from" class="block text-xs font-semibold text-gray-500 mb-1.5">Date From</label>
            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
        </div>
        <div class="px-2 sm:px-3">
            <label for="date_to" class="block text-xs font-semibold text-gray-500 mb-1.5">Date To</label>
            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
        </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <button type="submit" class="rounded-lg px-6 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">Apply filters</button>
    </div>
</form>

<form method="POST" action="{{ route('guidance.enrollments.bulk-approve') }}" class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    @csrf
    <div class="px-6 py-4 border-b border-white/20 bg-white/40 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600">
                <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-300">
                Select all
            </label>
            <span class="text-xs text-gray-500" id="selected-count">0 selected</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <input type="hidden" name="status" id="bulk-status" value="enrolled">
            <button type="submit" class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Enroll Selected</button>
            <button type="submit" onclick="document.getElementById('bulk-status').value='temporarily_enrolled'" class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md bg-amber-500 hover:bg-amber-600">Temporary Enroll</button>
            <button type="submit" formaction="{{ route('guidance.enrollments.print-multiple') }}" formtarget="_blank" class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md bg-slate-600 hover:bg-slate-700">Print Selected</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-6 py-4">
                        <span class="sr-only">Select</span>
                    </th>
                    <th class="px-6 py-4">Student Name</th>
                    <th class="px-6 py-4">Grade Level</th>
                    <th class="px-6 py-4">Learner Type</th>
                    <th class="px-6 py-4">LRN</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Date Submitted</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @forelse($enrollments ?? [] as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $name = $application ? $application->last_name . ', ' . $application->first_name . ($application->middle_name ? ' ' . $application->middle_name : '') : ($student?->user?->name ?? '—');
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                    @endphp
                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" class="row-checkbox h-4 w-4 rounded border-gray-300">
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-gray-800">{{ $name }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-gray-700">{{ $gradeLabel }}</span>
                            @if(!empty($enrollment->semester))
                                <span class="text-xs text-gray-500 block">Sem {{ ucfirst($enrollment->semester) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700">{{ $enrollment->learner_type ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700 font-mono">{{ $student?->lrn ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($enrollment->enrollment_status ?? '') === 'pending')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-amber-700 bg-amber-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Pending
                                </span>
                            @elseif(($enrollment->enrollment_status ?? '') === 'enrolled')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-white" style="background-color: #296374;">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white/80"></span> Enrolled
                                </span>
                            @elseif(($enrollment->enrollment_status ?? '') === 'temporarily_enrolled')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-blue-700 bg-blue-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Temporarily Enrolled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span> {{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_status ?? '—')) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $enrollment->created_at?->format('M d, Y') ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('guidance.enrollments.show', $enrollment) }}" class="inline-block text-white text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-lg shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">View</a>
                                <a href="{{ route('guidance.enrollments.print', $enrollment) }}" target="_blank" class="inline-block text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-lg shadow-md bg-slate-600 text-white hover:opacity-90 transition-opacity">Print</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No enrollments found for this school year.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($enrollments)
        <div class="px-6 py-4 border-t border-white/20 bg-white/20">
            {{ $enrollments->links() }}
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
@endsection
