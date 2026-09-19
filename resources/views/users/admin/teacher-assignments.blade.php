@extends('users.admin.layout')

@section('title', 'Teacher Assignments')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $advisoryModalOpen = old('_form') === 'advisory-edit' && $errors->any();
    $assignAdvisoryModalOpen = old('_form') === 'advisory' && $errors->any();
    $assignmentModalOpen = old('_form') === 'assignment' && $errors->any();
    $bulkModalOpen = old('_form') === 'bulk' && $errors->any();
    $reassignModalOpen = old('_form') === 'reassign' && $errors->any();
    $activeTab = $assignmentModalOpen || $bulkModalOpen || $reassignModalOpen || request('tab') === 'subjects'
        ? 'subjects'
        : 'advisory';
    $advisoryTeacherYearIds = $sections->whereNotNull('staff_ID')
        ->groupBy('staff_ID')
        ->map(fn ($teacherSections) => $teacherSections->pluck('SY_ID')->unique()->implode(','));
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-800 md:text-2xl">Teacher Assignments</h1>
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any() && ! in_array(old('_form'), ['advisory', 'assignment', 'bulk', 'reassign'], true))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Advisory Sections</p>
        <p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($advisoryCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Sections with an assigned adviser</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Unassigned</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($unassignedSectionCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Sections still needing an adviser</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Subject Assignments</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($assignmentCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Teacher-to-subject assignments</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Teachers</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($teacherCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Available for assignment</p>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-white p-4 shadow-lg shadow-gray-200/70">
    <div>
        <nav class="grid overflow-hidden rounded-lg border border-[#296374]/40 bg-white shadow-sm sm:grid-cols-2" aria-label="Teacher assignment tabs">
            <button type="button" data-assignment-tab="advisory" aria-selected="{{ $activeTab === 'advisory' ? 'true' : 'false' }}"
                class="assignment-tab flex items-center justify-center gap-2 border-b border-[#296374]/30 px-3 py-3 text-sm font-semibold transition sm:border-b-0 sm:border-r {{ $activeTab === 'advisory' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0 0 0-8 0z"></path></svg>
                Advisory
            </button>
            <button type="button" data-assignment-tab="subjects" aria-selected="{{ $activeTab === 'subjects' ? 'true' : 'false' }}"
                class="assignment-tab flex items-center justify-center gap-2 px-3 py-3 text-sm font-semibold transition {{ $activeTab === 'subjects' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Subjects
            </button>
        </nav>
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <section data-assignment-panel="advisory" class="{{ $activeTab === 'advisory' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.teacher-assignments.index') }}" class="flex flex-wrap items-center gap-1.5">
                <div class="relative"><svg class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path></svg><input type="search" name="advisory_search" value="{{ $advisorySearch }}" placeholder="Search" class="h-7 w-32 border border-gray-200 bg-white pl-7 pr-2 text-[10px] text-gray-700 outline-none focus:border-[#296374]"></div>
                <select name="advisory_SY_ID" class="h-7 border border-gray-200 bg-white px-2 text-[10px] font-medium text-gray-700 outline-none focus:border-[#296374]">
                    <option value="">All school years</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->SY_ID }}" @selected((string) $advisorySchoolYearId === (string) $year->SY_ID)>{{ $year->school_year }}</option>
                    @endforeach
                </select>
                <select name="advisory_grade_level" class="h-7 border border-gray-200 bg-white px-2 text-[10px] font-medium text-gray-700 outline-none focus:border-[#296374]">
                    <option value="">All grade levels</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" @selected($advisoryGradeLevel === $level['value'])>{{ $level['label'] }}</option>
                    @endforeach
                </select>
                <select name="advisory_per_page" class="h-7 border border-gray-200 bg-white px-2 text-[10px] font-medium text-gray-700 outline-none focus:border-[#296374]">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($advisoryPerPage ?? 10) === $size)>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-7 items-center border border-[#296374] px-2.5 text-[10px] font-bold text-white" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['advisory_search', 'advisory_SY_ID', 'advisory_grade_level', 'advisory_per_page']))
                    <a href="{{ route('admin.teacher-assignments.index') }}" class="inline-flex h-7 items-center px-2 text-[10px] font-medium text-red-500 hover:underline">Clear Filters</a>
                @endif
            </form>
        </div>

        <div class="flex items-center justify-between gap-4 border-b border-gray-100 bg-[#296374]/[0.03] px-4 py-4">
            <div><h2 class="text-sm font-bold text-gray-800">Assign Adviser</h2><p class="mt-1 text-xs text-gray-500">Choose an unassigned section and an available teacher.</p></div>
            <button type="button" onclick="openAssignAdvisoryModal()" class="inline-flex h-10 shrink-0 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Assign Adviser</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Section</th>
                        <th class="border-r border-gray-200 px-5 py-4">Adviser</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($advisorySections as $section)
                        @php $adviser = $section->adviser; @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $section->name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ optional($section->gradeLevel)->grade_label ?? '—' }}{{ $section->cluster ? ' · '.$section->cluster->name : '' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $adviser?->last_name }}, {{ $adviser?->first_name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $adviser?->username ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end">
                                    <button type="button" onclick="openAdvisoryModal({{ (int) $section->section_ID }}, @js($section->name), @js($section->grade_level))" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit adviser">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-gray-500">No advisory sections assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($advisorySections->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $advisorySections->appends(['tab' => 'advisory'])->withQueryString()->links() }}
            </div>
        @endif
    </section>

    <section data-assignment-panel="subjects" class="{{ $activeTab === 'subjects' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.teacher-assignments.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="subjects">
                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search section, subject, or teacher"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" @selected(request('grade_level') === $level['value'])>{{ $level['label'] }}</option>
                    @endforeach
                </select>
                <select name="cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($clusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" @selected((string) request('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <select name="SY_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All years</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->SY_ID }}" @selected((string) request('SY_ID') === (string) $year->SY_ID)>{{ $year->school_year }}</option>
                    @endforeach
                </select>
                <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($perPage ?? 15) === $size)>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['search', 'grade_level', 'cluster_ID', 'SY_ID', 'per_page']))
                    <a href="{{ route('admin.teacher-assignments.index', ['tab' => 'subjects']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" onclick="openBulkModal()" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-bold text-gray-700 transition hover:border-[#296374]/30 hover:text-[#296374]">Bulk Assign</button>
                    <button type="button" onclick="openAssignmentModal()" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">New Assignment</button>
                </div>
            </form>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse ($subjectRows as $subjectRow)
                @php $subject = $subjectRow->subject; $subjectAssignments = $subjectRow->assignments; @endphp
                <details class="group bg-white transition hover:bg-[#296374]/[0.03]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3"><svg class="h-4 w-4 shrink-0 text-[#296374] transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"></path></svg><div><p class="font-semibold text-gray-900">{{ $subject?->code ?? '—' }}</p><p class="mt-0.5 text-xs text-gray-500">{{ $subject?->title ?? '—' }}</p></div></div>
                        <span class="rounded-full bg-[#296374]/10 px-2.5 py-1 text-xs font-bold text-[#296374]">{{ $subjectAssignments->count() }} {{ Str::plural('section', $subjectAssignments->count()) }}</span>
                    </summary>
                    <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                        @if ($subjectAssignments->isEmpty())
                            <p class="py-3 text-sm text-gray-500">No assignments yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[560px] text-left text-sm">
                                    <thead class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                        <tr>
                                            <th class="pb-2">Section</th>
                                            <th class="pb-2">Grade Level</th>
                                            <th class="pb-2">Teacher</th>
                                            <th class="pb-2 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($subjectAssignments as $assignment)
                                            @php $section = $assignment->section; $teacher = $assignment->staff; @endphp
                                            <tr>
                                                <td class="py-3 font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td>
                                                <td class="py-3 text-gray-600">{{ $section?->grade_level ? str_replace('grade_', 'Grade ', $section->grade_level) : '—' }}</td>
                                                <td class="py-3 text-gray-700">{{ $teacher?->last_name }}, {{ $teacher?->first_name }}</td>
                                                <td class="py-3 text-right">
                                                    <button type="button" onclick="openReassignModal({{ (int) $assignment->assignment_ID }}, {{ (int) $assignment->staff_ID }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#296374] transition hover:bg-white" title="Edit" aria-label="Edit assignment">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.1 2.1 0 0 1 2.97 2.97L8.25 18.04 4 19.1l1.06-4.25L16.862 3.487Z"></path>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    {{-- Legacy compact table retained below for source-history continuity.
                    <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">@if ($subjectAssignments->isEmpty())<p class="py-3 text-sm text-gray-500">No assignments yet.</p>@else<div class="overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead class="text-[10px] font-bold uppercase tracking-wider text-gray-400"><tr><th class="pb-2">Section</th><th class="pb-2">Grade Level</th><th class="pb-2">Teacher</th><th class="pb-2">Semester</th><th class="pb-2 text-right">Actions</th></tr></thead><tbody class="divide-y divide-gray-200">@foreach ($subjectAssignments as $assignment) @php $section = $assignment->section; $teacher = $assignment->staff; @endphp<tr><td class="py-3 font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td><td class="py-3 text-gray-600">{{ optional($section?->gradeLevel)->grade_label ?? '—' }}</td><td class="py-3 text-gray-700">{{ $teacher?->last_name }}, {{ $teacher?->first_name }}</td><td class="py-3 text-gray-600">{{ optional($assignment->curriculumSubject)->semester ? ucfirst($assignment->curriculumSubject->semester) : '—' }}</td><td class="py-3 text-right"><button type="button" onclick="openReassignModal({{ (int) $assignment->assignment_ID }}, {{ (int) $assignment->staff_ID }})" class="rounded-lg px-2 py-1 text-xs font-semibold text-[#296374] hover:bg-white">Edit</button><form action="{{ route('admin.teacher-assignments.delete', $assignment) }}" method="POST" class="inline" onsubmit="return confirm('Remove this subject assignment?');">@csrf @method('DELETE')<button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button></form></td></tr>@endforeach</tbody></table></div>@endif</div>
                    --}}
                </details>
            @empty
                <div class="px-6 py-16 text-center text-gray-500">No assignments found.</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto">
            <table class="w-full min-w-[880px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-4">Subject</th>
                        <th class="px-5 py-4 text-right">Assigned Sections</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($assignments as $assignment)
                        @php
                            $section = $assignment->section;
                            $subject = $assignment->curriculumSubject?->subject;
                            $teacher = $assignment->staff;
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $section?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ optional($section?->gradeLevel)->grade_label ?? '—' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $subject?->code ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $subject?->title ?? '—' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $teacher?->last_name }}, {{ $teacher?->first_name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $teacher?->username ?? '—' }}</p>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($assignment->curriculumSubject)->semester ? ucfirst($assignment->curriculumSubject->semester) : '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section?->academicYear?->school_year ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                @if (($assignment->locked_grades_count ?? 0) > 0)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200">{{ $assignment->locked_grades_count }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick="openReassignModal({{ (int) $assignment->assignment_ID }}, {{ (int) $assignment->staff_ID }})"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    @if (($assignment->locked_grades_count ?? 0) > 0)
                                        <form action="{{ route('admin.teacher-assignments.unlock-grades', $assignment) }}" method="POST" class="inline" onsubmit="return confirm('Unlock submitted or approved grades for this assignment? The teacher will be able to edit and resubmit them.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg p-2 text-gray-500 transition hover:bg-amber-50 hover:text-amber-700" title="Unlock Grades">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.teacher-assignments.delete', $assignment) }}" method="POST" class="inline" onsubmit="return confirm('Remove this subject assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600" title="Delete">
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
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500">No assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjectRows->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $subjectRows->appends(['tab' => 'subjects'])->withQueryString()->links() }}
            </div>
        @endif
    </section>
</div>

<div id="assignAdvisoryModal" role="dialog" aria-modal="true" aria-labelledby="assignAdvisoryModalTitle" data-open="{{ $assignAdvisoryModalOpen ? 'true' : 'false' }}" class="fixed inset-0 z-[100] {{ $assignAdvisoryModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-3xl overflow-visible rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4"><div class="flex items-start justify-between gap-4"><div><p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p><h3 id="assignAdvisoryModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Assign Adviser</h3></div><button type="button" onclick="closeAssignAdvisoryModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">×</button></div></div>
        <form action="{{ route('admin.teacher-assignments.advisory.assign') }}" method="POST">
            @csrf <input type="hidden" name="_form" value="advisory">
            <div class="space-y-4 px-6 py-5">
                <div class="grid gap-4 md:grid-cols-[minmax(180px,0.7fr)_minmax(0,1.3fr)]">
                    <div><label for="advisory_grade_selector" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Level</label><select id="advisory_grade_selector" class="{{ $fieldClass }} border-gray-200"><option value="">Select grade level</option>@foreach ($gradeLevels as $level)<option value="{{ $level['value'] }}" @selected(old('advisory_grade_level') === $level['value'])>{{ $level['label'] }}</option>@endforeach</select></div>
                    <div><label for="advisory_section_search" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Section <span class="text-red-500">*</span></label><input id="advisory_section_search" type="search" placeholder="Search section" class="mb-2 {{ $fieldClass }} border-gray-200"><select id="advisory_section_id" name="section_ID" required class="{{ $fieldClass }} {{ $errors->has('section_ID') && old('_form') === 'advisory' ? 'border-red-300' : 'border-gray-200' }}"><option value="">Select section</option>@foreach ($sections->whereNull('staff_ID') as $section)<option value="{{ $section->section_ID }}" data-grade="{{ $section->grade_level }}" data-year="{{ $section->SY_ID }}" @selected((string) old('section_ID') === (string) $section->section_ID)>{{ $section->name }} · {{ $section->academicYear?->school_year ?? '—' }}</option>@endforeach</select>@error('section_ID') @if (old('_form') === 'advisory') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @endif @enderror</div>
                </div>
                <div><label for="advisory_staff_search" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Adviser <span class="text-red-500">*</span></label><input id="advisory_staff_search" type="search" placeholder="Search teacher" class="mb-2 {{ $fieldClass }} border-gray-200"><select id="advisory_staff_id" name="staff_ID" required class="{{ $fieldClass }} {{ $errors->has('staff_ID') && old('_form') === 'advisory' ? 'border-red-300' : 'border-gray-200' }}"><option value="">Select teacher</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->staff_id }}" data-advisory-years="{{ $advisoryTeacherYearIds->get($teacher->staff_id, '') }}" @selected((string) old('staff_ID') === (string) $teacher->staff_id)>{{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}</option>@endforeach</select>@error('staff_ID') @if (old('_form') === 'advisory') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @endif @enderror</div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4"><button type="button" onclick="closeAssignAdvisoryModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Assign Adviser</button></div>
        </form>
    </div>
</div>

<div id="advisoryModal" role="dialog" aria-modal="true" aria-labelledby="advisoryModalTitle" data-open="{{ $advisoryModalOpen ? 'true' : 'false' }}" class="fixed inset-0 z-[100] {{ $advisoryModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-visible rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4"><div class="flex items-start justify-between gap-4"><div><p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p><h3 id="advisoryModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Edit Adviser</h3></div><button type="button" onclick="closeAdvisoryModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">×</button></div></div>
        <form id="advisoryEditForm" action="{{ $advisoryModalOpen && old('section_ID') ? route('admin.teacher-assignments.advisory.update', ['section' => old('section_ID')]) : '#' }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="advisory-edit"><input type="hidden" id="edit_advisory_section_id" name="section_ID" value="{{ old('section_ID') }}">
            <div class="space-y-4 px-6 py-5"><div><p class="text-xs font-bold uppercase tracking-wide text-gray-600">Assignment</p><p id="edit_advisory_current_section" class="mt-1 text-sm font-semibold text-gray-900"></p></div><div class="grid gap-4 sm:grid-cols-2"><div><label for="edit_advisory_grade_selector" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Level</label><select id="edit_advisory_grade_selector" class="{{ $fieldClass }} border-gray-200"><option value="">Select grade level</option>@foreach ($gradeLevels as $level)<option value="{{ $level['value'] }}">{{ $level['label'] }}</option>@endforeach</select></div><div><label for="edit_advisory_section_search" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Assign to section</label><input id="edit_advisory_section_search" type="search" placeholder="Search section" class="mb-2 {{ $fieldClass }} border-gray-200"><select id="edit_advisory_target_section_id" name="target_section_ID" class="{{ $fieldClass }} {{ $errors->has('target_section_ID') && old('_form') === 'advisory-edit' ? 'border-red-300' : 'border-gray-200' }}"><option value="">Not assigned to any section</option>@foreach ($sections as $availableSection)<option value="{{ $availableSection->section_ID }}" data-grade="{{ $availableSection->grade_level }}" data-assigned="{{ $availableSection->staff_ID ? 'true' : 'false' }}" @selected((string) old('target_section_ID') === (string) $availableSection->section_ID)>{{ $availableSection->name }} · {{ $availableSection->academicYear?->school_year ?? '—' }}</option>@endforeach</select>@error('target_section_ID') @if (old('_form') === 'advisory-edit')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@endif @enderror</div></div><p class="text-xs text-gray-500">Choose another unassigned section, keep the current one, or remove the advisory assignment.</p></div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4"><button type="button" onclick="closeAdvisoryModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button><button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Update Adviser</button></div>
        </form>
    </div>
</div>

<div id="assignmentModal" role="dialog" aria-modal="true" aria-labelledby="assignmentModalTitle" data-open="{{ $assignmentModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $assignmentModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="assignmentModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">New Assignment</h3>
                </div>
                <button type="button" onclick="closeAssignmentModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="single-assignment-form" action="{{ route('admin.teacher-assignments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="assignment">
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="section_ID" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Section <span class="text-red-500">*</span></label>
                    <select id="section_ID" name="section_ID" required class="{{ $fieldClass }} {{ $errors->has('section_ID') && old('_form') === 'assignment' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->section_ID }}" data-curriculum="{{ $section->curriculum_ID }}" data-grade="{{ $section->grade_level }}" data-cluster="{{ $section->cluster_ID ?? '' }}" @selected((string) old('section_ID') === (string) $section->section_ID)>
                                {{ $section->name }} | {{ optional($section->gradeLevel)->grade_label ?? '—' }} | {{ $section->academicYear?->school_year ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    @error('section_ID')
                        @if (old('_form') === 'assignment')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="curr_subj_ID" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Subject <span class="text-red-500">*</span></label>
                    <select id="curr_subj_ID" name="curr_subj_ID" required class="{{ $fieldClass }} {{ $errors->has('curr_subj_ID') && old('_form') === 'assignment' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select subject</option>
                        @foreach ($curriculumSubjects as $currSubj)
                            <option value="{{ $currSubj->curr_subj_ID }}" data-curriculum="{{ $currSubj->curriculum_ID }}" data-grade="{{ $currSubj->grade_level }}" data-cluster="{{ $currSubj->cluster_ID }}" @selected((string) old('curr_subj_ID') === (string) $currSubj->curr_subj_ID)>
                                {{ optional($currSubj->subject)->code }} — {{ optional($currSubj->subject)->title }}{{ $currSubj->semester ? ' · '.ucfirst($currSubj->semester).' Sem' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Subjects filter automatically based on the selected section.</p>
                    @error('curr_subj_ID')
                        @if (old('_form') === 'assignment')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="assignment_staff_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Teacher <span class="text-red-500">*</span></label>
                    <select id="assignment_staff_id" name="staff_ID" required class="{{ $fieldClass }} {{ $errors->has('staff_ID') && old('_form') === 'assignment' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select teacher</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}" @selected((string) old('staff_ID') === (string) $teacher->staff_id)>
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_ID')
                        @if (old('_form') === 'assignment')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeAssignmentModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<div id="bulkModal" role="dialog" aria-modal="true" aria-labelledby="bulkModalTitle" data-open="{{ $bulkModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $bulkModalOpen ? 'flex' : 'hidden' }} items-start justify-center overflow-y-auto bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto mb-8 w-full max-w-5xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="bulkModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Bulk Assign</h3>
                </div>
                <button type="button" onclick="closeBulkModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="bulk-assignment-form" action="{{ route('admin.teacher-assignments.bulk') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="bulk">
            <div class="space-y-5 px-6 py-5">
                <div>
                    <label for="bulk_staff_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Teacher <span class="text-red-500">*</span></label>
                    <select id="bulk_staff_id" name="staff_ID" required class="{{ $fieldClass }} {{ $errors->has('staff_ID') && old('_form') === 'bulk' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select teacher</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}" @selected((string) old('staff_ID') === (string) $teacher->staff_id)>
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_ID')
                        @if (old('_form') === 'bulk')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600">Grade Levels</label>
                        <span id="bulk-grade-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                        @foreach ($gradeLevels as $level)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                                <input type="checkbox" name="grade_levels[]" value="{{ $level['value'] }}" class="bulk-grade-checkbox h-4 w-4 accent-[#296374]" @checked(in_array($level['value'], old('grade_levels', []), true))>
                                <span class="font-medium">{{ $level['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('grade_levels')
                        @if (old('_form') === 'bulk')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600">Sections</label>
                        <span id="bulk-section-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div id="bulk-sections-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                        Select one or more grade levels to show matching sections.
                    </div>
                    <div id="bulk-sections-list" class="hidden grid max-h-72 grid-cols-1 gap-2 overflow-y-auto pr-1 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($sections as $section)
                            <label class="bulk-section-option hidden items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700"
                                data-grade="{{ $section->grade_level }}"
                                data-curriculum="{{ $section->curriculum_ID }}"
                                data-cluster="{{ $section->cluster_ID ?? '' }}">
                                <input type="checkbox" name="section_ids[]" value="{{ $section->section_ID }}"
                                    class="bulk-section-checkbox mt-0.5 h-4 w-4 accent-[#296374]"
                                    data-grade="{{ $section->grade_level }}"
                                    data-curriculum="{{ $section->curriculum_ID }}"
                                    data-cluster="{{ $section->cluster_ID ?? '' }}"
                                    @checked(in_array((string) $section->section_ID, array_map('strval', old('section_ids', [])), true))>
                                <span>
                                    <span class="block text-[13px] font-semibold leading-tight">{{ $section->name }}</span>
                                    <span class="mt-1 block text-[11px] leading-tight text-gray-500">
                                        {{ optional($section->gradeLevel)->grade_label ?? '—' }}
                                        @if ($section->cluster)
                                            · {{ $section->cluster->name }}
                                        @endif
                                    </span>
                                    <span class="block text-[11px] leading-tight text-gray-500">{{ $section->academicYear?->school_year ?? '—' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('section_ids')
                        @if (old('_form') === 'bulk')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600">Subjects</label>
                        <span id="bulk-subject-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div id="bulk-subjects-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                        Select one or more sections to show compatible subjects.
                    </div>
                    <div id="bulk-subjects-list" class="hidden grid max-h-80 grid-cols-1 gap-2 overflow-y-auto pr-1 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($curriculumSubjects as $currSubj)
                            <label class="bulk-subject-option hidden items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700"
                                data-grade="{{ $currSubj->grade_level }}"
                                data-curriculum="{{ $currSubj->curriculum_ID }}"
                                data-cluster="{{ $currSubj->cluster_ID ?? '' }}">
                                <input type="checkbox" name="curr_subj_ids[]" value="{{ $currSubj->curr_subj_ID }}"
                                    class="bulk-subject-checkbox mt-0.5 h-4 w-4 accent-[#296374]"
                                    data-grade="{{ $currSubj->grade_level }}"
                                    data-curriculum="{{ $currSubj->curriculum_ID }}"
                                    data-cluster="{{ $currSubj->cluster_ID ?? '' }}"
                                    @checked(in_array((string) $currSubj->curr_subj_ID, array_map('strval', old('curr_subj_ids', [])), true))>
                                <span>
                                    <span class="block text-[13px] font-semibold leading-tight">{{ optional($currSubj->subject)->code }} — {{ optional($currSubj->subject)->title }}</span>
                                    <span class="mt-1 block text-[11px] leading-tight text-gray-500">
                                        {{ str_replace('grade_', 'Grade ', $currSubj->grade_level) }}
                                        @if ($currSubj->semester)
                                            · {{ ucfirst($currSubj->semester) }} Semester
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('curr_subj_ids')
                        @if (old('_form') === 'bulk')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeBulkModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Confirm Bulk Assignment</button>
            </div>
        </form>
    </div>
</div>

<div id="reassignModal" role="dialog" aria-modal="true" aria-labelledby="reassignModalTitle" data-open="{{ $reassignModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $reassignModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="reassignModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Change Teacher</h3>
                </div>
                <button type="button" onclick="closeReassignModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="reassignForm" action="{{ $reassignModalOpen && old('assignment_ID') ? route('admin.teacher-assignments.update', ['assignment' => old('assignment_ID')]) : '#' }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="reassign">
            <input type="hidden" id="reassign_assignment_id" name="assignment_ID" value="{{ old('assignment_ID') }}">
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="reassign_staff_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Teacher <span class="text-red-500">*</span></label>
                    <select id="reassign_staff_id" name="staff_ID" required class="{{ $fieldClass }} {{ $errors->has('staff_ID') && old('_form') === 'reassign' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select teacher</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}" @selected((string) old('staff_ID') === (string) $teacher->staff_id)>
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_ID')
                        @if (old('_form') === 'reassign')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeReassignModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Update Teacher</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setModalOpen(modal, isOpen) {
        modal.classList.toggle('hidden', !isOpen);
        modal.classList.toggle('flex', isOpen);
        modal.setAttribute('data-open', isOpen ? 'true' : 'false');
        document.body.classList.toggle('overflow-hidden', isOpen);
    }

    function openAssignAdvisoryModal() {
        setModalOpen(document.getElementById('assignAdvisoryModal'), true);
        refreshAdvisoryForm();
        document.getElementById('advisory_grade_selector').focus();
    }

    function closeAssignAdvisoryModal() {
        setModalOpen(document.getElementById('assignAdvisoryModal'), false);
    }

    function openAdvisoryModal(sectionId, sectionName, gradeLevel) {
        const form = document.getElementById('advisoryEditForm');
        const updateRouteTemplate = '{{ route('admin.teacher-assignments.advisory.update', ['section' => '__SECTION__']) }}';
        form.action = updateRouteTemplate.replace('__SECTION__', sectionId);
        document.getElementById('edit_advisory_section_id').value = sectionId;
        document.getElementById('edit_advisory_current_section').textContent = sectionName;
        document.getElementById('edit_advisory_grade_selector').value = gradeLevel || '';
        const targetSelect = document.getElementById('edit_advisory_target_section_id');
        targetSelect.value = sectionId;
        filterSelectOptions(targetSelect, document.getElementById('edit_advisory_section_search').value || '', function (option) { return option.value === String(sectionId) || ((option.dataset.assigned !== 'true') && option.dataset.grade === gradeLevel); });
        if (targetSelect.refreshSearchableDropdown) targetSelect.refreshSearchableDropdown();
        setModalOpen(document.getElementById('advisoryModal'), true);
        document.getElementById('edit_advisory_section_search').focus();
    }

    function closeAdvisoryModal() {
        setModalOpen(document.getElementById('advisoryModal'), false);
    }

    function openAssignmentModal() {
        setModalOpen(document.getElementById('assignmentModal'), true);
        filterSubjects();
        document.getElementById('section_ID').focus();
    }

    function closeAssignmentModal() {
        setModalOpen(document.getElementById('assignmentModal'), false);
    }

    function openBulkModal() {
        setModalOpen(document.getElementById('bulkModal'), true);
        refreshBulkFilters();
        document.getElementById('bulk_staff_id').focus();
    }

    function closeBulkModal() {
        setModalOpen(document.getElementById('bulkModal'), false);
    }

    function openReassignModal(assignmentId, staffId) {
        const form = document.getElementById('reassignForm');
        const updateRouteTemplate = '{{ route('admin.teacher-assignments.update', ['assignment' => '__ASSIGNMENT__']) }}';
        form.action = updateRouteTemplate.replace('__ASSIGNMENT__', assignmentId);
        document.getElementById('reassign_assignment_id').value = assignmentId;
        document.getElementById('reassign_staff_id').value = staffId || '';
        setModalOpen(document.getElementById('reassignModal'), true);
        document.getElementById('reassign_staff_id').focus();
    }

    function closeReassignModal() {
        setModalOpen(document.getElementById('reassignModal'), false);
    }

    function filterSelectOptions(select, search, predicate) {
        const selectedValue = select.value;
        Array.from(select.options).forEach(function (option) {
            if (!option.value) return;
            const matches = option.text.toLowerCase().includes(search.toLowerCase()) && predicate(option);
            option.hidden = !matches;
            option.disabled = !matches;
        });
        if (selectedValue && select.options[select.selectedIndex] && select.options[select.selectedIndex].hidden) select.value = '';
    }

    function filterAdvisoryTeachers(select, search, schoolYearId, currentStaffId) {
        filterSelectOptions(select, search || '', function (option) {
            const assignedYears = (option.dataset.advisoryYears || '').split(',').filter(Boolean);
            return String(option.value) === String(currentStaffId || '') || !schoolYearId || assignedYears.indexOf(String(schoolYearId)) === -1;
        });
    }

    function makeSearchPartOfDropdown(searchInput, select, placeholder) {
        const dropdown = document.createElement('div');
        const trigger = document.createElement('button');
        const menu = document.createElement('div');
        const results = document.createElement('div');
        const refresh = function () {
            const selected = select.options[select.selectedIndex];
            trigger.innerHTML = '<span class="truncate">' + (selected && selected.value ? selected.text : placeholder) + '</span><svg class="ml-2 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"></path></svg>';
            results.innerHTML = '';
            Array.from(select.options).forEach(function (option) {
                if (option.hidden || option.disabled) return;
                const item = document.createElement('button');
                item.type = 'button'; item.textContent = option.text;
                item.className = 'block w-full rounded-md px-3 py-2.5 text-left text-sm text-slate-800 transition hover:bg-[#296374]/10';
                item.addEventListener('click', function () { select.value = option.value; select.dispatchEvent(new Event('change')); });
                results.appendChild(item);
            });
        };

        dropdown.className = 'relative';
        trigger.type = 'button';
        trigger.className = 'flex h-10 w-full items-center justify-between rounded-lg border border-gray-200 bg-white px-3 text-left text-sm text-gray-800 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
        menu.className = 'absolute z-30 mt-1 hidden w-full rounded-xl border border-slate-100 bg-white p-2 shadow-xl';
        searchInput.classList.remove('mb-2');
        searchInput.classList.add('mb-2');
        select.classList.add('hidden');
        searchInput.className = 'h-11 w-full rounded-lg border border-violet-100 bg-white px-3 text-sm text-slate-800 outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100';
        results.className = 'mt-2 max-h-48 overflow-y-auto rounded-lg bg-slate-50 p-1';

        searchInput.parentNode.insertBefore(dropdown, searchInput);
        dropdown.appendChild(trigger);
        dropdown.appendChild(menu);
        menu.appendChild(searchInput);
        menu.appendChild(select);
        menu.appendChild(results);

        trigger.addEventListener('click', function () {
            const open = menu.classList.toggle('hidden');
            if (!open) searchInput.focus();
        });
        select.addEventListener('change', function () { refresh(); menu.classList.add('hidden'); });
        searchInput.addEventListener('input', function () { window.setTimeout(refresh, 0); });
        document.addEventListener('click', function (event) { if (!dropdown.contains(event.target)) menu.classList.add('hidden'); });
        select.refreshSearchableDropdown = refresh;
        refresh();
    }

    const advisoryGradeSelector = document.getElementById('advisory_grade_selector');
    const advisorySectionSelect = document.getElementById('advisory_section_id');
    const advisorySectionSearch = document.getElementById('advisory_section_search');
    const advisoryStaffSelect = document.getElementById('advisory_staff_id');
    const advisoryStaffSearch = document.getElementById('advisory_staff_search');
    const editAdvisorySectionSelect = document.getElementById('edit_advisory_target_section_id');
    const editAdvisorySectionSearch = document.getElementById('edit_advisory_section_search');
    const editAdvisoryGradeSelector = document.getElementById('edit_advisory_grade_selector');

    makeSearchPartOfDropdown(advisorySectionSearch, advisorySectionSelect, 'Select section');
    makeSearchPartOfDropdown(advisoryStaffSearch, advisoryStaffSelect, 'Select teacher');
    makeSearchPartOfDropdown(editAdvisorySectionSearch, editAdvisorySectionSelect, 'Not assigned to any section');

    function refreshAdvisoryForm() {
        const grade = advisoryGradeSelector.value;
        filterSelectOptions(advisorySectionSelect, advisorySectionSearch.value || '', function (option) { return !grade || option.dataset.grade === grade; });
        const selectedSection = advisorySectionSelect.options[advisorySectionSelect.selectedIndex];
        filterAdvisoryTeachers(advisoryStaffSelect, advisoryStaffSearch.value || '', selectedSection ? selectedSection.dataset.year : '', '');
        if (advisorySectionSelect.refreshSearchableDropdown) advisorySectionSelect.refreshSearchableDropdown();
        if (advisoryStaffSelect.refreshSearchableDropdown) advisoryStaffSelect.refreshSearchableDropdown();
    }

    advisoryGradeSelector.addEventListener('change', refreshAdvisoryForm);
    advisorySectionSearch.addEventListener('input', refreshAdvisoryForm);
    advisorySectionSelect.addEventListener('change', refreshAdvisoryForm);
    advisoryStaffSearch.addEventListener('input', refreshAdvisoryForm);
    editAdvisorySectionSearch.addEventListener('input', function () {
        const currentSectionId = document.getElementById('edit_advisory_section_id').value;
        const grade = editAdvisoryGradeSelector.value;
        filterSelectOptions(editAdvisorySectionSelect, editAdvisorySectionSearch.value, function (option) { return option.value === currentSectionId || ((option.dataset.assigned !== 'true') && option.dataset.grade === grade); });
        if (editAdvisorySectionSelect.refreshSearchableDropdown) editAdvisorySectionSelect.refreshSearchableDropdown();
    });
    editAdvisoryGradeSelector.addEventListener('change', function () { editAdvisorySectionSearch.dispatchEvent(new Event('input')); });
    refreshAdvisoryForm();

    document.querySelectorAll('.assignment-tab').forEach(function (button) {
        button.addEventListener('click', function () {
            const tab = button.dataset.assignmentTab;
            document.querySelectorAll('[data-assignment-panel]').forEach(function (panel) { panel.classList.toggle('hidden', panel.dataset.assignmentPanel !== tab); });
            document.querySelectorAll('.assignment-tab').forEach(function (item) {
                const active = item.dataset.assignmentTab === tab;
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                item.classList.toggle('bg-[#296374]', active); item.classList.toggle('text-white', active);
                item.classList.toggle('bg-white', !active); item.classList.toggle('text-[#296374]', !active);
            });
            document.querySelectorAll('.subject-header-action').forEach(function (action) { action.classList.toggle('hidden', tab !== 'subjects'); action.classList.toggle('inline-flex', tab === 'subjects'); });
            const url = new URL(window.location.href);
            if (tab === 'subjects') url.searchParams.set('tab', 'subjects'); else url.searchParams.delete('tab');
            history.replaceState(null, '', url);
        });
    });

    ['assignAdvisoryModal', 'advisoryModal', 'assignmentModal', 'bulkModal', 'reassignModal'].forEach(function (id) {
        const modal = document.getElementById(id);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                setModalOpen(modal, false);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }

        ['assignAdvisoryModal', 'advisoryModal', 'assignmentModal', 'bulkModal', 'reassignModal'].forEach(function (id) {
            const modal = document.getElementById(id);
            if (modal.getAttribute('data-open') === 'true') {
                setModalOpen(modal, false);
            }
        });
    });

    var sectionSelect = document.getElementById('section_ID');
    var subjectSelect = document.getElementById('curr_subj_ID');

    function filterSubjects() {
        if (!sectionSelect || !subjectSelect) {
            return;
        }

        var selected = sectionSelect.options[sectionSelect.selectedIndex];
        var curriculumId = selected ? selected.getAttribute('data-curriculum') : '';
        var gradeLevel = selected ? selected.getAttribute('data-grade') : '';
        var clusterId = selected ? selected.getAttribute('data-cluster') : '';
        var currentValue = subjectSelect.value;

        Array.from(subjectSelect.options).forEach(function (opt) {
            if (opt.value === '') {
                opt.hidden = false;
                return;
            }

            var matchesCurriculum = opt.getAttribute('data-curriculum') === curriculumId;
            var matchesGrade = opt.getAttribute('data-grade') === gradeLevel;
            var matchesCluster = true;
            if (clusterId && clusterId !== '') {
                matchesCluster = opt.getAttribute('data-cluster') === clusterId;
            }
            opt.hidden = !(matchesCurriculum && matchesGrade && matchesCluster);
        });

        var currentOption = Array.from(subjectSelect.options).find(function (opt) {
            return opt.value === currentValue;
        });
        if (!currentOption || currentOption.hidden) {
            subjectSelect.value = '';
        }
    }

    if (sectionSelect) {
        sectionSelect.addEventListener('change', filterSubjects);
        filterSubjects();
    }

    var bulkGradeCheckboxes = Array.from(document.querySelectorAll('.bulk-grade-checkbox'));
    var bulkSectionOptions = Array.from(document.querySelectorAll('.bulk-section-option'));
    var bulkSectionCheckboxes = Array.from(document.querySelectorAll('.bulk-section-checkbox'));
    var bulkSubjectOptions = Array.from(document.querySelectorAll('.bulk-subject-option'));
    var bulkSubjectCheckboxes = Array.from(document.querySelectorAll('.bulk-subject-checkbox'));
    var bulkSectionsEmpty = document.getElementById('bulk-sections-empty');
    var bulkSectionsList = document.getElementById('bulk-sections-list');
    var bulkSubjectsEmpty = document.getElementById('bulk-subjects-empty');
    var bulkSubjectsList = document.getElementById('bulk-subjects-list');
    var bulkGradeCount = document.getElementById('bulk-grade-count');
    var bulkSectionCount = document.getElementById('bulk-section-count');
    var bulkSubjectCount = document.getElementById('bulk-subject-count');

    function updateBulkCounts() {
        if (bulkGradeCount) {
            bulkGradeCount.textContent = bulkGradeCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
        }
        if (bulkSectionCount) {
            bulkSectionCount.textContent = bulkSectionCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
        }
        if (bulkSubjectCount) {
            bulkSubjectCount.textContent = bulkSubjectCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
        }
    }

    function selectedSectionMeta() {
        return bulkSectionCheckboxes
            .filter(function (input) {
                return input.checked && !input.closest('.bulk-section-option').classList.contains('hidden');
            })
            .map(function (input) {
                return {
                    grade: input.getAttribute('data-grade') || '',
                    curriculum: input.getAttribute('data-curriculum') || '',
                    cluster: input.getAttribute('data-cluster') || ''
                };
            });
    }

    function subjectMatchesAnySection(subjectOption, sections) {
        var subjectGrade = subjectOption.getAttribute('data-grade') || '';
        var subjectCurriculum = subjectOption.getAttribute('data-curriculum') || '';
        var subjectCluster = subjectOption.getAttribute('data-cluster') || '';

        return sections.some(function (section) {
            var matchesGrade = section.grade === subjectGrade;
            var matchesCurriculum = section.curriculum === subjectCurriculum;
            var matchesCluster = true;

            if (section.cluster) {
                matchesCluster = section.cluster === subjectCluster;
            }

            return matchesGrade && matchesCurriculum && matchesCluster;
        });
    }

    function updateBulkSections() {
        var selectedGrades = bulkGradeCheckboxes
            .filter(function (input) { return input.checked; })
            .map(function (input) { return input.value; });

        var visibleSections = 0;
        bulkSectionOptions.forEach(function (option) {
            var shouldShow = selectedGrades.indexOf(option.getAttribute('data-grade')) !== -1;
            option.classList.toggle('hidden', !shouldShow);
            option.classList.toggle('flex', shouldShow);

            var input = option.querySelector('.bulk-section-checkbox');
            if (!shouldShow && input) {
                input.checked = false;
            }

            if (shouldShow) {
                visibleSections++;
            }
        });

        if (bulkSectionsEmpty) {
            bulkSectionsEmpty.classList.toggle('hidden', visibleSections > 0);
        }
        if (bulkSectionsList) {
            bulkSectionsList.classList.toggle('hidden', visibleSections === 0);
        }
    }

    function updateBulkSubjects() {
        var sections = selectedSectionMeta();
        var visibleSubjects = 0;

        bulkSubjectOptions.forEach(function (option) {
            var shouldShow = sections.length > 0 && subjectMatchesAnySection(option, sections);
            option.classList.toggle('hidden', !shouldShow);
            option.classList.toggle('flex', shouldShow);

            var input = option.querySelector('.bulk-subject-checkbox');
            if (!shouldShow && input) {
                input.checked = false;
            }

            if (shouldShow) {
                visibleSubjects++;
            }
        });

        if (bulkSubjectsEmpty) {
            bulkSubjectsEmpty.classList.toggle('hidden', visibleSubjects > 0);
        }
        if (bulkSubjectsList) {
            bulkSubjectsList.classList.toggle('hidden', visibleSubjects === 0);
        }
    }

    function refreshBulkFilters() {
        updateBulkSections();
        updateBulkSubjects();
        updateBulkCounts();
    }

    bulkGradeCheckboxes.forEach(function (input) {
        input.addEventListener('change', refreshBulkFilters);
    });

    bulkSectionCheckboxes.forEach(function (input) {
        input.addEventListener('change', function () {
            updateBulkSubjects();
            updateBulkCounts();
        });
    });

    bulkSubjectCheckboxes.forEach(function (input) {
        input.addEventListener('change', updateBulkCounts);
    });

    refreshBulkFilters();
</script>
@endsection
