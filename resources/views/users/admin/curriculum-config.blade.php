@extends('users.admin.layout')

@section('title', 'Curriculum')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $curriculumModalOpen = old('_form') === 'curriculum' && $errors->any();
    $gradeLevelModalOpen = old('_form') === 'grade_level' && $errors->any();
    $curriculumSubjectModalOpen = old('_form') === 'curriculum_subject' && $errors->any();
    $requestedTab = request('tab');
    $activeTab = $curriculumSubjectModalOpen
        ? 'curriculum_subjects'
        : (($curriculumModalOpen || $gradeLevelModalOpen)
            ? 'grade_levels'
            : (in_array($requestedTab, ['curricula', 'grade_levels', 'curriculum_subjects'], true) ? $requestedTab : 'curricula'));
    $gradeLabels = collect($gradeLevelOptions)->pluck('label', 'value');
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-800 md:text-2xl">Curriculum</h1>
</div>

@if (session('show_toast'))
    <div id="curriculumSuccessToast" role="status" class="fixed right-5 top-5 z-[120] flex w-full max-w-sm items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4 text-sm text-emerald-800 shadow-xl">
        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
        </div>
        <p class="pt-0.5 font-medium">Changes saved successfully.</p>
        <button type="button" onclick="closeCurriculumSuccessToast()" class="ml-auto rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" aria-label="Close notification">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"></path></svg>
        </button>
    </div>
@elseif (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any() && ! in_array(old('_form'), ['curriculum', 'grade_level', 'curriculum_subject'], true))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Curricula</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalCurriculums) }}</p>
        <p class="mt-1 text-xs text-gray-500">Configured curriculum records</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Active</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($activeCurriculumCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Available for section assignment</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Archived</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($inactiveCurriculumCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Hidden from new assignments</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Assigned Subjects</p>
        <p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($curriculumSubjectCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Subjects linked by grade and semester</p>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-white p-4 shadow-lg shadow-gray-200/70">
    <nav class="grid overflow-hidden rounded-lg border border-[#296374]/40 bg-white shadow-sm sm:grid-cols-3" role="tablist" aria-label="Curriculum tabs">
            <button type="button" id="curriculaTabBtn" onclick="switchCurriculumTab('curricula')" role="tab"
                aria-selected="{{ $activeTab === 'curricula' ? 'true' : 'false' }}"
                class="flex items-center justify-center gap-2 border-b border-[#296374]/30 px-3 py-3 text-sm font-semibold transition sm:border-b-0 sm:border-r {{ $activeTab === 'curricula' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
                Curricula
            </button>
            <button type="button" id="gradeLevelsTabBtn" onclick="switchCurriculumTab('grade_levels')" role="tab"
                aria-selected="{{ $activeTab === 'grade_levels' ? 'true' : 'false' }}"
                class="flex items-center justify-center gap-2 border-b border-[#296374]/30 px-3 py-3 text-sm font-semibold transition sm:border-b-0 sm:border-r {{ $activeTab === 'grade_levels' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
                Grade Levels
            </button>
            <button type="button" id="curriculumSubjectsTabBtn" onclick="switchCurriculumTab('curriculum_subjects')" role="tab"
                aria-selected="{{ $activeTab === 'curriculum_subjects' ? 'true' : 'false' }}"
                class="flex items-center justify-center gap-2 border-b border-[#296374]/30 px-3 py-3 text-sm font-semibold transition sm:border-b-0 sm:border-r {{ $activeTab === 'curriculum_subjects' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
                Subjects
            </button>
</nav>
</div>

<div class="mt-4 overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div id="curriculaTabPanel" class="{{ $activeTab === 'curricula' ? '' : 'hidden' }}">
        <div class="flex items-center justify-between border-b border-gray-100 bg-[#296374]/[0.03] px-4 py-4"><h2 class="text-sm font-bold text-gray-800">Curricula</h2><button type="button" onclick="openMasterCurriculumModal()" class="rounded-lg px-4 py-2 text-sm font-bold text-white" style="background-color:#296374">Add Curriculum</button></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead><tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600"><th class="px-5 py-4">Name</th><th class="px-5 py-4">Description</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Report</th><th class="px-5 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-gray-200">@forelse ($masterCurricula as $curricula)<tr><td class="px-5 py-4 font-semibold text-gray-900">{{ $curricula->name }}</td><td class="px-5 py-4 text-gray-700">{{ $curricula->description ?: '—' }}</td><td class="px-5 py-4">{{ $curricula->dataStatus?->label ?? '—' }}</td><td class="px-5 py-4 text-right"><a href="{{ route('admin.curriculum-config.curricula.report', $curricula) }}" target="_blank" title="Preview report" class="mr-2 inline-flex rounded p-2 text-[#296374] hover:bg-[#296374]/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a><a href="{{ route('admin.curriculum-config.curricula.report', ['curricula' => $curricula, 'download' => 1]) }}" title="Download report" class="inline-flex rounded p-2 text-[#296374] hover:bg-[#296374]/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg></a></td><td class="px-5 py-4 text-right"><button type="button" title="Edit" onclick='openMasterCurriculumModal(@json(['curricula_ID' => $curricula->curricula_ID, 'name' => $curricula->name, 'description' => $curricula->description]))' class="mr-2 inline-flex rounded p-2 text-[#296374] hover:bg-[#296374]/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11.8 15H9v-2.8l8.6-8.6z"/></svg></button><form method="POST" action="{{ route('admin.curriculum-config.curricula.toggle-status', $curricula) }}" class="inline">@csrf @method('PATCH')<button type="submit" title="{{ $curricula->dataStatus?->key === 'active' ? 'Archive' : 'Activate' }}" class="inline-flex rounded p-2 text-[#296374] hover:bg-[#296374]/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/></svg></button></form></td></tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No curricula yet.</td></tr>@endforelse</tbody></table></div>
    </div>
    <div id="gradeLevelsTabPanel" class="{{ $activeTab === 'grade_levels' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="grade_levels">
                <input type="hidden" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}">
                <input type="hidden" name="curriculum_subjects_curriculum_ID" value="{{ request('curriculum_subjects_curriculum_ID') }}">
                <input type="hidden" name="curriculum_subjects_cluster_ID" value="{{ request('curriculum_subjects_cluster_ID') }}">
                <input type="hidden" name="curriculum_subjects_grade_level" value="{{ request('curriculum_subjects_grade_level') }}">
                <input type="hidden" name="curriculum_subjects_semester" value="{{ request('curriculum_subjects_semester') }}">
                <input type="hidden" name="curriculum_subjects_per_page" value="{{ request('curriculum_subjects_per_page', $curriculumSubjectsPerPage ?? 10) }}">
                <input type="hidden" name="overview_curriculum_ID" value="{{ request('overview_curriculum_ID') }}">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="curriculum_search" value="{{ request('curriculum_search') }}" placeholder="Search name or description"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="curriculum_status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('curriculum_status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('curriculum_status') === 'inactive')>Inactive</option>
                </select>
                <select name="curriculum_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([5, 10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($curriculumPerPage ?? 10) === $size)>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['curriculum_search', 'curriculum_status', 'curriculum_per_page']))
                    <a href="{{ route('admin.curriculum-config.index', ['tab' => 'grade_levels']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="flex items-center justify-between gap-4 border-b border-gray-100 bg-[#296374]/[0.03] px-4 py-4">
            <div><h2 class="text-sm font-bold text-gray-800">Curriculum Grade Levels</h2></div>
            <button type="button" onclick="openGradeLevelModal()" class="inline-flex h-10 shrink-0 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Add Grade Level</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Grade Level</th>
                        <th class="border-r border-gray-200 px-5 py-4">Semester</th>
                        <th class="border-r border-gray-200 px-5 py-4">Cluster</th>
                        <th class="border-r border-gray-200 px-5 py-4">Name</th>
                        <th class="border-r border-gray-200 px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($curriculums as $curriculum)
                        @php
                            $curriculumPayload = [
                                'curriculum_ID' => $curriculum->curriculum_ID,
                                'curricula_ID' => $curriculum->curricula_ID,
                                'grade_ID' => $curriculum->grade_ID,
                                'semester_ID' => $curriculum->semester_ID,
                                'cluster_ID' => $curriculum->cluster_ID,
                                'name' => $curriculum->name,
                                'description' => $curriculum->description,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $curriculum->gradeLevel?->grade_label ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $curriculum->gradingSemester?->label ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $curriculum->cluster?->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $curriculum->name }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $curriculum->status ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                    {{ $curriculum->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openGradeLevelModal(@json($curriculumPayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.curriculum-config.toggle-status', $curriculum) }}" method="POST" class="inline" onsubmit="return confirm('{{ $curriculum->status ? 'Archive this curriculum?' : 'Activate this curriculum?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition {{ $curriculum->status ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-600' }}" title="{{ $curriculum->status ? 'Archive' : 'Activate' }}">
                                            @if ($curriculum->status)
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
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500">No curriculum records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($curriculums->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $curriculums->appends(['tab' => 'grade_levels'])->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div id="curriculumSubjectsTabPanel" class="{{ $activeTab === 'curriculum_subjects' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="curriculum_subjects">
                <input type="hidden" name="curriculum_search" value="{{ request('curriculum_search') }}">
                <input type="hidden" name="curriculum_status" value="{{ request('curriculum_status') }}">
                <input type="hidden" name="curriculum_per_page" value="{{ request('curriculum_per_page', $curriculumPerPage ?? 10) }}">
                <input type="hidden" name="overview_curriculum_ID" value="{{ request('overview_curriculum_ID') }}">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}" placeholder="Search subject code or title"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="curriculum_subjects_curriculum_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All curricula</option>
                    @foreach ($curriculumOptions as $option)
                        <option value="{{ $option->curriculum_ID }}" @selected((int) request('curriculum_subjects_curriculum_ID') === (int) $option->curriculum_ID)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <select name="curriculum_subjects_cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($clusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" @selected((int) request('curriculum_subjects_cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <select name="curriculum_subjects_grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevelOptions as $gradeLevel)
                        <option value="{{ $gradeLevel['value'] }}" @selected(request('curriculum_subjects_grade_level') === $gradeLevel['value'])>{{ $gradeLevel['label'] }}</option>
                    @endforeach
                </select>
                <select name="curriculum_subjects_semester" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All semesters</option>
                    @foreach ($gradingSemesters as $semester)
                        <option value="{{ $semester->semester_ID }}" @selected((int) request('curriculum_subjects_semester') === (int) $semester->semester_ID)>{{ $semester->label }}</option>
                    @endforeach
                </select>
                <select name="curriculum_subjects_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([5, 10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($curriculumSubjectsPerPage ?? 10) === $size)>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['curriculum_subjects_search', 'curriculum_subjects_curriculum_ID', 'curriculum_subjects_cluster_ID', 'curriculum_subjects_grade_level', 'curriculum_subjects_semester', 'curriculum_subjects_per_page']))
                    <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="flex items-center justify-between gap-4 border-b border-gray-100 bg-[#296374]/[0.03] px-4 py-4">
            <div><h2 class="text-sm font-bold text-gray-800">Curriculum Subjects</h2></div>
            <button type="button" onclick="openCurriculumSubjectModal()" class="inline-flex h-10 shrink-0 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Assign Subjects</button>
        </div>

        <div class="space-y-3 bg-slate-50 p-4">
                    @forelse ($curriculumSubjectCurriculums as $item)
                        @php
                            $assignedSubjects = $item->curriculumSubjects;
                            $assignedSubjectsPayload = $assignedSubjects->map(function ($subject): array {
                                return [
                                    'subject_ID' => $subject->subject_ID,
                                    'cluster_ID' => $subject->cluster_ID,
                                    'grade_ID' => $subject->grade_ID,
                                    'semester_ID' => $subject->semester_ID,
                                ];
                            })->values();
                        @endphp
                        <div>
                            <details class="group overflow-hidden rounded-lg border border-cyan-100 bg-cyan-50 transition open:shadow-sm">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold text-[#294968] [&::-webkit-details-marker]:hidden">
                                        <span>{{ $item->name }} <span class="ml-2 text-xs font-medium text-gray-500">{{ $assignedSubjects->count() }} assigned subjects</span></span>
                                        <svg class="h-5 w-5 text-[#296374] transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"></path></svg>
                                    </summary>
                                    <div class="border-t border-cyan-100 bg-white px-5 py-4">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Assigned Subjects</p>
                                            <button type="button" data-assignments='@json($assignedSubjectsPayload)' data-curriculum-name="{{ $item->name }}" onclick="event.stopPropagation(); openCurriculumSubjectModal(null, {{ $item->curriculum_ID }}, JSON.parse(this.dataset.assignments), this.dataset.curriculumName);" class="relative z-10 rounded-lg border border-[#296374]/30 bg-white px-3 py-1.5 text-xs font-bold text-[#296374] transition hover:bg-[#296374]/5">Edit</button>
                                        </div>
                                        <div class="grid gap-2 md:grid-cols-2">
                                            @foreach ($assignedSubjects as $subject)
                                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm">
                                                    <div>
                                                        <p class="font-semibold text-gray-900">{{ $subject->subject?->code }} <span class="font-normal text-gray-600">— {{ $subject->subject?->title }}</span></p>
                                                        <p class="mt-1 text-xs text-gray-500">{{ str_replace('grade_', 'Grade ', $subject->grade_level) }} · {{ $subject->gradingSemester?->label }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                            </details>
                        </div>
                    @empty
                        @if (false)
                        @php
                            $subjectPayload = [
                                'curr_subj_ID' => $item->curr_subj_ID,
                                'curriculum_ID' => $item->curriculum_ID,
                                'subject_ID' => $item->subject_ID,
                                'cluster_ID' => $item->cluster_ID,
                                'grade_ID' => $item->grade_ID,
                                'semester_ID' => $item->semester_ID,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ optional($item->curriculum)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ optional($item->subject)->code ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ optional($item->subject)->title ?? '—' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($item->cluster)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $item->gradeLevel?->grade_label ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $item->gradingSemester?->label ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openCurriculumSubjectModal(@json($subjectPayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.curriculum-config.subjects.delete', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this subject from the curriculum?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600" title="Remove">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endif
                        <div class="rounded-lg border border-dashed border-gray-200 bg-white px-6 py-16 text-center text-gray-500">No curriculum subjects yet.</div>
                    @endforelse
        </div>

        @if ($curriculumSubjectCurriculums->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $curriculumSubjectCurriculums->appends(['tab' => 'curriculum_subjects'])->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div id="curriculumOverviewTabPanel" class="{{ $activeTab === 'curriculum_overview' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="curriculum_overview">
                <input type="hidden" name="curriculum_search" value="{{ request('curriculum_search') }}">
                <input type="hidden" name="curriculum_status" value="{{ request('curriculum_status') }}">
                <input type="hidden" name="curriculum_per_page" value="{{ request('curriculum_per_page', $curriculumPerPage ?? 10) }}">
                <input type="hidden" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}">
                <input type="hidden" name="curriculum_subjects_curriculum_ID" value="{{ request('curriculum_subjects_curriculum_ID') }}">
                <input type="hidden" name="curriculum_subjects_cluster_ID" value="{{ request('curriculum_subjects_cluster_ID') }}">
                <input type="hidden" name="curriculum_subjects_grade_level" value="{{ request('curriculum_subjects_grade_level') }}">
                <input type="hidden" name="curriculum_subjects_semester" value="{{ request('curriculum_subjects_semester') }}">
                <input type="hidden" name="curriculum_subjects_per_page" value="{{ request('curriculum_subjects_per_page', $curriculumSubjectsPerPage ?? 10) }}">

                <select name="overview_curriculum_ID" class="h-10 min-w-[220px] rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All curricula</option>
                    @foreach ($curriculumOptions as $option)
                        <option value="{{ $option->curriculum_ID }}" @selected((int) request('overview_curriculum_ID') === (int) $option->curriculum_ID)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->filled('overview_curriculum_ID'))
                    <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculum_overview']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="space-y-6 p-4 md:p-6">
            @forelse ($curriculumOverview as $curriculum)
                @php
                    $grouped = $curriculum->curriculumSubjects->groupBy(fn ($item) => $item->grade_ID.'|'.$item->semester_ID);
                    $slots = collect($gradeLevelOptions)->flatMap(function ($gradeLevel) use ($gradingSemesters) {
                        $gradeId = \App\Models\GradeLevel::idForValue($gradeLevel['value']);
                        $fullYearId = $gradingSemesters->firstWhere('key', \App\Models\GradingSemester::FULL_YEAR)?->semester_ID;
                        $firstId = $gradingSemesters->firstWhere('key', \App\Models\GradingSemester::FIRST)?->semester_ID;
                        $secondId = $gradingSemesters->firstWhere('key', \App\Models\GradingSemester::SECOND)?->semester_ID;
                        return in_array($gradeLevel['value'], ['grade_11', 'grade_12'], true)
                            ? [['key' => $gradeId.'|'.$firstId, 'label' => $gradeLevel['label'].' · First Semester'], ['key' => $gradeId.'|'.$secondId, 'label' => $gradeLevel['label'].' · Second Semester']]
                            : [['key' => $gradeId.'|'.$fullYearId, 'label' => $gradeLevel['label'].' · Full Year']];
                    });
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-lg font-bold text-gray-900">{{ $curriculum->name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $curriculum->description ?: 'No description provided.' }}</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        @foreach ($slots as $slot)
                            <div class="rounded-lg border border-gray-200 bg-white">
                                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                                    <h4 class="text-sm font-semibold text-gray-700">{{ $slot['label'] }}</h4>
                                </div>
                                <div class="p-4">
                                    @php $items = $grouped->get($slot['key'], collect())->sortBy(fn ($row) => optional($row->subject)->code); @endphp
                                    @if ($items->isEmpty())
                                        <p class="text-sm text-gray-400">No subjects assigned.</p>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach ($items as $row)
                                                <li class="text-sm text-gray-700">
                                                    <span class="font-semibold">{{ optional($row->subject)->code }}</span>
                                                    <span class="text-gray-600">— {{ optional($row->subject)->title }}</span>
                                                    @if (optional($row->cluster)->name)
                                                        <span class="text-xs text-gray-400">({{ $row->cluster->name }})</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center text-gray-500">
                    No curriculum subjects found for the selected filter.
                </div>
            @endforelse
        </div>
    </div>
</div>

<div id="gradeLevelModal" role="dialog" aria-modal="true" aria-labelledby="gradeLevelModalTitle" data-open="{{ $gradeLevelModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[105] {{ $gradeLevelModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="gradeLevelModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Curriculum Grade Level</h3>
                </div>
                <button type="button" onclick="closeGradeLevelModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        <form id="gradeLevelForm" method="POST" action="{{ route('admin.curriculum-config.store') }}">
            @csrf
            <input type="hidden" name="_form" value="grade_level">
            <input id="grade_level_method" type="hidden" name="_method" value="POST">
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="grade_level_curricula_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Curriculum <span class="text-red-500">*</span></label>
                    <select id="grade_level_curricula_id" name="curricula_ID" required class="{{ $fieldClass }} {{ $errors->has('curricula_ID') && old('_form') === 'grade_level' ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : 'border-gray-200' }}">
                        <option value="">Select curriculum</option>
                        @foreach($activeMasterCurricula as $master)<option value="{{ $master->curricula_ID }}" @selected((old('_form') === 'grade_level' && (string) old('curricula_ID') === (string) $master->curricula_ID) || (old('_form') !== 'grade_level' && $loop->first))>{{ $master->name }}</option>@endforeach
                    </select>
                    @error('curricula_ID') @if(old('_form') === 'grade_level')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror
                </div>
                <div>
                    <label for="grade_level_grade_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Level <span class="text-red-500">*</span></label>
                    <select id="grade_level_grade_id" name="grade_ID" required class="{{ $fieldClass }} {{ $errors->has('grade_ID') && old('_form') === 'grade_level' ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : 'border-gray-200' }}">
                        <option value="">Select grade level</option>
                        @foreach($gradeLevelOptions as $grade)<option value="{{ App\Models\GradeLevel::idForValue($grade['value']) }}" @selected(old('_form') === 'grade_level' && (string) old('grade_ID') === (string) App\Models\GradeLevel::idForValue($grade['value']))>{{ $grade['label'] }}</option>@endforeach
                    </select>
                    @error('grade_ID') @if(old('_form') === 'grade_level')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror
                </div>
                <div>
                    <label for="grade_level_semester_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Semester <span class="text-red-500">*</span></label>
                    <select id="grade_level_semester_id" name="semester_ID" required class="{{ $fieldClass }} {{ $errors->has('semester_ID') && old('_form') === 'grade_level' ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : 'border-gray-200' }}">
                        <option value="">Select semester</option>
                        @foreach($gradingSemesters as $semester)<option value="{{ $semester->semester_ID }}" data-key="{{ $semester->key }}" @selected(old('_form') === 'grade_level' && (string) old('semester_ID') === (string) $semester->semester_ID)>{{ $semester->label }}</option>@endforeach
                    </select>
                    @error('semester_ID') @if(old('_form') === 'grade_level')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror
                </div>
                <div id="gradeLevelClusterField" class="hidden">
                    <label for="grade_level_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Cluster <span class="text-red-500">*</span></label>
                    <select id="grade_level_cluster_id" name="cluster_ID" class="{{ $fieldClass }} {{ $errors->has('cluster_ID') && old('_form') === 'grade_level' ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : 'border-gray-200' }}">
                        <option value="">Select cluster</option>
                        @foreach($clusters as $cluster)<option value="{{ $cluster->cluster_ID }}" @selected(old('_form') === 'grade_level' && (string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>@endforeach
                    </select>
                    @error('cluster_ID') @if(old('_form') === 'grade_level')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror
                </div>
                <div>
                    <label for="grade_level_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Display Name <span class="text-red-500">*</span></label>
                    <input id="grade_level_name" name="name" required value="{{ old('_form') === 'grade_level' ? old('name') : '' }}" class="{{ $fieldClass }} {{ $errors->has('name') && old('_form') === 'grade_level' ? 'border-red-500 focus:border-red-500 focus:ring-red-500/15' : 'border-gray-200' }}">
                    @error('name') @if(old('_form') === 'grade_level')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeGradeLevelModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Save Grade Level</button>
            </div>
        </form>
    </div>
</div>

<div id="curriculumModal" role="dialog" aria-modal="true" aria-labelledby="curriculumModalTitle" data-open="{{ $curriculumModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $curriculumModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="curriculumModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Curriculum</h3>
                </div>
                <button type="button" onclick="closeCurriculumModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="curriculumForm" action="{{ route('admin.curriculum-config.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="curriculum">
            <input type="hidden" id="curriculum_method" name="_method" value="POST">
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="curriculum_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Name <span class="text-red-500">*</span></label>
                    <input id="curriculum_name" name="name" type="text" value="{{ old('name') }}" required
                        class="{{ $fieldClass }} {{ $errors->has('name') && old('_form') === 'curriculum' ? 'border-red-300' : 'border-gray-200' }}">
                    @error('name')
                        @if (old('_form') === 'curriculum')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="curriculum_description" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Description</label>
                    <textarea id="curriculum_description" name="description" rows="3"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('description') && old('_form') === 'curriculum' ? 'border-red-300' : 'border-gray-200' }}">{{ old('_form') === 'curriculum' ? old('description') : '' }}</textarea>
                    @error('description')
                        @if (old('_form') === 'curriculum')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeCurriculumModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" id="curriculumSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Save Curriculum</button>
            </div>
        </form>
    </div>
</div>

<div id="curriculumSubjectModal" role="dialog" aria-modal="true" aria-labelledby="curriculumSubjectModalTitle" data-open="{{ $curriculumSubjectModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $curriculumSubjectModalOpen ? 'flex' : 'hidden' }} items-start justify-center overflow-y-auto bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto mb-8 w-full max-w-5xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="curriculumSubjectModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Assign Subjects</h3>
                </div>
                <button type="button" onclick="closeCurriculumSubjectModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="curriculumSubjectForm" action="{{ route('admin.curriculum-config.subjects.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_form" value="curriculum_subject">
            <input type="hidden" id="curriculum_subject_method" name="_method" value="POST">
            <input type="hidden" id="curriculum_subject_edit_mode" name="edit_mode" value="">
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="flex flex-col gap-5">
                <div class="order-1">
                        <label for="curriculum_subject_curriculum_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Level <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_curriculum_id" name="curriculum_ID" required
                            class="{{ $fieldClass }} {{ $errors->has('curriculum_ID') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select grade level</option>
                            @foreach ($unassignedCurriculumOptions as $curriculum)
                                <option value="{{ $curriculum->curriculum_ID }}" data-grade-id="{{ $curriculum->grade_ID }}" data-cluster-id="{{ $curriculum->cluster_ID }}" data-grade-category="{{ $curriculum->gradeLevel?->category }}" @selected(old('_form') === 'curriculum_subject' && (string) old('curriculum_ID') === (string) $curriculum->curriculum_ID)>{{ $curriculum->name }}</option>
                            @endforeach
                        </select>
                        @error('curriculum_ID')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                    <div class="order-4">
                        <label for="curriculum_subject_subject_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Subjects <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_subject_id" disabled multiple size="7"
                            class="hidden w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('subject_ID') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->subject_ID }}" data-school-level="{{ $subject->school_level }}" @selected(old('_form') === 'curriculum_subject' && (string) old('subject_ID') === (string) $subject->subject_ID)>{{ $subject->code }} — {{ $subject->title }}</option>
                            @endforeach
                        </select>
                        @error('subject_ID')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p id="curriculumSubjectHint" class="text-xs text-gray-500">Select a grade level to see available subjects.</p>
                            <div class="flex items-center gap-3">
                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-medium text-[#296374]">
                                    <input id="selectAllCurriculumSubjects" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]" disabled>
                                    Select all
                                </label>
                                <span id="curriculumSubjectSelectedCount" class="text-xs font-medium text-gray-500">0 selected</span>
                            </div>
                        </div>
                        <div class="mb-3 grid gap-2 sm:grid-cols-[minmax(0,1fr)_220px_220px]">
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"></path>
                                </svg>
                                <input id="curriculumSubjectSearch" type="search" placeholder="Search subject code or title"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white py-2 pl-10 pr-10 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15">
                                <button type="button" id="clearCurriculumSubjectSearch" class="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Clear subject search">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"></path>
                                    </svg>
                                </button>
                            </div>
                            <select id="curriculumSubjectTypeFilter" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15">
                                <option value="">All subject types</option>
                                @foreach ($subjectTypes as $subjectType)
                                    <option value="{{ $subjectType->key }}">{{ $subjectType->label }}</option>
                                @endforeach
                            </select>
                            <select id="curriculumSubjectSchoolLevelFilter" aria-label="School level, determined by the selected grade level" class="h-10 rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-700" disabled>
                                <option value="">School level</option>
                                <option value="Junior High School">Junior High School</option>
                                <option value="Senior High School">Senior High School</option>
                            </select>
                        </div>
                        <div id="curriculumSubjectCards" class="grid max-h-80 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($subjects as $subject)
                                <label class="subject-card hidden cursor-pointer rounded-lg border border-gray-200 bg-gray-50 p-3 transition hover:border-[#296374]/50 hover:bg-[#296374]/5" data-school-level="{{ $subject->school_level }}" data-type="{{ $subject->subjectType?->key ?? '' }}">
                                    <span class="flex items-start gap-3">
                                        <input type="checkbox" name="subject_ID[]" value="{{ $subject->subject_ID }}" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]" disabled>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-[#294968]">{{ $subject->code }}</span>
                                            <span class="mt-0.5 block text-xs text-gray-500">{{ $subject->title }}</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeCurriculumSubjectModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" id="curriculumSubjectSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Assign Subjects</button>
            </div>
        </form>
    </div>
</div>

<div id="confirmCurriculumSubjectModal" role="dialog" aria-modal="true" aria-labelledby="confirmCurriculumSubjectTitle" class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-900/70 p-4">
    <div class="w-full max-w-md overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <h3 id="confirmCurriculumSubjectTitle" class="text-lg font-bold text-white">Confirm Assignment</h3>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm leading-6 text-gray-600">You are about to assign <span id="confirmCurriculumSubjectCount" class="font-bold text-gray-800">0 subjects</span> to this curriculum. Do you want to continue?</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <button type="button" onclick="closeCurriculumSubjectConfirmation()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
            <button type="button" onclick="confirmCurriculumSubjectAssignment()" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Confirm Assignment</button>
        </div>
    </div>
</div>

<script>
    function setTabButtonState(button, isActive) {
        button.classList.toggle('bg-[#296374]', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('bg-white', !isActive);
        button.classList.toggle('text-[#296374]', !isActive);
        button.classList.toggle('hover:bg-[#296374]/10', !isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    }

    function switchCurriculumTab(tab) {
        const isCurricula = tab === 'curricula';
        const isGradeLevels = tab === 'grade_levels';
        const isSubjects = tab === 'curriculum_subjects';

        document.getElementById('curriculaTabPanel').classList.toggle('hidden', !isCurricula);
        document.getElementById('gradeLevelsTabPanel').classList.toggle('hidden', !isGradeLevels);
        document.getElementById('curriculumSubjectsTabPanel').classList.toggle('hidden', !isSubjects);

        setTabButtonState(document.getElementById('curriculaTabBtn'), isCurricula);
        setTabButtonState(document.getElementById('gradeLevelsTabBtn'), isGradeLevels);
        setTabButtonState(document.getElementById('curriculumSubjectsTabBtn'), isSubjects);

        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    function openCurriculumModal(curriculum = null) {
        const form = document.getElementById('curriculumForm');
        const method = document.getElementById('curriculum_method');
        const title = document.getElementById('curriculumModalTitle');
        const submit = document.getElementById('curriculumSubmit');
        const modal = document.getElementById('curriculumModal');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.update', ['curriculum' => '__CURR__']) }}';

        if (curriculum) {
            title.textContent = 'Edit Curriculum';
            submit.textContent = 'Update Curriculum';
            form.action = updateRouteTemplate.replace('__CURR__', curriculum.curriculum_ID);
            method.value = 'PUT';
            document.getElementById('curriculum_name').value = curriculum.name || '';
            document.getElementById('curriculum_description').value = curriculum.description || '';
        } else {
            title.textContent = 'Add Curriculum';
            submit.textContent = 'Save Curriculum';
            form.action = '{{ route('admin.curriculum-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('curriculum_name').focus();
    }

    function openMasterCurriculumModal(curriculum = null) {
        const form = document.getElementById('curriculumForm');
        const method = document.getElementById('curriculum_method');
        const title = document.getElementById('curriculumModalTitle');
        const submit = document.getElementById('curriculumSubmit');
        const modal = document.getElementById('curriculumModal');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.curricula.update', ['curricula' => '__CURRICULA__']) }}';

        title.textContent = curriculum ? 'Edit Curriculum' : 'Add Curriculum';
        submit.textContent = curriculum ? 'Update Curriculum' : 'Save Curriculum';
        form.action = curriculum
            ? updateRouteTemplate.replace('__CURRICULA__', curriculum.curricula_ID)
            : '{{ route('admin.curriculum-config.curricula.store') }}';
        method.value = curriculum ? 'PUT' : 'POST';
        document.getElementById('curriculum_name').value = curriculum?.name || '';
        document.getElementById('curriculum_description').value = curriculum?.description || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('curriculum_name').focus();
    }

    function closeCurriculumModal() {
        const modal = document.getElementById('curriculumModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    function syncGradeLevelName(autoSelectSemester = false) {
        const grade = document.getElementById('grade_level_grade_id');
        const semester = document.getElementById('grade_level_semester_id');
        const cluster = document.getElementById('grade_level_cluster_id');
        const clusterField = document.getElementById('gradeLevelClusterField');
        const selected = grade.options[grade.selectedIndex];
        const isShs = /Grade (11|12)/.test(selected?.text || '');
        clusterField.classList.toggle('hidden', !isShs);
        cluster.required = isShs;
        if (autoSelectSemester && grade.value) {
            semester.value = Array.from(semester.options).find(option => option.dataset.key === (isShs ? 'first' : 'full_year'))?.value || '';
        }
        document.getElementById('grade_level_name').value = isShs
            ? [selected?.text, semester.options[semester.selectedIndex]?.text, cluster.options[cluster.selectedIndex]?.text].filter(Boolean).join(' - ')
            : (selected?.text || '');
    }
    function openGradeLevelModal(item = null) {
        const form = document.getElementById('gradeLevelForm');
        form.action = item ? '{{ route('admin.curriculum-config.update', ['curriculum' => '__ID__']) }}'.replace('__ID__', item.curriculum_ID) : '{{ route('admin.curriculum-config.store') }}';
        document.getElementById('grade_level_method').value = item ? 'PUT' : 'POST';
        document.getElementById('grade_level_curricula_id').value = item?.curricula_ID || document.getElementById('grade_level_curricula_id').value;
        document.getElementById('grade_level_grade_id').value = item?.grade_ID || '';
        document.getElementById('grade_level_semester_id').value = item?.semester_ID || document.getElementById('grade_level_semester_id').value;
        document.getElementById('grade_level_cluster_id').value = item?.cluster_ID || '';
        syncGradeLevelName(false);
        if (item) {
            document.getElementById('grade_level_name').value = item.name || '';
        }
        const modal = document.getElementById('gradeLevelModal');
        modal.classList.replace('hidden', 'flex');
        modal.setAttribute('data-open', 'true');
    }
    function closeGradeLevelModal() {
        const modal = document.getElementById('gradeLevelModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('grade_level_grade_id')?.addEventListener('change', () => syncGradeLevelName(true));
        ['grade_level_semester_id', 'grade_level_cluster_id']
            .forEach(id => document.getElementById(id)?.addEventListener('change', () => syncGradeLevelName(false)));
        syncGradeLevelName(false);

        const gradeLevelForm = document.getElementById('gradeLevelForm');
        gradeLevelForm.addEventListener('invalid', event => {
            const field = event.target;
            field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/15');
        }, true);
        ['input', 'change'].forEach(eventName => gradeLevelForm.addEventListener(eventName, event => {
            const field = event.target;
            if (field.checkValidity()) {
                field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/15');
            }
        }));
    });

    function openCurriculumSubjectModal(item = null, curriculumId = null, existingAssignments = [], curriculumName = '') {
        const form = document.getElementById('curriculumSubjectForm');
        const method = document.getElementById('curriculum_subject_method');
        const title = document.getElementById('curriculumSubjectModalTitle');
        const submit = document.getElementById('curriculumSubjectSubmit');
        const modal = document.getElementById('curriculumSubjectModal');
        const editMode = document.getElementById('curriculum_subject_edit_mode');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.subjects.update', ['curriculumSubject' => '__ITEM__']) }}';

        if (item) {
            title.textContent = 'Edit Assignment';
            submit.textContent = 'Update Assignment';
            form.action = updateRouteTemplate.replace('__ITEM__', item.curr_subj_ID);
            method.value = 'PUT';
            document.getElementById('curriculum_subject_curriculum_id').value = item.curriculum_ID || '';
            document.getElementById('curriculum_subject_subject_id').value = item.subject_ID || '';
            document.querySelectorAll('.subject-card input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = String(checkbox.value) === String(item.subject_ID);
            });
            editMode.value = '';
        } else {
            title.textContent = curriculumId ? 'Edit Subjects' : 'Assign Subjects';
            submit.textContent = curriculumId ? 'Save Subjects' : 'Assign Subjects';
            form.action = '{{ route('admin.curriculum-config.subjects.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
            const curriculumSelect = document.getElementById('curriculum_subject_curriculum_id');
            if (curriculumId && !Array.from(curriculumSelect.options).some(option => String(option.value) === String(curriculumId))) {
                const option = new Option(curriculumName || 'Selected grade level', curriculumId, false, false);
                const assignment = existingAssignments[0] || item || {};
                option.dataset.gradeId = assignment.grade_ID || '';
                option.dataset.clusterId = assignment.cluster_ID || '';
                curriculumSelect.add(option);
            }
            curriculumSelect.value = curriculumId || '';
            editMode.value = curriculumId ? '1' : '';

        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        syncCurriculumSubjectSemester();

        if (!item && curriculumId && existingAssignments.length) {
            const firstAssignment = existingAssignments[0];
            const existingSubjectIds = existingAssignments
                .filter(assignment => String(assignment.grade_ID) === String(firstAssignment.grade_ID)
                    && String(assignment.semester_ID) === String(firstAssignment.semester_ID)
                    && String(assignment.cluster_ID || '') === String(firstAssignment.cluster_ID || ''))
                .map(assignment => String(assignment.subject_ID));

            document.querySelectorAll('.subject-card input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = existingSubjectIds.includes(String(checkbox.value));
            });
            syncCurriculumSubjectSemester();
        }
        document.getElementById('curriculum_subject_curriculum_id').focus();
    }

    function closeCurriculumSubjectModal() {
        const modal = document.getElementById('curriculumSubjectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    let curriculumSubjectAssignmentConfirmed = false;

    function openCurriculumSubjectConfirmation() {
        const selectedSubjects = document.querySelectorAll('.subject-card input[type="checkbox"]:checked').length;
        document.getElementById('confirmCurriculumSubjectCount').textContent = `${selectedSubjects} ${selectedSubjects === 1 ? 'subject' : 'subjects'}`;

        const modal = document.getElementById('confirmCurriculumSubjectModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCurriculumSubjectConfirmation() {
        const modal = document.getElementById('confirmCurriculumSubjectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmCurriculumSubjectAssignment() {
        curriculumSubjectAssignmentConfirmed = true;
        closeCurriculumSubjectConfirmation();
        document.getElementById('curriculumSubjectForm').requestSubmit();
    }

    function closeCurriculumSuccessToast() {
        document.getElementById('curriculumSuccessToast')?.remove();
    }

    function syncCurriculumSubjectSemester() {
        const curriculum = document.getElementById('curriculum_subject_curriculum_id');
        const hint = document.getElementById('curriculumSubjectHint');
        const selectedCount = document.getElementById('curriculumSubjectSelectedCount');
        const subjectSearch = document.getElementById('curriculumSubjectSearch');
        const clearSubjectSearch = document.getElementById('clearCurriculumSubjectSearch');
        const subjectTypeFilter = document.getElementById('curriculumSubjectTypeFilter');
        const schoolLevelFilter = document.getElementById('curriculumSubjectSchoolLevelFilter');
        const selectAllSubjects = document.getElementById('selectAllCurriculumSubjects');
        const subjectCards = Array.from(document.querySelectorAll('.subject-card'));
        const searchTerm = subjectSearch.value.trim().toLowerCase();
        const selectedType = subjectTypeFilter.value;
        const selectedCurriculum = curriculum.options[curriculum.selectedIndex];
        const hasGradeLevel = Boolean(curriculum.value);
        const schoolLevel = selectedCurriculum?.dataset.gradeCategory === 'Senior High School'
            ? 'Senior High School'
            : (hasGradeLevel ? 'Junior High School' : '');

        schoolLevelFilter.value = schoolLevel;

        subjectCards.forEach(card => {
            const isAvailable = hasGradeLevel && card.dataset.schoolLevel === schoolLevel;
            const matches = isAvailable
                && (selectedType === '' || card.dataset.type === selectedType)
                && (searchTerm === '' || card.textContent.toLowerCase().includes(searchTerm));
            const checkbox = card.querySelector('input[type="checkbox"]');

            card.classList.toggle('hidden', !matches);
            checkbox.disabled = !isAvailable;
            if (!isAvailable) {
                checkbox.checked = false;
            }
            card.classList.toggle('border-[#296374]', checkbox.checked);
            card.classList.toggle('bg-[#296374]/5', checkbox.checked);
        });

        const visibleSubjectCards = subjectCards.filter(card => !card.classList.contains('hidden'));
        const visibleCheckboxes = visibleSubjectCards.map(card => card.querySelector('input[type="checkbox"]'));
        selectAllSubjects.disabled = visibleCheckboxes.length === 0;
        selectAllSubjects.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
        selectAllSubjects.indeterminate = visibleCheckboxes.some(checkbox => checkbox.checked) && !selectAllSubjects.checked;

        hint.textContent = !hasGradeLevel
            ? 'Select a grade level to see available subjects.'
            : `Showing ${schoolLevel} subjects. Choose one or more subjects to assign.`;
        selectedCount.textContent = `${document.querySelectorAll('.subject-card input:checked').length} selected`;
        clearSubjectSearch.classList.toggle('hidden', searchTerm === '');
        clearSubjectSearch.classList.toggle('inline-flex', searchTerm !== '');
    }

    document.getElementById('curriculum_subject_curriculum_id').addEventListener('change', syncCurriculumSubjectSemester);
    document.getElementById('curriculumSubjectSearch').addEventListener('input', syncCurriculumSubjectSemester);
    document.getElementById('curriculumSubjectTypeFilter').addEventListener('change', syncCurriculumSubjectSemester);
    document.getElementById('clearCurriculumSubjectSearch').addEventListener('click', function () {
        document.getElementById('curriculumSubjectSearch').value = '';
        syncCurriculumSubjectSemester();
        document.getElementById('curriculumSubjectSearch').focus();
    });
    document.getElementById('selectAllCurriculumSubjects').addEventListener('change', function () {
        document.querySelectorAll('.subject-card:not(.hidden) input[type="checkbox"]:not(:disabled)').forEach(checkbox => {
            checkbox.checked = this.checked;
            checkbox.closest('.subject-card').classList.toggle('border-[#296374]', this.checked);
            checkbox.closest('.subject-card').classList.toggle('bg-[#296374]/5', this.checked);
        });
        syncCurriculumSubjectSemester();
    });
    document.querySelectorAll('.subject-card input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            this.closest('.subject-card').classList.toggle('border-[#296374]', this.checked);
            this.closest('.subject-card').classList.toggle('bg-[#296374]/5', this.checked);
            document.getElementById('curriculumSubjectSelectedCount').textContent = `${document.querySelectorAll('.subject-card input:checked').length} selected`;
        });
    });
    document.getElementById('curriculumSubjectForm').addEventListener('submit', function (event) {
        const isCreatingAssignments = document.getElementById('curriculum_subject_method').value === 'POST';

        if (isCreatingAssignments && !curriculumSubjectAssignmentConfirmed) {
            event.preventDefault();
            openCurriculumSubjectConfirmation();
        }
    });

    document.getElementById('curriculumModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeCurriculumModal();
        }
    });

    document.getElementById('curriculumSubjectModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeCurriculumSubjectModal();
        }
    });

    document.getElementById('confirmCurriculumSubjectModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeCurriculumSubjectConfirmation();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }

        if (document.getElementById('curriculumModal').getAttribute('data-open') === 'true') {
            closeCurriculumModal();
        }

        if (document.getElementById('curriculumSubjectModal').getAttribute('data-open') === 'true') {
            closeCurriculumSubjectModal();
        }

        closeCurriculumSubjectConfirmation();
    });

    if (document.getElementById('curriculumSuccessToast')) {
        setTimeout(closeCurriculumSuccessToast, 4000);
    }
</script>
@endsection
