@extends('users.registrar.layout')

@section('title', 'Class Subjects')

@section('content')
<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Class Subjects</h1>
        </div>
    </div>
</div>

@if (session('status'))
<div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
    {{ session('status') }}
</div>
@endif

@php
$sectionList = $sections instanceof \Illuminate\Pagination\LengthAwarePaginator ? $sections->getCollection() : $sections;
@endphp

<div class="mb-6">
    <form method="GET" action="{{ route('registrar.class-subjects.index') }}">
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
            <input
                type="text"
                id="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Section, subject, or teacher"
                class="h-10 w-44 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">

            <select name="grade_level" id="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All levels</option>
                @foreach ($gradeLevels ?? [] as $level)
                <option value="{{ $level['value'] }}" {{ ($filters['grade_level'] ?? '') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                @endforeach
            </select>

            <select name="cluster_ID" id="cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All clusters</option>
                @foreach ($clusters ?? [] as $cluster)
                <option value="{{ $cluster->cluster_ID }}" {{ ($filters['cluster_ID'] ?? '') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                @endforeach
            </select>

            <select name="SY_ID" id="SY_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All school years</option>
                @foreach ($academicYears ?? [] as $year)
                <option value="{{ $year->SY_ID }}" {{ ($filters['SY_ID'] ?? '') == $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                @endforeach
            </select>

            <select name="per_page" id="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([10, 20, 50] as $size)
                <option value="{{ $size }}" {{ ($filters['per_page'] ?? 10) == $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>

            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"></path>
                </svg>
                Apply
            </button>
            <a href="{{ route('registrar.class-subjects.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

@if ($sectionList->isNotEmpty())
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($sectionList as $section)
    @php
    $assignments = $section->teacherSubjectAssignments ?? collect();
    $cap = (int) ($section->capacity ?? 0);
    $count = (int) ($section->active_enrollments_count ?? 0);
    $pct = $cap > 0 ? min(100, (int) round(100 * $count / $cap)) : 0;
    $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
    $badgeColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
    $statusLabel = $pct >= 100 ? 'full' : ($pct >= 80 ? 'near full' : 'open');
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
    $gradeInitial = strtoupper(str_replace('grade_', 'G', $section->grade_level));
    $schoolYear = $section->academicYear?->school_year ?? 'N/A';
    $adviserName = $section->adviser ? trim($section->adviser->last_name.', '.$section->adviser->first_name) : null;
    @endphp

    <div class="relative flex min-h-[190px] flex-col rounded-lg border border-gray-200/80 bg-white p-5 shadow-md shadow-slate-200/70">
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

        <div class="mt-5 space-y-2 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="truncate">{{ $schoolYear }}</span>
            </div>
            @if ($section->cluster?->name)
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                </svg>
                <span class="truncate">{{ $section->cluster->name }}</span>
            </div>
            @endif
            @if ($adviserName)
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="truncate">Adviser: {{ $adviserName }}</span>
            </div>
            @endif
            @if ($section->room)
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path>
                </svg>
                <span class="truncate font-mono">{{ $section->room }}</span>
            </div>
            @endif
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0 018 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-semibold text-gray-700">{{ $count }}</span>
                <span>of {{ $cap ?: 'unlimited' }} students</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span>{{ $assignments->count() }} {{ Str::plural('subject', $assignments->count()) }} assigned</span>
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-1 flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-gray-500">
                <span>Capacity</span>
                <span>{{ $pct }}%</span>
            </div>
            <div class="h-2 rounded-full bg-gray-200">
                <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%;"></div>
            </div>
        </div>

        <details class="group mt-5 border-t border-gray-100 pt-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2.5 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 [&::-webkit-details-marker]:hidden">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    Subjects
                    <span class="ml-1 normal-case tracking-normal text-gray-500">({{ $assignments->count() }})</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </summary>

            <div class="mt-3 space-y-2">
                @forelse ($assignments as $assignment)
                @php
                $subject = $assignment->curriculumSubject?->subject;
                $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
                $semester = $assignment->curriculumSubject?->semester;
                $teacher = $assignment->staff;
                $teacherName = $teacher ? trim($teacher->last_name . ', ' . $teacher->first_name) : 'Unassigned';
                @endphp
                <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-100 bg-white px-3 py-2">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-gray-800">{{ $subjectLabel }}</p>
                        <p class="text-[10px] text-gray-500">{{ $semester ? ucfirst($semester) . ' semester' : 'Full year' }} · {{ $teacherName }}</p>
                    </div>
                    <a href="{{ route('registrar.class-subjects.show', $assignment) }}" class="inline-flex shrink-0 items-center rounded-lg px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                        Terms
                    </a>
                </div>
                @empty
                <p class="text-xs text-gray-500">No subjects assigned for this section.</p>
                @endforelse
            </div>
        </details>
    </div>
    @endforeach
</div>

@if ($sections instanceof \Illuminate\Pagination\LengthAwarePaginator && $sections->hasPages())
<div class="mt-6 rounded-lg border border-gray-200/80 bg-white/90 px-6 py-4 shadow-sm">
    {{ $sections->links() }}
</div>
@endif
@else
<div class="rounded-lg border border-gray-200/80 bg-white/95 px-6 py-16 text-center shadow-md">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
    </div>
    <p class="mt-4 font-medium text-gray-600">No classes found</p>
    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back once sections are set up for the school year.</p>
</div>
@endif
@endsection
