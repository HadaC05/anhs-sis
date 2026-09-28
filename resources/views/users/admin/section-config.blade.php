@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@php
    $managementRoutePrefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.';
@endphp

@section('title', 'Sections')

@push('toasts')
    @include('users.admin.partials.section-toasts')
@endpush

@section('content')
@php
    $showSeniorColumns = $sections->getCollection()->contains(fn ($section) => in_array($section->grade_level, ['grade_11', 'grade_12'], true));
    $modalOpen = ! $detailsTab && $errors->any();
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Sections</h1>
    </div>

</div>

<nav aria-label="Section tabs" class="mb-4" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));width:100%;gap:4px;padding:4px;background:#dce9ed;border-radius:12px;">
    @foreach(['creation' => 'Section Creation', 'details' => 'Section Details'] as $tab => $label)
        @php
            $activeTab = ($tab === 'details') === $detailsTab;
        @endphp
        <a href="{{ route($managementRoutePrefix.'section-config.index', ['tab' => $tab]) }}" @if($activeTab) aria-current="page" @endif style="display:flex;align-items:center;justify-content:center;text-align:center;padding:9px 8px;font-size:13px;border-radius:8px;font-weight:800;background:{{ $activeTab ? '#296374' : '#f0f6f8' }};color:{{ $activeTab ? '#fff' : '#245566' }};box-shadow:{{ $activeTab ? '0 3px 8px #29637440' : 'none' }};">{{ $label }}</a>
    @endforeach
</nav>

@include('users.admin.partials.copy-sections-modal')



<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="border-b border-gray-100 px-4 py-4">
            @unless($detailsTab)
    <div class="mb-3 flex flex-wrap justify-end gap-2">
    <button id="copySectionsTrigger" type="button" onclick="openCopySectionsModal()" class="inline-flex items-center justify-center rounded-lg border border-[#296374] bg-white px-4 py-2.5 text-sm font-bold text-[#296374] shadow-sm transition hover:bg-gray-50">Copy Sections</button>
    <button type="button" onclick="openSectionModal()" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
        Add Section
    </button>
    </div>
    @endunless
        <form method="GET" action="{{ route($managementRoutePrefix.'section-config.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <input type="hidden" name="tab" value="{{ $detailsTab ? 'details' : 'creation' }}">
            <div class="relative min-w-0 sm:col-span-2">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                </svg>
                <input type="search" aria-label="Search sections" name="search" value="{{ request('search') }}" placeholder="Search section or room"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            <select name="cluster_ID" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All clusters</option>
                @foreach ($clusters as $cluster)
                    <option value="{{ $cluster->cluster_ID }}" @selected((int) request('cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                @endforeach
            </select>
            <select aria-label="Grade level" name="grade_level" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All grades</option>
                @foreach ($gradeLevels as $level)
                    <option value="{{ $level['value'] }}" @selected(request('grade_level') === $level['value'])>{{ preg_replace('/\D+/', '', $level['label']) }}</option>
                @endforeach
            </select>
            <select aria-label="School year" name="SY_ID" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All years</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->SY_ID }}" @selected($selectedSchoolYearId === (int) $year->SY_ID)>{{ $year->school_year }}</option>
                @endforeach
            </select>
            <select name="curriculum_grade_level_ID" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All curricula</option>
                @foreach ($curriculums as $curriculum)
                    <option value="{{ $curriculum->curriculum_ID }}" @selected((int) request('curriculum_grade_level_ID') === (int) $curriculum->curriculum_ID)>{{ $curriculum->name }}</option>
                @endforeach
            </select>
            <select name="status" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="active" @selected(($status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(($status ?? 'active') === 'inactive')>Inactive</option>
                <option value="all" @selected(($status ?? 'active') === 'all')>All statuses</option>
            </select>
            <select name="per_page" class="h-10 w-full min-w-0 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([5, 10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            @if (request()->hasAny(['search', 'cluster_ID', 'grade_level', 'SY_ID', 'curriculum_grade_level_ID', 'per_page', 'status']))
                <a href="{{ route($managementRoutePrefix.'section-config.index', ['tab' => $detailsTab ? 'details' : 'creation']) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left [overflow-wrap:anywhere]" style="min-width:{{ $showSeniorColumns ? 850 : 650 }}px">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-4 py-1.5">Name</th>
                    @if($showSeniorColumns)
                    <th class="border-r border-gray-200 px-4 py-1.5">Cluster</th>
                    @endif
                    <th class="border-r border-gray-200 px-4 py-1.5">Grade</th>
                    @if($showSeniorColumns)
                    <th class="border-r border-gray-200 px-4 py-1.5">Semester</th>
                    @endif
                    <th class="border-r border-gray-200 px-4 py-1.5">Adviser</th>
                    <th class="border-r border-gray-200 px-4 py-1.5">Academic Year</th>
                    @if($detailsTab)
                    <th class="border-r border-gray-200 px-4 py-1.5">Availability</th>
                    @else
                    <th class="border-r border-gray-200 px-4 py-1.5">Capacity</th>
                    <th class="border-r border-gray-200 px-4 py-1.5">Status</th>
                    @endif
                    <th class="px-4 py-1.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-[13px] leading-5">
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
                        <td class="border-r border-gray-100 px-4 py-1.5 font-semibold text-gray-900">{{ $section->name }}</td>
                        @if($showSeniorColumns)
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ optional($section->cluster)->name ?? '—' }}</td>
                        @endif
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ preg_replace('/\D+/', '', $section->grade_level) }}</td>
                        @if($showSeniorColumns)
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ $section->curriculumGradeLevel?->gradingSemester?->label ?? '—' }}</td>
                        @endif
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ $adviserName }}</td>
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ optional($section->academicYear)->school_year ?? '—' }}</td>
                        @if($detailsTab)
                        <td class="border-r border-gray-100 px-4 py-1.5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ ($section->capacity > 0 && $section->enrolled_students_count >= $section->capacity) ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ ($section->capacity > 0 && $section->enrolled_students_count >= $section->capacity) ? 'Full' : 'Available' }}</span>
                        </td>
                        @else
                        <td class="border-r border-gray-100 px-4 py-1.5 text-gray-700">{{ $section->capacity }}</td>
                        <td class="border-r border-gray-100 px-4 py-1.5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $section->status ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                {{ $section->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        @endif
                        <td class="px-4 py-1.5">
                            <div class="flex items-center justify-end gap-1">
                                @if($detailsTab)
                                    <a href="{{ route($managementRoutePrefix.'section-config.index', array_merge(request()->except('section'), ['tab' => 'details', 'section' => $section->section_ID])) }}" class="inline-flex rounded-lg p-1.5 text-[#296374] transition hover:bg-[#296374]/10" title="View students" aria-label="View students in {{ $section->name }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                                    </a>
                                @else
                                <button type="button" onclick='openSectionModal(@json($sectionPayload))'
                                    class="rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route($managementRoutePrefix.'section-config.toggle-status', $section) }}" method="POST" class="inline" data-confirm-action="{{ $section->status ? 'Archive' : 'Activate' }}" data-confirm-message="{{ $section->status ? 'Archive this section?' : 'Activate this section?' }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg p-1.5 text-gray-500 transition {{ $section->status ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-600' }}" title="{{ $section->status ? 'Archive' : 'Activate' }}">
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
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($detailsTab ? 8 : 9) - ($showSeniorColumns ? 0 : 2) }}" class="px-6 py-16 text-center text-gray-500">No sections found.</td>
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

@include('users.admin.partials.section-details-modal')

<div id="sectionModal" role="dialog" aria-modal="true" aria-labelledby="sectionModalTitle" data-open="{{ $modalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $modalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="mx-auto flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="shrink-0 border-b border-gray-300 bg-[#296374] px-6 py-4">
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

        <form id="sectionForm" class="flex min-h-0 flex-col" action="{{ route($managementRoutePrefix.'section-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="section_method" name="_method" value="POST">

            <div class="min-h-0 space-y-4 overflow-y-auto px-6 py-5">
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
                                <option value="{{ $level['value'] }}" @selected(old('grade_level') === $level['value'])>{{ preg_replace('/\D+/', '', $level['label']) }}</option>
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

            <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
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
    // Keep the dialog above the portal header and outside container-query boundaries.
    const sectionDialog = document.getElementById('sectionModal');
    document.body.appendChild(sectionDialog);
    let sectionDialogTrigger = null;
    let sectionBodyOverflow = document.body.style.overflow;

    if (sectionDialog.dataset.open === 'true') {
        document.body.style.overflow = 'hidden';
    }

    function openSectionModal(section = null) {
        const form = document.getElementById('sectionForm');
        const method = document.getElementById('section_method');
        const title = document.getElementById('sectionModalTitle');
        const submit = document.getElementById('sectionSubmit');
        const modal = document.getElementById('sectionModal');
        const updateRouteTemplate = '{{ route($managementRoutePrefix.'section-config.update', ['section' => '__SECTION__']) }}';

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
            form.action = '{{ route($managementRoutePrefix.'section-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        sectionDialogTrigger = document.activeElement;
        if (modal.dataset.open !== 'true') {
            sectionBodyOverflow = document.body.style.overflow;
        }
        document.body.style.overflow = 'hidden';
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
        document.body.style.overflow = sectionBodyOverflow;
        sectionDialogTrigger?.focus();
    }

    document.getElementById('sectionModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeSectionModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Tab' && sectionDialog.dataset.open === 'true') {
            const fields = Array.from(sectionDialog.querySelectorAll('button, input, select, a[href], [tabindex="0"]'))
                .filter((field) => !field.disabled && field.getClientRects().length > 0);
            const first = fields[0];
            const last = fields[fields.length - 1];
            if (e.shiftKey && (document.activeElement === first || !sectionDialog.contains(document.activeElement))) {
                e.preventDefault();
                last?.focus();
            } else if (!e.shiftKey && (document.activeElement === last || !sectionDialog.contains(document.activeElement))) {
                e.preventDefault();
                first?.focus();
            }
        }
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
