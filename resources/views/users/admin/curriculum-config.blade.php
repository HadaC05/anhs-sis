@extends('users.admin.layout')

@section('title', 'Curriculum')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $curriculumModalOpen = old('_form') === 'curriculum' && $errors->any();
    $curriculumSubjectModalOpen = old('_form') === 'curriculum_subject' && $errors->any();
    $requestedTab = request('tab');
    $activeTab = $curriculumSubjectModalOpen
        ? 'curriculum_subjects'
        : ($curriculumModalOpen
            ? 'curriculums'
            : (in_array($requestedTab, ['curriculum_subjects', 'curriculum_overview'], true) ? $requestedTab : 'curriculums'));
    $gradeLabels = collect($gradeLevelOptions)->pluck('label', 'value');
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Curriculum</h1>
        <p class="mt-1 text-sm text-gray-500">Configure curricula and assign subjects by grade level and semester.</p>
    </div>
    <div>
        <button type="button" id="addCurriculumHeaderBtn" onclick="openCurriculumModal()"
            class="{{ $activeTab === 'curriculums' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;">
            Add Curriculum
        </button>
        <button type="button" id="addCurriculumSubjectHeaderBtn" onclick="openCurriculumSubjectModal()"
            class="{{ $activeTab === 'curriculum_subjects' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;">
            Assign Subject
        </button>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any() && ! in_array(old('_form'), ['curriculum', 'curriculum_subject'], true))
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

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="border-b border-gray-100 px-4 pt-2">
        <nav class="flex flex-wrap gap-1" role="tablist" aria-label="Curriculum tabs">
            <button type="button" id="curriculumsTabBtn" onclick="switchCurriculumTab('curriculums')" role="tab"
                aria-selected="{{ $activeTab === 'curriculums' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'curriculums' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Curriculum
                <span id="curriculumsTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'curriculums' ? '' : 'hidden' }}"></span>
            </button>
            <button type="button" id="curriculumSubjectsTabBtn" onclick="switchCurriculumTab('curriculum_subjects')" role="tab"
                aria-selected="{{ $activeTab === 'curriculum_subjects' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'curriculum_subjects' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Subjects
                <span id="curriculumSubjectsTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'curriculum_subjects' ? '' : 'hidden' }}"></span>
            </button>
            <button type="button" id="curriculumOverviewTabBtn" onclick="switchCurriculumTab('curriculum_overview')" role="tab"
                aria-selected="{{ $activeTab === 'curriculum_overview' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'curriculum_overview' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Overview
                <span id="curriculumOverviewTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'curriculum_overview' ? '' : 'hidden' }}"></span>
            </button>
        </nav>
    </div>

    <div id="curriculumsTabPanel" class="{{ $activeTab === 'curriculums' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="curriculums">
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
                    <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculums']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Name</th>
                        <th class="border-r border-gray-200 px-5 py-4">Description</th>
                        <th class="border-r border-gray-200 px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($curriculums as $curriculum)
                        @php
                            $curriculumPayload = [
                                'curriculum_ID' => $curriculum->curriculum_ID,
                                'name' => $curriculum->name,
                                'description' => $curriculum->description,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $curriculum->name }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $curriculum->description ?: '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $curriculum->status ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                    {{ $curriculum->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openCurriculumModal(@json($curriculumPayload))'
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
                {{ $curriculums->appends(['tab' => 'curriculums'])->withQueryString()->links() }}
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
                    <option value="first" @selected(request('curriculum_subjects_semester') === 'first')>First</option>
                    <option value="second" @selected(request('curriculum_subjects_semester') === 'second')>Second</option>
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

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Curriculum</th>
                        <th class="border-r border-gray-200 px-5 py-4">Subject</th>
                        <th class="border-r border-gray-200 px-5 py-4">Cluster</th>
                        <th class="border-r border-gray-200 px-5 py-4">Grade Level</th>
                        <th class="border-r border-gray-200 px-5 py-4">Semester</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($curriculumSubjects as $item)
                        @php
                            $subjectPayload = [
                                'curr_subj_ID' => $item->curr_subj_ID,
                                'curriculum_ID' => $item->curriculum_ID,
                                'subject_ID' => $item->subject_ID,
                                'cluster_ID' => $item->cluster_ID,
                                'grade_level' => $item->grade_level,
                                'semester' => $item->semester,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ optional($item->curriculum)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ optional($item->subject)->code ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ optional($item->subject)->title ?? '—' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($item->cluster)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $gradeLabels[$item->grade_level] ?? str_replace('_', ' ', $item->grade_level) }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ ucfirst($item->semester) }}</td>
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
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-500">No curriculum subjects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($curriculumSubjects->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $curriculumSubjects->appends(['tab' => 'curriculum_subjects'])->withQueryString()->links() }}
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
                    $grouped = $curriculum->curriculumSubjects->groupBy(fn ($item) => $item->grade_level.'|'.$item->semester);
                    $slots = collect($gradeLevelOptions)->flatMap(fn ($gradeLevel) => [
                        ['key' => $gradeLevel['value'].'|first', 'label' => $gradeLevel['label'].' · First Semester'],
                        ['key' => $gradeLevel['value'].'|second', 'label' => $gradeLevel['label'].' · Second Semester'],
                    ]);
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
    class="fixed inset-0 z-[100] {{ $curriculumSubjectModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="curriculumSubjectModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Assign Subject</h3>
                </div>
                <button type="button" onclick="closeCurriculumSubjectModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="curriculumSubjectForm" action="{{ route('admin.curriculum-config.subjects.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="curriculum_subject">
            <input type="hidden" id="curriculum_subject_method" name="_method" value="POST">
            <div class="space-y-4 px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="curriculum_subject_curriculum_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Curriculum <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_curriculum_id" name="curriculum_ID" required
                            class="{{ $fieldClass }} {{ $errors->has('curriculum_ID') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select curriculum</option>
                            @foreach ($curriculumOptions as $curriculum)
                                <option value="{{ $curriculum->curriculum_ID }}" @selected(old('_form') === 'curriculum_subject' && (string) old('curriculum_ID') === (string) $curriculum->curriculum_ID)>{{ $curriculum->name }}</option>
                            @endforeach
                        </select>
                        @error('curriculum_ID')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                    <div>
                        <label for="curriculum_subject_subject_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Subject <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_subject_id" name="subject_ID" required
                            class="{{ $fieldClass }} {{ $errors->has('subject_ID') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->subject_ID }}" data-cluster="{{ $subject->cluster_ID }}" @selected(old('_form') === 'curriculum_subject' && (string) old('subject_ID') === (string) $subject->subject_ID)>{{ $subject->code }} — {{ $subject->title }}</option>
                            @endforeach
                        </select>
                        @error('subject_ID')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="curriculum_subject_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Cluster</label>
                        <select id="curriculum_subject_cluster_id" name="cluster_ID"
                            class="{{ $fieldClass }} {{ $errors->has('cluster_ID') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">No cluster (Junior High)</option>
                            @foreach ($clusters as $cluster)
                                <option value="{{ $cluster->cluster_ID }}" @selected(old('_form') === 'curriculum_subject' && (string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                        @error('cluster_ID')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                    <div>
                        <label for="curriculum_subject_grade_level" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_grade_level" name="grade_level" required
                            class="{{ $fieldClass }} {{ $errors->has('grade_level') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select</option>
                            @foreach ($gradeLevelOptions as $gradeLevel)
                                <option value="{{ $gradeLevel['value'] }}" @selected(old('_form') === 'curriculum_subject' && old('grade_level') === $gradeLevel['value'])>{{ $gradeLevel['label'] }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                    <div>
                        <label for="curriculum_subject_semester" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Semester <span class="text-red-500">*</span></label>
                        <select id="curriculum_subject_semester" name="semester" required
                            class="{{ $fieldClass }} {{ $errors->has('semester') && old('_form') === 'curriculum_subject' ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select</option>
                            <option value="first" @selected(old('_form') === 'curriculum_subject' && old('semester') === 'first')>First</option>
                            <option value="second" @selected(old('_form') === 'curriculum_subject' && old('semester') === 'second')>Second</option>
                        </select>
                        @error('semester')
                            @if (old('_form') === 'curriculum_subject')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeCurriculumSubjectModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" id="curriculumSubjectSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setTabButtonState(button, indicator, isActive) {
        button.classList.toggle('text-[#296374]', isActive);
        button.classList.toggle('text-gray-500', !isActive);
        button.classList.toggle('hover:text-gray-700', !isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        indicator.classList.toggle('hidden', !isActive);
    }

    function switchCurriculumTab(tab) {
        const isCurriculums = tab === 'curriculums';
        const isSubjects = tab === 'curriculum_subjects';
        const isOverview = tab === 'curriculum_overview';

        document.getElementById('curriculumsTabPanel').classList.toggle('hidden', !isCurriculums);
        document.getElementById('curriculumSubjectsTabPanel').classList.toggle('hidden', !isSubjects);
        document.getElementById('curriculumOverviewTabPanel').classList.toggle('hidden', !isOverview);

        setTabButtonState(document.getElementById('curriculumsTabBtn'), document.getElementById('curriculumsTabIndicator'), isCurriculums);
        setTabButtonState(document.getElementById('curriculumSubjectsTabBtn'), document.getElementById('curriculumSubjectsTabIndicator'), isSubjects);
        setTabButtonState(document.getElementById('curriculumOverviewTabBtn'), document.getElementById('curriculumOverviewTabIndicator'), isOverview);

        const addCurriculumBtn = document.getElementById('addCurriculumHeaderBtn');
        const addSubjectBtn = document.getElementById('addCurriculumSubjectHeaderBtn');
        addCurriculumBtn.classList.toggle('hidden', !isCurriculums);
        addCurriculumBtn.classList.toggle('inline-flex', isCurriculums);
        addSubjectBtn.classList.toggle('hidden', !isSubjects);
        addSubjectBtn.classList.toggle('inline-flex', isSubjects);

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

    function closeCurriculumModal() {
        const modal = document.getElementById('curriculumModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    function openCurriculumSubjectModal(item = null) {
        const form = document.getElementById('curriculumSubjectForm');
        const method = document.getElementById('curriculum_subject_method');
        const title = document.getElementById('curriculumSubjectModalTitle');
        const submit = document.getElementById('curriculumSubjectSubmit');
        const modal = document.getElementById('curriculumSubjectModal');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.subjects.update', ['curriculumSubject' => '__ITEM__']) }}';

        if (item) {
            title.textContent = 'Edit Assignment';
            submit.textContent = 'Update Assignment';
            form.action = updateRouteTemplate.replace('__ITEM__', item.curr_subj_ID);
            method.value = 'PUT';
            document.getElementById('curriculum_subject_curriculum_id').value = item.curriculum_ID || '';
            document.getElementById('curriculum_subject_subject_id').value = item.subject_ID || '';
            document.getElementById('curriculum_subject_cluster_id').value = item.cluster_ID || '';
            document.getElementById('curriculum_subject_grade_level').value = item.grade_level || '';
            document.getElementById('curriculum_subject_semester').value = item.semester || '';
        } else {
            title.textContent = 'Assign Subject';
            submit.textContent = 'Save Assignment';
            form.action = '{{ route('admin.curriculum-config.subjects.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('curriculum_subject_curriculum_id').focus();
    }

    function closeCurriculumSubjectModal() {
        const modal = document.getElementById('curriculumSubjectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    document.getElementById('curriculum_subject_subject_id').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const clusterId = selected ? selected.getAttribute('data-cluster') : '';
        document.getElementById('curriculum_subject_cluster_id').value = clusterId || '';
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
    });
</script>
@endsection
