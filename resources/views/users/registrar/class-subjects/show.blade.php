@extends('users.registrar.layout')

@section('title', 'Class Subject Terms')

@section('content')
@php
    $subject = $assignment->curriculumSubject?->subject;
    $teacher = $assignment->staff;
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level ?? ''));
    $gradeInitial = strtoupper(str_replace('grade_', 'G', $section->grade_level ?? ''));
    $subjectCode = $subject?->code ?? 'SUBJ';
    $subjectTitle = $subject?->title ?? 'Subject';
    $subjectLabel = $subject ? ($subjectCode.' - '.$subjectTitle) : 'Subject';
    $teacherName = $teacher ? trim($teacher->last_name.', '.$teacher->first_name) : 'Unassigned';
    $semester = $assignment->curriculumSubject?->semester;
    $unlockableCount = collect($termSummaries)->filter(fn (array $term): bool => $term['can_unlock'] || $term['is_registrar_unlocked'])->count();
@endphp

<div class="mb-6">
    <a href="{{ route('registrar.class-subjects.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#296374] transition hover:underline">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        Back to Class Subjects
    </a>
</div>

<div class="mb-6 flex items-start gap-4">
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-lg" style="background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);">
        {{ $gradeInitial }}
    </span>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">{{ $section->name }}</h1>
            <span class="hidden text-xl font-light text-gray-300 sm:inline" aria-hidden="true">·</span>
            <span class="text-base font-semibold text-[#296374] md:text-lg">{{ $subjectLabel }}</span>
            @if ($unlockableCount > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-white">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                    Term unlock available
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-0.5 text-[11px] font-bold text-white">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    All terms open
                </span>
            @endif
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
            <span>{{ $gradeLabel }}</span>
            @if ($section->cluster?->name)
                <span>{{ $section->cluster->name }}</span>
            @endif
            @if ($section->room)
                <span>Room {{ $section->room }}</span>
            @endif
            <span>{{ $semester ? ucfirst($semester).' semester' : 'Full year' }}</span>
            @if ($section->academicYear?->school_year)
                <span>{{ $section->academicYear->school_year }}</span>
            @endif
            <span class="text-gray-300">|</span>
            <span>Teacher: {{ $teacherName }}</span>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-4 lg:px-6">
        <p class="text-sm text-gray-500">
            Unlock a term below to allow the teacher to edit and resubmit grades for <strong class="text-[#296374]">this class subject only</strong> when higher approval allows corrections.
        </p>
    </div>

    <div class="divide-y divide-gray-100">
        @forelse($termSummaries as $term)
            <div class="px-4 py-4 lg:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-800">{{ $term['label'] }}</h2>
                            @if($term['is_current_term'])
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">Current school term</span>
                            @endif
                            @if($term['is_registrar_unlocked'])
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase text-sky-700">Registrar unlocked</span>
                            @endif
                            @if($term['is_school_locked'] && ! $term['is_registrar_unlocked'])
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700">School term closed</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($term['submitted'] > 0)
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase text-violet-700">{{ $term['submitted'] }} submitted</span>
                            @endif
                            @if($term['approved'] > 0)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">{{ $term['approved'] }} approved</span>
                            @endif
                            @if($term['released'] > 0)
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">{{ $term['released'] }} released</span>
                            @endif
                            @if($term['total'] === 0)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase text-gray-500">No grades yet</span>
                            @endif
                        </div>

                        <p class="mt-2 text-xs text-gray-500">{{ $term['total'] }} total records · {{ $term['draft'] }} draft</p>
                    </div>

                    <div class="shrink-0">
                        @if($term['can_unlock'] || $term['is_registrar_unlocked'])
                            <form action="{{ route('registrar.class-subjects.unlock-term', $assignment) }}" method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-center" onsubmit="return confirm('Unlock {{ $term['label'] }} for this class subject? Locked grades will return to draft for teacher editing.');">
                                @csrf
                                <input type="hidden" name="grading_period" value="{{ $term['key'] }}">
                                <input type="text" name="notes" maxlength="500" placeholder="Approval note (optional)" class="h-9 w-full rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10 sm:w-44">
                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-amber-500 px-4 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-amber-600">
                                    {{ $term['is_registrar_unlocked'] ? 'Re-unlock' : 'Unlock Term' }}
                                </button>
                            </form>
                        @else
                            <span class="inline-flex h-9 items-center text-xs font-medium text-gray-400">Open for editing</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <p class="mt-4 text-sm font-medium text-gray-600">No grading terms are open for the current school year.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
