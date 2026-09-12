@extends('users.guidance.layout')

@section('title', 'Sectioning')

@section('content')
<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Sectioning</h1>
            <p class="text-gray-500 text-sm">View and manage class sections for the current school year</p>
        </div>
    </div>
    @if (!isset($activeYear) || !$activeYear)
        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <p class="text-amber-800 text-sm font-medium">No active school year set. Sections will appear here once an admin sets the current school year.</p>
        </div>
    @endif
</div>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@php
    $sectionsList = $sections instanceof \Illuminate\Pagination\LengthAwarePaginator ? $sections->getCollection() : ($sections ?? collect());
@endphp

<div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" action="{{ route('guidance.sections.index') }}" class="flex-1">
        <input type="hidden" name="per_page" value="{{ request('per_page', 16) }}">

        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
            <select name="grade_level" id="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All levels</option>
                @foreach ($gradeLevels ?? [] as $level)
                    <option value="{{ $level['value'] }}" {{ request('grade_level') == $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                @endforeach
            </select>

            @if ($showClusterFilter ?? false)
                <select name="cluster_id" id="cluster_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($clusters ?? [] as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" {{ request('cluster_id') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"></path>
                </svg>
                Apply
            </button>
            <a href="{{ route('guidance.sections.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
        </div>
    </form>

    <div class="flex flex-wrap items-center gap-3 shrink-0">
        <button type="button" onclick="openSectionModal()" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Section
        </button>
        @if ($sectionsList->isNotEmpty())
            <a href="{{ route('guidance.sections.master-list') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </a>
        @endif
    </div>
</div>

@if ($sectionsList->isNotEmpty())
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($sectionsList as $section)
            @php
                $cap = (int) ($section->capacity ?? 0);
                $count = (int) ($section->enrollments_count ?? 0);
                $pct = $cap > 0 ? min(100, (int) round(100 * $count / $cap)) : 0;
                $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
                $badgeColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                $statusLabel = $pct >= 100 ? 'full' : ($pct >= 80 ? 'near full' : 'open');
                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
                $gradeInitial = strtoupper(str_replace('grade_', 'G', $section->grade_level));
                $adviserName = $section->adviser
                    ? trim($section->adviser->first_name . ' ' . $section->adviser->last_name)
                    : 'No adviser';
            @endphp

            <a href="{{ route('guidance.sections.show', $section) }}" class="group relative block min-h-[190px] rounded-lg border border-gray-200/80 bg-white p-5 shadow-md shadow-slate-200/70 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-slate-300/70 focus:outline-none focus:ring-2 focus:ring-[#296374]/30">
                <span class="absolute right-0 top-4 rounded-l-sm {{ $badgeColor }} px-3 py-1 text-[11px] font-bold lowercase text-white shadow-sm">{{ $statusLabel }}</span>

                <div class="flex items-start gap-4 pr-16">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white shadow-md" style="background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);">
                        {{ $gradeInitial }}
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-gray-800">{{ $section->name }}</h2>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $gradeLabel }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path></svg>
                        <span class="truncate font-mono">{{ $section->room ?? 'No room assigned' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0 018 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="font-semibold text-gray-700">{{ $count }}</span>
                        <span>of {{ $cap ?: 'unlimited' }} students</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"></path></svg>
                        <span class="truncate">{{ $adviserName }}</span>
                    </div>
                    @if($section->cluster?->name)
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                            <span class="truncate">{{ $section->cluster->name }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-5">
                    <div class="mb-1 flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        <span>Capacity</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-200">
                        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    @isset($sections)
        <div class="mt-6 rounded-lg border border-gray-200/80 bg-white/90 px-6 py-4 shadow-sm">
            {{ $sections->links() }}
        </div>
    @endisset
@else
    <div class="rounded-lg border border-gray-200/80 bg-white/95 px-6 py-16 text-center shadow-md">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <p class="mt-4 font-medium text-gray-600">No sections found</p>
        <p class="mt-1 text-sm text-gray-500">Try changing the grade level filter or check back once sections are created for this school year.</p>
    </div>
@endif

<div id="sectionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent p-6">
            <h3 class="text-xl font-bold text-gray-800">Add Section</h3>
        </div>
        <form id="sectionForm" class="space-y-4 p-6" action="{{ route('guidance.sections.store') }}" method="POST">
            @csrf
            <div>
                <label for="section_grade_level" class="mb-1 block text-sm font-semibold text-gray-700">Grade Level</label>
                <select id="section_grade_level" name="grade_level" class="w-full rounded-lg border border-gray-300 px-4 py-2" required>
                    <option value="">Select grade level</option>
                    @foreach($gradeLevels ?? [] as $level)
                        <option value="{{ $level['value'] }}" {{ old('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div id="section_cluster_field" class="hidden">
                <label for="section_cluster_id" class="mb-1 block text-sm font-semibold text-gray-700">Cluster</label>
                <select id="section_cluster_id" name="cluster_ID" class="w-full rounded-lg border border-gray-300 px-4 py-2">
                    <option value="">Select cluster</option>
                    @foreach($clusters ?? [] as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" {{ (string) old('cluster_ID') === (string) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <p id="section_cluster_hint" class="mt-1 text-xs text-gray-500">Required for Grade 11 and Grade 12 sections.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="section_name" class="mb-1 block text-sm font-semibold text-gray-700">Section Name</label>
                    <input id="section_name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2" required>
                </div>
                <div>
                    <label for="section_staff_id" class="mb-1 block text-sm font-semibold text-gray-700">Adviser</label>
                    <select id="section_staff_id" name="staff_ID" class="w-full rounded-lg border border-gray-300 px-4 py-2">
                        <option value="">None</option>
                        @foreach($staffs ?? [] as $staff)
                            <option value="{{ $staff->staff_id }}" {{ (string) old('staff_ID') === (string) $staff->staff_id ? 'selected' : '' }}>{{ $staff->last_name }}, {{ $staff->first_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="section_sy_id" class="mb-1 block text-sm font-semibold text-gray-700">Academic Year</label>
                <select id="section_sy_id" name="SY_ID" class="w-full rounded-lg border border-gray-300 px-4 py-2" required>
                    <option value="">Select school year</option>
                    @foreach($academicYears ?? [] as $year)
                        <option value="{{ $year->SY_ID }}" {{ (string) old('SY_ID', $activeYear?->SY_ID ?? '') === (string) $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="section_room" class="mb-1 block text-sm font-semibold text-gray-700">Room</label>
                    <input id="section_room" name="room" type="text" value="{{ old('room') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2">
                </div>
                <div>
                    <label for="section_capacity" class="mb-1 block text-sm font-semibold text-gray-700">Capacity</label>
                    <input id="section_capacity" name="capacity" type="text" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" autocomplete="off" value="{{ old('capacity') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 {{ $errors->has('capacity') ? 'border-red-300' : '' }}" required>
                    <p class="mt-1 text-xs text-gray-500">Numbers only, up to 100.</p>
                    @error('capacity')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeSectionModal()" class="flex-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 rounded-lg bg-[#296374] px-4 py-2 text-white hover:bg-[#1e4a57]">Save Section</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSectionModal() {
        document.getElementById('sectionModal').classList.remove('hidden');
        document.getElementById('sectionModal').classList.add('flex');
        toggleSectionClusterRequirement();
    }

    function closeSectionModal() {
        document.getElementById('sectionModal').classList.add('hidden');
        document.getElementById('sectionModal').classList.remove('flex');
    }

    document.getElementById('sectionModal').addEventListener('click', function (event) {
        if (event.target === this) {
            closeSectionModal();
        }
    });

    function toggleSectionClusterRequirement() {
        const gradeSelect = document.getElementById('section_grade_level');
        const clusterField = document.getElementById('section_cluster_field');
        const clusterSelect = document.getElementById('section_cluster_id');
        const isSeniorHigh = ['grade_11', 'grade_12'].includes(gradeSelect.value);

        clusterField.classList.toggle('hidden', !isSeniorHigh);
        clusterSelect.required = isSeniorHigh;

        if (!isSeniorHigh) {
            clusterSelect.value = '';
        }
    }

    document.getElementById('section_grade_level').addEventListener('change', toggleSectionClusterRequirement);

    function limitSectionCapacity(input) {
        if (! input) {
            return;
        }

        const digits = (input.value || '').replace(/\D+/g, '').slice(0, 3);

        if (digits === '') {
            input.value = '';
            return;
        }

        const value = Number(digits);
        input.value = value > 100 ? '100' : String(value);
    }

    const capacityInput = document.getElementById('section_capacity');
    capacityInput.addEventListener('input', function () {
        limitSectionCapacity(this);
    });
    capacityInput.addEventListener('keydown', function (event) {
        if (['e', 'E', '+', '-', '.'].includes(event.key)) {
            event.preventDefault();
        }
    });
    limitSectionCapacity(capacityInput);

    @if ($errors->any() && (old('name') || old('capacity') || old('grade_level')))
        openSectionModal();
    @endif
</script>
@endsection
