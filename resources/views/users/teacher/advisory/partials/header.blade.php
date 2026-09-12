@php
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
    $schoolYear = $section->academicYear?->school_year ?? 'N/A';
    $activeLearnerCount = $section->active_enrollments_count
        ?? \App\Models\Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', \App\Models\EnrollmentStatus::activeIds())
            ->count();
@endphp

<section class="mb-6 overflow-hidden rounded-md border border-slate-300 bg-white shadow-sm">
    <div class="flex flex-col divide-y divide-slate-200 sm:flex-row sm:divide-x sm:divide-y-0">
        <div class="min-w-0 px-5 py-4 sm:flex-1"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Advisory Class</p><h1 class="mt-1 truncate text-xl font-semibold tracking-tight text-slate-900">{{ $section->name }}</h1></div>
        <div class="min-w-0 px-5 py-4 sm:flex-1"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Grade Level</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $gradeLabel }}</p></div>
        <div class="min-w-0 px-5 py-4 sm:flex-1"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">School Year</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $schoolYear }}</p></div>
        <div class="min-w-0 px-5 py-4 sm:flex-1"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Students</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $activeLearnerCount }}</p></div>
    </div>
</section>

<div class="mb-6 grid grid-cols-2 overflow-hidden rounded-lg border border-[#296374]/40 bg-white shadow-sm sm:grid-cols-5">
    <a href="{{ route('teacher.advisory.class-list.index', $section) }}"
        class="flex min-w-0 items-center justify-center gap-2 border-b border-r border-[#296374]/30 px-3 py-3 text-center text-sm font-semibold transition sm:border-b-0 {{ ($active ?? 'grades') === 'class-list' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0z"></path></svg>
        Class List
    </a>
    <a href="{{ route('teacher.advisory.show', $section) }}"
        class="flex min-w-0 items-center justify-center gap-2 border-b border-r border-[#296374]/30 px-3 py-3 text-center text-sm font-semibold transition sm:border-b-0 {{ ($active ?? 'grades') === 'grades' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
        Student Grades
    </a>
    <a href="{{ route('teacher.advisory.observed-values', $section) }}"
        class="flex min-w-0 items-center justify-center gap-2 border-b border-r border-[#296374]/30 px-3 py-3 text-center text-sm font-semibold transition sm:border-b-0 {{ ($active ?? 'grades') === 'observed-values' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
        Observed Values
    </a>
    <a href="{{ route('teacher.advisory.attendance', $section) }}"
        class="flex min-w-0 items-center justify-center gap-2 border-b border-r border-[#296374]/30 px-3 py-3 text-center text-sm font-semibold transition sm:border-b-0 {{ ($active ?? 'grades') === 'attendance' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        Attendance Record
    </a>
    <a href="{{ route('teacher.advisory.promotions.index', $section) }}"
        class="col-span-2 flex min-w-0 items-center justify-center gap-2 px-3 py-3 text-center text-sm font-semibold transition sm:col-span-1 {{ ($active ?? 'grades') === 'promotions' ? 'bg-[#296374] text-white' : 'bg-white text-[#296374] hover:bg-[#296374]/10' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h6m0 0v6m0-6-8 8-4-4-4 4"></path></svg>
        Promotion
    </a>
</div>
