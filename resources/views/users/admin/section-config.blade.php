@extends('users.admin.layout')

@section('title', 'Sections')

@section('content')
@php
    $modalOpen = $errors->any();
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Sections</h1>
    </div>
    <button type="button" onclick="openSectionModal()" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
        Add Section
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

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="border-b border-gray-100 px-4 py-4">
        <form method="GET" action="{{ route('admin.section-config.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                </svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search section or room"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            <select name="cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All clusters</option>
                @foreach ($clusters as $cluster)
                    <option value="{{ $cluster->cluster_ID }}" @selected((int) request('cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                @endforeach
            </select>
            <select name="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All grades</option>
                @foreach ($gradeLevels as $level)
                    <option value="{{ $level['value'] }}" @selected(request('grade_level') === $level['value'])>{{ $level['label'] }}</option>
                @endforeach
            </select>
            <select name="SY_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All years</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->SY_ID }}" @selected((int) request('SY_ID') === (int) $year->SY_ID)>{{ $year->school_year }}</option>
                @endforeach
            </select>
            <select name="curriculum_grade_level_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All curricula</option>
                @foreach ($curriculums as $curriculum)
                    <option value="{{ $curriculum->curriculum_ID }}" @selected((int) request('curriculum_grade_level_ID') === (int) $curriculum->curriculum_ID)>{{ $curriculum->name }}</option>
                @endforeach
            </select>
            <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="active" @selected(($status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(($status ?? 'active') === 'inactive')>Inactive</option>
                <option value="all" @selected(($status ?? 'active') === 'all')>All statuses</option>
            </select>
            <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([5, 10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            @if (request()->hasAny(['search', 'cluster_ID', 'grade_level', 'SY_ID', 'curriculum_grade_level_ID', 'per_page', 'status']))
                <a href="{{ route('admin.section-config.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">Name</th>
                    <th class="border-r border-gray-200 px-5 py-4">Cluster</th>
                    <th class="border-r border-gray-200 px-5 py-4">Grade</th>
                    <th class="border-r border-gray-200 px-5 py-4">Semester</th>
                    <th class="border-r border-gray-200 px-5 py-4">Adviser</th>
                    <th class="border-r border-gray-200 px-5 py-4">Academic Year</th>
                    <th class="border-r border-gray-200 px-5 py-4">Room</th>
                    <th class="border-r border-gray-200 px-5 py-4">Capacity</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($sections as $section)
                    @php
                        $adviserName = optional($section->adviser)->last_name
                            ? optional($section->adviser)->last_name.', '.optional($section->adviser)->first_name
                            : '—';
                        $sectionPayload = [
                            'section_ID' => $section->section_ID,
                            'name' => $section->name,
                            'cluster_ID' => $section->cluster_ID,
                            'grade_level' => $section->grade_level,
                            'staff_ID' => $section->staff_ID,
                            'SY_ID' => $section->SY_ID,
                            'curriculum_grade_level_ID' => $section->curriculum_grade_level_ID,
                            'room' => $section->room,
                            'capacity' => $section->capacity,
                        ];
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $section->name }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($section->cluster)->name ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section->gradeLevel?->grade_label ?? $section->curriculumGradeLevel?->gradeLevel?->grade_label ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section->curriculumGradeLevel?->gradingSemester?->label ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $adviserName }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($section->academicYear)->school_year ?? '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section->room ?: '—' }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section->capacity }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $section->status ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                {{ $section->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick='openSectionModal(@json($sectionPayload))'
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.section-config.toggle-status', $section) }}" method="POST" class="inline" onsubmit="return confirm('{{ $section->status ? 'Archive this section?' : 'Activate this section?' }}');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg p-2 text-gray-500 transition {{ $section->status ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-600' }}" title="{{ $section->status ? 'Archive' : 'Activate' }}">
                                        @if ($section->status)
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center text-gray-500">No sections found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($sections->hasPages())
        <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            {{ $sections->withQueryString()->links() }}
        </div>
    @endif
</div>

<div id="sectionModal" role="dialog" aria-modal="true" aria-labelledby="sectionModalTitle" data-open="{{ $modalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $modalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-2xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="sectionModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Section</h3>
                </div>
                <button type="button" onclick="closeSectionModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="sectionForm" action="{{ route('admin.section-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="section_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="section_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Section Name <span class="text-red-500">*</span></label>
                        <input id="section_name" name="name" type="text" value="{{ old('name') }}" required
                            class="{{ $fieldClass }} {{ $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('name')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="section_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Cluster</label>
                        <select id="section_cluster_id" name="cluster_ID"
                            class="{{ $fieldClass }} {{ $errors->has('cluster_ID') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select cluster</option>
                            @foreach ($clusters as $cluster)
                                <option value="{{ $cluster->cluster_ID }}" @selected((string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                        <p id="section_cluster_hint" class="mt-1 text-xs text-gray-500">Required for senior high school sections only.</p>
                        @error('cluster_ID')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="section_grade_level" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Level <span class="text-red-500">*</span></label>
                        <select id="section_grade_level" name="grade_level" required
                            class="{{ $fieldClass }} {{ $errors->has('grade_level') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select</option>
                            @foreach ($gradeLevels as $level)
                                <option value="{{ $level['value'] }}" @selected(old('grade_level') === $level['value'])>{{ $level['label'] }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="section_staff_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Adviser</label>
                        <select id="section_staff_id" name="staff_ID"
                            class="{{ $fieldClass }} {{ $errors->has('staff_ID') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">None</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->staff_id }}" @selected((string) old('staff_ID') === (string) $staff->staff_id)>{{ $staff->last_name }}, {{ $staff->first_name }}</option>
                            @endforeach
                        </select>
                        @error('staff_ID')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="section_sy_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Academic Year <span class="text-red-500">*</span></label>
                        <select id="section_sy_id" name="SY_ID" required
                            class="{{ $fieldClass }} {{ $errors->has('SY_ID') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select school year</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->SY_ID }}" @selected((string) old('SY_ID') === (string) $year->SY_ID)>{{ $year->school_year }}</option>
                            @endforeach
                        </select>
                        @error('SY_ID')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="section_curriculum_grade_level_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Curriculum grade level <span class="text-red-500">*</span></label>
                        <select id="section_curriculum_grade_level_id" name="curriculum_grade_level_ID" required
                            class="{{ $fieldClass }} {{ $errors->has('curriculum_grade_level_ID') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select curriculum</option>
                            @foreach ($curriculums as $curriculum)
                                <option value="{{ $curriculum->curriculum_ID }}" @selected((string) old('curriculum_grade_level_ID') === (string) $curriculum->curriculum_ID)>{{ $curriculum->name }}</option>
                            @endforeach
                        </select>
                        @error('curriculum_grade_level_ID')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="section_room" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Room</label>
                        <input id="section_room" name="room" type="text" value="{{ old('room') }}"
                            class="{{ $fieldClass }} {{ $errors->has('room') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('room')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="section_capacity" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Capacity <span class="text-red-500">*</span></label>
                        <input id="section_capacity" name="capacity" type="text" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" autocomplete="off" value="{{ old('capacity') }}" required
                            class="{{ $fieldClass }} {{ $errors->has('capacity') ? 'border-red-300' : 'border-gray-200' }}">
                        <p class="mt-1 text-xs text-gray-500">Numbers only, up to 100.</p>
                        @error('capacity')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeSectionModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="sectionSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Section
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSectionModal(section = null) {
        const form = document.getElementById('sectionForm');
        const method = document.getElementById('section_method');
        const title = document.getElementById('sectionModalTitle');
        const submit = document.getElementById('sectionSubmit');
        const modal = document.getElementById('sectionModal');
        const updateRouteTemplate = '{{ route('admin.section-config.update', ['section' => '__SECTION__']) }}';

        if (section) {
            title.textContent = 'Edit Section';
            submit.textContent = 'Update Section';
            form.action = updateRouteTemplate.replace('__SECTION__', section.section_ID);
            method.value = 'PUT';
            document.getElementById('section_name').value = section.name || '';
            document.getElementById('section_cluster_id').value = section.cluster_ID || '';
            document.getElementById('section_grade_level').value = section.grade_level || '';
            document.getElementById('section_staff_id').value = section.staff_ID || '';
            document.getElementById('section_sy_id').value = section.SY_ID || '';
            document.getElementById('section_curriculum_grade_level_id').value = section.curriculum_grade_level_ID || '';
            document.getElementById('section_room').value = section.room || '';
            document.getElementById('section_capacity').value = section.capacity || '';
        } else {
            title.textContent = 'Add Section';
            submit.textContent = 'Save Section';
            form.action = '{{ route('admin.section-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        toggleSectionClusterRequirement();
        document.getElementById('section_name').focus();
    }

    function closeSectionModal() {
        const modal = document.getElementById('sectionModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    document.getElementById('sectionModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeSectionModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('sectionModal').getAttribute('data-open') === 'true') {
            closeSectionModal();
        }
    });

    function toggleSectionClusterRequirement() {
        const gradeSelect = document.getElementById('section_grade_level');
        const clusterSelect = document.getElementById('section_cluster_id');
        const hint = document.getElementById('section_cluster_hint');
        const isSeniorHigh = ['grade_11', 'grade_12'].includes(gradeSelect.value);

        clusterSelect.required = isSeniorHigh;
        clusterSelect.disabled = !isSeniorHigh;
        hint.textContent = isSeniorHigh
            ? 'Required for senior high school sections.'
            : 'Junior high school sections do not use clusters.';

        if (!isSeniorHigh) {
            clusterSelect.value = '';
        }
    }

    document.getElementById('section_grade_level').addEventListener('change', toggleSectionClusterRequirement);
    toggleSectionClusterRequirement();

    function limitSectionCapacity(input) {
        const digits = String(input.value).replace(/\D/g, '').slice(0, 3);
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
</script>
@endsection
