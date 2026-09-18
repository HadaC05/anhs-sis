@extends('users.admin.layout')

@section('title', 'Subjects')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $subjectModalOpen = old('_form') === 'subject' && $errors->any();
    $preferredCourseModalOpen = old('_form') === 'preferred_course' && $errors->any();
    $activeTab = $preferredCourseModalOpen
        ? 'preferred_courses'
        : ($subjectModalOpen || request('tab') !== 'preferred_courses' ? 'subjects' : 'preferred_courses');
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Subjects</h1>
        <p class="mt-1 text-sm text-gray-500">Manage the subject masterfile and preferred courses.</p>
    </div>
    <div>
        <button type="button" id="addSubjectHeaderBtn" onclick="openSubjectModal()"
            class="{{ $activeTab === 'subjects' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;">
            Add Subject
        </button>
        <button type="button" id="addPreferredCourseHeaderBtn" onclick="openPreferredCourseModal()"
            class="{{ $activeTab === 'preferred_courses' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
            style="background-color: #296374;">
            Add Preferred Course
        </button>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any() && ! in_array(old('_form'), ['subject', 'preferred_course'], true))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Subjects</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalSubjects) }}</p>
        <p class="mt-1 text-xs text-gray-500">All subjects in the masterfile</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Active</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($activeSubjectCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Available for curriculum assignment</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Archived</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($archivedSubjectCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Hidden from new assignments</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Preferred Courses</p>
        <p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($preferredCourseCount) }}</p>
        <p class="mt-1 text-xs text-gray-500">Tracks used during enrollment</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
        <nav class="flex gap-1" role="tablist" aria-label="Subject configuration tabs">
            <button type="button" id="subjectsTabBtn" onclick="switchSubjectConfigTab('subjects')" role="tab"
                aria-selected="{{ $activeTab === 'subjects' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'subjects' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Subjects
                <span id="subjectsTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'subjects' ? '' : 'hidden' }}"></span>
            </button>
            <button type="button" id="preferredCoursesTabBtn" onclick="switchSubjectConfigTab('preferred_courses')" role="tab"
                aria-selected="{{ $activeTab === 'preferred_courses' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-bold transition {{ $activeTab === 'preferred_courses' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Preferred Courses
                <span id="preferredCoursesTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'preferred_courses' ? '' : 'hidden' }}"></span>
            </button>
        </nav>
    </div>

    <div id="subjectsTabPanel" class="{{ $activeTab === 'subjects' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.subject-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="subjects">
                <input type="hidden" name="preferred_courses_search" value="{{ request('preferred_courses_search') }}">
                <input type="hidden" name="preferred_courses_cluster_ID" value="{{ request('preferred_courses_cluster_ID') }}">
                <input type="hidden" name="preferred_courses_per_page" value="{{ request('preferred_courses_per_page', $preferredCoursesPerPage ?? 10) }}">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search code or title"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="type" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All types</option>
                    @foreach ($subjectTypes as $subjectType)
                        <option value="{{ $subjectType->key }}" @selected(request('type') === $subjectType->key)>{{ $subjectType->label }}</option>
                    @endforeach
                </select>
                <select name="school_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All school levels</option>
                    <option value="Junior High School" @selected(request('school_level') === 'Junior High School')>Junior High School</option>
                    <option value="Senior High School" @selected(request('school_level') === 'Senior High School')>Senior High School</option>
                </select>
                <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
                <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) ($perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['search', 'type', 'school_level', 'status', 'per_page']))
                    <a href="{{ route('admin.subject-config.index', ['tab' => 'subjects']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Code</th>
                        <th class="border-r border-gray-200 px-5 py-4">Title</th>
                        <th class="border-r border-gray-200 px-5 py-4">Type</th>
                        <th class="border-r border-gray-200 px-5 py-4">School Level</th>
                        <th class="border-r border-gray-200 px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($subjects as $subject)
                        @php
                            $subjectPayload = [
                                'subject_ID' => $subject->subject_ID,
                                'code' => $subject->code,
                                'title' => $subject->title,
                                'type' => $subject->type,
                                'school_level' => $subject->school_level,
                            ];
                            $typeBadge = match ($subject->type) {
                                'core' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                                'applied' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                default => 'bg-violet-50 text-violet-700 ring-violet-200',
                            };
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $subject->code }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $subject->title }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide ring-1 {{ $typeBadge }}">
                                    {{ $subject->type }}
                                </span>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $subject->school_level }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $subject->status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                    {{ ucfirst($subject->status ?? 'active') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openSubjectModal(@json($subjectPayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.subject-config.delete', $subject) }}" method="POST" class="inline" onsubmit="return confirm('{{ $subject->status === 'active' ? 'Archive this subject?' : 'Restore this subject?' }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition {{ $subject->status === 'active' ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-600' }}" title="{{ $subject->status === 'active' ? 'Archive' : 'Restore' }}">
                                            @if ($subject->status === 'active')
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
                            <td colspan="6" class="px-6 py-16 text-center text-gray-500">No subjects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $subjects->appends(['tab' => 'subjects'])->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div id="preferredCoursesTabPanel" class="{{ $activeTab === 'preferred_courses' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route('admin.subject-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="preferred_courses">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="type" value="{{ request('type') }}">
                <input type="hidden" name="cluster_ID" value="{{ request('cluster_ID') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', $perPage ?? 15) }}">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="preferred_courses_search" value="{{ request('preferred_courses_search') }}" placeholder="Search course name"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="preferred_courses_cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($preferredClusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" @selected((int) request('preferred_courses_cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <select name="preferred_courses_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([5, 10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" {{ (int) ($preferredCoursesPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['preferred_courses_search', 'preferred_courses_cluster_ID', 'preferred_courses_per_page']))
                    <a href="{{ route('admin.subject-config.index', ['tab' => 'preferred_courses']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Course</th>
                        <th class="border-r border-gray-200 px-5 py-4">Academic Cluster</th>
                        <th class="border-r border-gray-200 px-5 py-4">Description</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($preferredCourses as $course)
                        @php
                            $coursePayload = [
                                'course_ID' => $course->course_ID,
                                'cluster_ID' => $course->cluster_ID,
                                'name' => $course->name,
                                'description' => $course->description,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $course->name }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($course->cluster)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $course->description ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openPreferredCourseModal(@json($coursePayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.subject-config.preferred-courses.delete', $course) }}" method="POST" class="inline" onsubmit="return confirm('Delete this preferred course?');">
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
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500">No preferred courses yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($preferredCourses->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $preferredCourses->appends(['tab' => 'preferred_courses'])->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<div id="subjectModal" role="dialog" aria-modal="true" aria-labelledby="subjectModalTitle" data-open="{{ $subjectModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $subjectModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="subjectModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Subject</h3>
                </div>
                <button type="button" onclick="closeSubjectModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="subjectForm" action="{{ route('admin.subject-config.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="subject">
            <input type="hidden" id="subject_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="subject_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Code <span class="text-red-500">*</span></label>
                        <input id="subject_code" name="code" type="text" value="{{ old('code') }}" required
                            class="{{ $fieldClass }} {{ $errors->has('code') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('code')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="subject_type" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Type <span class="text-red-500">*</span></label>
                        <select id="subject_type" name="type" required
                            class="{{ $fieldClass }} {{ $errors->has('type') ? 'border-red-300' : 'border-gray-200' }}">
                            <option value="">Select type</option>
                            @foreach ($subjectTypes as $subjectType)
                                <option value="{{ $subjectType->key }}" @selected(old('type') === $subjectType->key)>{{ $subjectType->label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label for="subject_title" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Title <span class="text-red-500">*</span></label>
                    <input id="subject_title" name="title" type="text" value="{{ old('title') }}" required
                        class="{{ $fieldClass }} {{ $errors->has('title') ? 'border-red-300' : 'border-gray-200' }}">
                    @error('title')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="subject_school_level" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">School Level <span class="text-red-500">*</span></label>
                    <select id="subject_school_level" name="school_level" required
                        class="{{ $fieldClass }} {{ $errors->has('school_level') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select school level</option>
                        <option value="Junior High School" @selected(old('school_level') === 'Junior High School')>Junior High School</option>
                        <option value="Senior High School" @selected(old('school_level') === 'Senior High School')>Senior High School</option>
                    </select>
                    @error('school_level')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeSubjectModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="subjectSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Subject
                </button>
            </div>
        </form>
    </div>
</div>

<div id="preferredCourseModal" role="dialog" aria-modal="true" aria-labelledby="preferredCourseModalTitle" data-open="{{ $preferredCourseModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $preferredCourseModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="preferredCourseModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Preferred Course</h3>
                </div>
                <button type="button" onclick="closePreferredCourseModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="preferredCourseForm" action="{{ route('admin.subject-config.preferred-courses.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="preferred_course">
            <input type="hidden" id="preferred_course_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="preferred_course_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Academic Cluster <span class="text-red-500">*</span></label>
                    <select id="preferred_course_cluster_id" name="cluster_ID" required
                        class="{{ $fieldClass }} {{ $errors->has('cluster_ID') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select cluster</option>
                        @foreach ($preferredClusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" @selected((string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                    @error('cluster_ID')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="preferred_course_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Course Name <span class="text-red-500">*</span></label>
                    <input id="preferred_course_name" name="name" type="text" value="{{ old('name') }}" required
                        class="{{ $fieldClass }} {{ $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}">
                    @error('name')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="preferred_course_description" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Description</label>
                    <textarea id="preferred_course_description" name="description" rows="3"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('description') ? 'border-red-300' : 'border-gray-200' }}">{{ old('description') }}</textarea>
                    @error('description')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closePreferredCourseModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="preferredCourseSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Preferred Course
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchSubjectConfigTab(tab) {
        const isSubjects = tab === 'subjects';
        const subjectsPanel = document.getElementById('subjectsTabPanel');
        const coursesPanel = document.getElementById('preferredCoursesTabPanel');
        const subjectsBtn = document.getElementById('subjectsTabBtn');
        const coursesBtn = document.getElementById('preferredCoursesTabBtn');
        const subjectsIndicator = document.getElementById('subjectsTabIndicator');
        const coursesIndicator = document.getElementById('preferredCoursesTabIndicator');
        const addSubjectBtn = document.getElementById('addSubjectHeaderBtn');
        const addCourseBtn = document.getElementById('addPreferredCourseHeaderBtn');

        subjectsPanel.classList.toggle('hidden', !isSubjects);
        coursesPanel.classList.toggle('hidden', isSubjects);

        subjectsBtn.classList.toggle('text-[#296374]', isSubjects);
        subjectsBtn.classList.toggle('text-gray-500', !isSubjects);
        subjectsBtn.classList.toggle('hover:text-gray-700', !isSubjects);
        coursesBtn.classList.toggle('text-[#296374]', !isSubjects);
        coursesBtn.classList.toggle('text-gray-500', isSubjects);
        coursesBtn.classList.toggle('hover:text-gray-700', isSubjects);
        subjectsBtn.setAttribute('aria-selected', isSubjects ? 'true' : 'false');
        coursesBtn.setAttribute('aria-selected', isSubjects ? 'false' : 'true');

        subjectsIndicator.classList.toggle('hidden', !isSubjects);
        coursesIndicator.classList.toggle('hidden', isSubjects);

        addSubjectBtn.classList.toggle('hidden', !isSubjects);
        addSubjectBtn.classList.toggle('inline-flex', isSubjects);
        addCourseBtn.classList.toggle('hidden', isSubjects);
        addCourseBtn.classList.toggle('inline-flex', !isSubjects);

        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    function openSubjectModal(subject = null) {
        const form = document.getElementById('subjectForm');
        const method = document.getElementById('subject_method');
        const title = document.getElementById('subjectModalTitle');
        const submit = document.getElementById('subjectSubmit');
        const modal = document.getElementById('subjectModal');
        const updateRouteTemplate = '{{ route('admin.subject-config.update', ['subject' => '__SUBJECT__']) }}';

        if (subject) {
            title.textContent = 'Edit Subject';
            submit.textContent = 'Update Subject';
            form.action = updateRouteTemplate.replace('__SUBJECT__', subject.subject_ID);
            method.value = 'PUT';
            document.getElementById('subject_code').value = subject.code || '';
            document.getElementById('subject_title').value = subject.title || '';
            document.getElementById('subject_type').value = subject.type || '';
            document.getElementById('subject_school_level').value = subject.school_level || '';
        } else {
            title.textContent = 'Add Subject';
            submit.textContent = 'Save Subject';
            form.action = '{{ route('admin.subject-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('subject_code').focus();
    }

    function closeSubjectModal() {
        const modal = document.getElementById('subjectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    function openPreferredCourseModal(course = null) {
        const form = document.getElementById('preferredCourseForm');
        const method = document.getElementById('preferred_course_method');
        const title = document.getElementById('preferredCourseModalTitle');
        const submit = document.getElementById('preferredCourseSubmit');
        const modal = document.getElementById('preferredCourseModal');
        const updateRouteTemplate = '{{ route('admin.subject-config.preferred-courses.update', ['preferredCourse' => '__COURSE__']) }}';

        if (course) {
            title.textContent = 'Edit Preferred Course';
            submit.textContent = 'Update Preferred Course';
            form.action = updateRouteTemplate.replace('__COURSE__', course.course_ID);
            method.value = 'PUT';
            document.getElementById('preferred_course_cluster_id').value = course.cluster_ID || '';
            document.getElementById('preferred_course_name').value = course.name || '';
            document.getElementById('preferred_course_description').value = course.description || '';
        } else {
            title.textContent = 'Add Preferred Course';
            submit.textContent = 'Save Preferred Course';
            form.action = '{{ route('admin.subject-config.preferred-courses.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('preferred_course_cluster_id').focus();
    }

    function closePreferredCourseModal() {
        const modal = document.getElementById('preferredCourseModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    document.getElementById('subjectModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeSubjectModal();
        }
    });

    document.getElementById('preferredCourseModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closePreferredCourseModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }

        if (document.getElementById('subjectModal').getAttribute('data-open') === 'true') {
            closeSubjectModal();
        }

        if (document.getElementById('preferredCourseModal').getAttribute('data-open') === 'true') {
            closePreferredCourseModal();
        }
    });
</script>
@endsection
