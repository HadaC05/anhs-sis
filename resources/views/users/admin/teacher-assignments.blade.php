@extends('users.admin.layout')

@section('title', 'Teacher Assignments')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $advisoryModalOpen = old('_form') === 'advisory' && $errors->any();
    $assignmentModalOpen = old('_form') === 'assignment' && $errors->any();
    $bulkModalOpen = old('_form') === 'bulk' && $errors->any();
    $reassignModalOpen = old('_form') === 'reassign' && $errors->any();
    $activeTab = $assignmentModalOpen || $bulkModalOpen || $reassignModalOpen || request('tab') === 'subjects'
        ? 'subjects'
        : 'advisory';
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Teacher Assignments</h1>
        <p class="mt-1 text-sm text-gray-500">Assign teachers to advisory sections and subjects.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" onclick="openAdvisoryModal()"
            class="{{ $activeTab === 'advisory' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;" id="assignAdviserHeaderBtn">
            Assign Adviser
        </button>
        <button type="button" onclick="openAssignmentModal()"
            class="{{ $activeTab === 'subjects' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;" id="newAssignmentHeaderBtn">
            New Assignment
        </button>
        <button type="button" onclick="openBulkModal()"
            class="{{ $activeTab === 'subjects' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:border-[#296374]/30 hover:text-[#296374]"
            id="bulkAssignHeaderBtn">
            Bulk Assign
        </button>
    </div>
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

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="border-b border-gray-100 px-4 pt-2">
        <nav class="flex gap-1" aria-label="Teacher assignment tabs">
            <a href="{{ route('admin.teacher-assignments.index', array_merge(request()->except(['tab', 'advisory_page', 'subject_page']), ['tab' => 'advisory'])) }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'advisory' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Advisory
                @if ($activeTab === 'advisory')
                    <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374]"></span>
                @endif
            </a>
            <a href="{{ route('admin.teacher-assignments.index', array_merge(request()->except(['tab', 'advisory_page', 'subject_page']), ['tab' => 'subjects'])) }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'subjects' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Subjects
                @if ($activeTab === 'subjects')
                    <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374]"></span>
                @endif
            </a>
        </nav>
    </div>

    @if ($activeTab === 'advisory')
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.teacher-assignments.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="advisory">
                <select name="advisory_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) ($advisoryPerPage ?? 10) === $size)>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Section</th>
                        <th class="border-r border-gray-200 px-5 py-4">Adviser</th>
                        <th class="border-r border-gray-200 px-5 py-4">School Year</th>
                        <th class="border-r border-gray-200 px-5 py-4">Students</th>
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
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $section->academicYear?->school_year ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ number_format($section->active_enrollments_count ?? 0) }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end">
                                    <form action="{{ route('admin.teacher-assignments.advisory.remove', $section) }}" method="POST" onsubmit="return confirm('Remove this advisory assignment?');">
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
                            <td colspan="5" class="px-6 py-16 text-center text-gray-500">No advisory sections assigned yet.</td>
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
    @endif

    @if ($activeTab === 'subjects')
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
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Section</th>
                        <th class="border-r border-gray-200 px-5 py-4">Subject</th>
                        <th class="border-r border-gray-200 px-5 py-4">Teacher</th>
                        <th class="border-r border-gray-200 px-5 py-4">Semester</th>
                        <th class="border-r border-gray-200 px-5 py-4">School Year</th>
                        <th class="border-r border-gray-200 px-5 py-4">Locked Grades</th>
                        <th class="px-5 py-4 text-right">Actions</th>
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

        @if ($assignments->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $assignments->appends(['tab' => 'subjects'])->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>

<div id="advisoryModal" role="dialog" aria-modal="true" aria-labelledby="advisoryModalTitle" data-open="{{ $advisoryModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $advisoryModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="advisoryModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Assign Adviser</h3>
                </div>
                <button type="button" onclick="closeAdvisoryModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form action="{{ route('admin.teacher-assignments.advisory.assign') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="advisory">
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="advisory_section_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Section <span class="text-red-500">*</span></label>
                    <select id="advisory_section_id" name="section_ID" required class="{{ $fieldClass }} {{ $errors->has('section_ID') && old('_form') === 'advisory' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->section_ID }}" @selected((string) old('section_ID') === (string) $section->section_ID)>
                                {{ $section->name }} | {{ optional($section->gradeLevel)->grade_label ?? '—' }} | {{ $section->academicYear?->school_year ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    @error('section_ID')
                        @if (old('_form') === 'advisory')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="advisory_staff_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Adviser <span class="text-red-500">*</span></label>
                    <select id="advisory_staff_id" name="staff_ID" required class="{{ $fieldClass }} {{ $errors->has('staff_ID') && old('_form') === 'advisory' ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select teacher</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}" @selected((string) old('staff_ID') === (string) $teacher->staff_id)>
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_ID')
                        @if (old('_form') === 'advisory')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">A teacher can only have one advisory section per school year.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeAdvisoryModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Assign Adviser</button>
            </div>
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

    function openAdvisoryModal() {
        setModalOpen(document.getElementById('advisoryModal'), true);
        document.getElementById('advisory_section_id').focus();
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

    ['advisoryModal', 'assignmentModal', 'bulkModal', 'reassignModal'].forEach(function (id) {
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

        ['advisoryModal', 'assignmentModal', 'bulkModal', 'reassignModal'].forEach(function (id) {
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
