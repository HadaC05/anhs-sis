@extends('users.admin.layout')

@section('title', 'Academic Year')

@section('content')
@php
    $modalOpen = $errors->any();
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Academic Year</h1>
    </div>
    <button type="button" onclick="openAcademicYearModal()" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
        Add Academic Year
    </button>
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
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Years</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalYears) }}</p>
        <p class="mt-1 text-xs text-gray-500">Configured school years</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Active</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($activeCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Available for enrollment</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Inactive</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($inactiveCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Archived school years</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">School Years</h2>
        </div>
    </div>

    <div class="border-b border-gray-100 px-4 py-4">
        <form method="GET" action="{{ route('admin.academic-year-config.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                </svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search school year"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([5, 10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            @if (request()->hasAny(['search', 'status', 'per_page']))
                <a href="{{ route('admin.academic-year-config.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">School Year</th>
                    <th class="border-r border-gray-200 px-5 py-4">Start Date</th>
                    <th class="border-r border-gray-200 px-5 py-4">End Date</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($academicYears as $year)
                    @php
                        $yearPayload = [
                            'SY_ID' => $year->SY_ID,
                            'start_date' => $year->start_date?->format('Y-m-d'),
                            'end_date' => $year->end_date?->format('Y-m-d'),
                        ];
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $year->school_year }}</p>
                            @if ($currentYear && $currentYear->SY_ID === $year->SY_ID)
                                <p class="mt-0.5 text-xs font-semibold text-[#296374]">Current school year</p>
                            @endif
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $year->start_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $year->end_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $year->status ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                {{ $year->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick='openAcademicYearModal(@json($yearPayload))'
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.academic-year-config.toggle-status', $year) }}" method="POST" class="inline" onsubmit="return confirm('{{ $year->status ? 'Archive this academic year?' : ($currentYear ? 'Activate this academic year? '.$currentYear->school_year.' will be archived.' : 'Activate this academic year?') }}');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition {{ $year->status ? 'text-amber-700 ring-amber-200 hover:bg-amber-50' : 'text-emerald-700 ring-emerald-200 hover:bg-emerald-50' }}">
                                        {{ $year->status ? 'Archive' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">No academic years yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($academicYears->hasPages())
        <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            {{ $academicYears->withQueryString()->links() }}
        </div>
    @endif
</div>

<div id="academicYearModal" role="dialog" aria-modal="true" aria-labelledby="academicYearModalTitle" data-open="{{ $modalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $modalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-lg overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="academicYearModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Academic Year</h3>
                </div>
                <button type="button" onclick="closeAcademicYearModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="academicYearForm" action="{{ route('admin.academic-year-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="academic_year_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Start Date <span class="text-red-500">*</span></label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required min="{{ \App\Models\AcademicYear::EARLIEST_DATE }}" max="{{ \App\Models\AcademicYear::LATEST_DATE }}"
                            class="{{ $fieldClass }} {{ $errors->has('start_date') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('start_date')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">End Date <span class="text-red-500">*</span></label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required min="{{ \App\Models\AcademicYear::EARLIEST_DATE }}"
                            class="{{ $fieldClass }} {{ $errors->has('end_date') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('end_date')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-gray-600">School Year</p>
                    <div class="flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <label for="start_year" class="sr-only">Starting year</label>
                            <input id="start_year" type="text" maxlength="4" readonly tabindex="-1" placeholder="----" value="{{ old('start_year', old('school_year') ? explode('-', (string) old('school_year'))[0] : '') }}"
                                class="{{ $fieldClass }} cursor-default bg-gray-50 text-center tracking-widest {{ $errors->has('school_year') ? 'border-red-300' : 'border-gray-200' }}">
                        </div>
                        <span class="text-lg font-semibold text-gray-400" aria-hidden="true">-</span>
                        <div class="min-w-0 flex-1">
                            <label for="end_year" class="sr-only">Ending year</label>
                            <input id="end_year" type="text" maxlength="4" readonly tabindex="-1" placeholder="----" value="{{ old('end_year', old('school_year') ? explode('-', (string) old('school_year'))[1] ?? '' : '') }}"
                                class="{{ $fieldClass }} cursor-default bg-gray-50 text-center tracking-widest {{ $errors->has('school_year') ? 'border-red-300' : 'border-gray-200' }}">
                        </div>
                    </div>
                    @error('school_year')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeAcademicYearModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="academicYearSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Academic Year
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const earliestCoverageDate = '{{ \App\Models\AcademicYear::EARLIEST_DATE }}';
    const latestCoverageDate = '{{ \App\Models\AcademicYear::LATEST_DATE }}';

    function parseIsoDate(value) {
        const parts = String(value).split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function shiftIsoDate(value, years, days) {
        const date = parseIsoDate(value);
        if (years) {
            date.setFullYear(date.getFullYear() + years);
        }
        if (days) {
            date.setDate(date.getDate() + days);
        }

        return toIsoDate(date);
    }

    function maxIsoDate(first, second) {
        return first > second ? first : second;
    }

    function minIsoDate(first, second) {
        return first < second ? first : second;
    }

    function syncSchoolYearName() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        document.getElementById('start_year').value = startDate ? startDate.slice(0, 4) : '';
        document.getElementById('end_year').value = endDate ? endDate.slice(0, 4) : '';
    }

    function syncCoverageDateLimits() {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const startDate = startInput.value;
        const endDate = endInput.value;

        startInput.min = earliestCoverageDate;
        startInput.max = latestCoverageDate;
        endInput.min = earliestCoverageDate;
        endInput.max = shiftIsoDate(latestCoverageDate, 1, 0);

        if (startDate) {
            endInput.min = maxIsoDate(earliestCoverageDate, shiftIsoDate(startDate, 0, 1));
            endInput.max = shiftIsoDate(startDate, 1, 0);

            if (endDate && (endDate < endInput.min || endDate > endInput.max)) {
                endInput.value = '';
            }
        }

        if (endInput.value) {
            startInput.max = minIsoDate(latestCoverageDate, shiftIsoDate(endInput.value, 0, -1));
            startInput.min = maxIsoDate(earliestCoverageDate, shiftIsoDate(endInput.value, -1, 0));
        }

        syncSchoolYearName();
    }

    function openAcademicYearModal(year = null) {
        const form = document.getElementById('academicYearForm');
        const method = document.getElementById('academic_year_method');
        const title = document.getElementById('academicYearModalTitle');
        const subtitle = document.getElementById('academicYearModalSubtitle');
        const submit = document.getElementById('academicYearSubmit');
        const updateRouteTemplate = '{{ route('admin.academic-year-config.update', ['academicYear' => '__SY__']) }}';
        const modal = document.getElementById('academicYearModal');

        if (year) {
            title.textContent = 'Edit Academic Year';
            if (subtitle) {
                subtitle.textContent = 'Update the coverage dates. The school year name is filled automatically.';
            }
            submit.textContent = 'Update Academic Year';
            form.action = updateRouteTemplate.replace('__SY__', year.SY_ID);
            method.value = 'PUT';
            document.getElementById('start_date').value = year.start_date || '';
            document.getElementById('end_date').value = year.end_date || '';
        } else {
            title.textContent = 'Add Academic Year';
            if (subtitle) {
                subtitle.textContent = 'Set the coverage dates. The school year name is filled automatically.';
            }
            submit.textContent = 'Save Academic Year';
            form.action = '{{ route('admin.academic-year-config.store') }}';
            method.value = 'POST';
            form.reset();
            document.getElementById('start_year').value = '';
            document.getElementById('end_year').value = '';
        }

        syncCoverageDateLimits();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('start_date').focus();
    }

    function closeAcademicYearModal() {
        const modal = document.getElementById('academicYearModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    document.getElementById('academicYearModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeAcademicYearModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('academicYearModal').getAttribute('data-open') === 'true') {
            closeAcademicYearModal();
        }
    });

    document.getElementById('start_date').addEventListener('change', syncCoverageDateLimits);
    document.getElementById('end_date').addEventListener('change', syncCoverageDateLimits);
    document.getElementById('start_date').addEventListener('input', syncCoverageDateLimits);
    document.getElementById('end_date').addEventListener('input', syncCoverageDateLimits);
    syncCoverageDateLimits();
</script>
@endsection
