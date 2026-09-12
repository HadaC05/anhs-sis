@extends('users.registrar.layout')

@section('title', 'Student Masterlist')

@section('content')
@php
    $statusParam = request('status');
    $showAll = $statusParam === '' || $statusParam === 'all';
    $statusOptions = ['all' => 'All status'] + \App\Models\EnrollmentStatus::optionsFor(\App\Models\EnrollmentStatus::activeSlugs());
    $learnerOptions = ['' => 'All learners'] + \App\Models\LearnerType::options();
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="mt-1 text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Student Masterlist</h1>
        @if (! isset($activeYear) || ! $activeYear)
            <p class="mt-2 text-sm font-medium text-amber-600">No active school year set. Student records will appear once an admin sets the current school year.</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white/90 px-4 py-3 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Showing</p>
        <p class="text-sm font-semibold text-gray-700">{{ $students?->total() ?? 0 }} student records</p>
    </div>
</div>

@if (session('status'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ $errors->first() }}
    </div>
@endif

<form method="GET" action="{{ route('registrar.students') }}" class="mb-5">
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
                        : (! $showAll && $statusParam === $value);
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

        <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            @foreach ([10, 15, 20, 50] as $size)
                <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
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
        <a href="{{ route('registrar.students') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[960px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">Student</th>
                    <th class="border-r border-gray-200 px-5 py-4">Grade</th>
                    <th class="border-r border-gray-200 px-5 py-4">Section</th>
                    <th class="border-r border-gray-200 px-5 py-4">LRN</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="border-r border-gray-200 px-5 py-4">Enrolled</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($students ?? [] as $enrollment)
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
                            'enrolled' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                            'temporarily_enrolled' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            default => 'bg-gray-100 text-gray-700 ring-gray-200',
                        };
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $name }}</p>
                            @if ($enrollment->learner_type && $enrollment->learner_type !== \App\Models\LearnerType::REGULAR)
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-gray-500">{{ $enrollment->learner_type_label }}</p>
                            @endif
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-700">{{ $gradeLabel }}</p>
                            @if (! empty($enrollment->semester))
                                <p class="text-xs text-gray-400">Sem {{ ucfirst($enrollment->semester) }}</p>
                            @endif
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-medium text-gray-800">{{ $enrollment->section?->name ?? 'Unassigned' }}</p>
                            <p class="text-xs text-gray-500">{{ $enrollment->cluster?->name ?? 'N/A' }}</p>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $student?->lrn ?? '-' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses }}">
                                {{ $enrollment->enrollment_status_label ?: '-' }}
                            </span>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 font-medium text-gray-700">{{ $enrollment->created_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-5 py-4">
                            @if ($student)
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('registrar.students.show', $student) }}" title="View student record" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 hover:text-[#296374]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7S3.73 16.06 2.46 12z"></path></svg>
                                    </a>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">�</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3 text-gray-500">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                                    </svg>
                                </div>
                                <p class="font-semibold text-gray-700">No students found</p>
                                <p class="text-sm">Try adjusting the filters or search term.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @isset($students)
        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-500 md:flex-row md:items-center md:justify-between">
            <span>
                Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} records
            </span>
            <div>
                {{ $students->links() }}
            </div>
        </div>
    @endisset
</div>
@endsection
