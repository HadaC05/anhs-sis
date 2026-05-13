@extends('users.student.layout')

@section('title', 'Dashboard')

@section('content')
@php
    $studentName = optional($application)->last_name
        ? optional($application)->last_name . ', ' . optional($application)->first_name
        : Auth::user()->name;
    $firstName = optional($application)->first_name ?: \Illuminate\Support\Str::of(Auth::user()->name)->before(' ');
    $enrollmentStatus = $currentEnrollment?->enrollment_status
        ? strtoupper(str_replace('_', ' ', $currentEnrollment->enrollment_status))
        : 'NOT ENROLLED';
    $gradeLabel = $currentEnrollment?->grade_level
        ? strtoupper(str_replace('grade_', 'Grade ', $currentEnrollment->grade_level))
        : 'N/A';
    $sectionName = $currentEnrollment?->section?->name ?? 'Not Assigned';
    $clusterName = $currentEnrollment?->cluster?->name ?? 'N/A';
    $preferredCourseName = $currentEnrollment?->preferredCourse?->name ?? 'N/A';
    $semesterLabel = $currentEnrollment?->semester ? ucfirst($currentEnrollment->semester) : 'N/A';
    $schoolYear = $activeYear?->school_year ?? 'Not Set';
    $needsEnrollment = $currentEnrollment === null;

    $statusStyles = match ($currentEnrollment?->enrollment_status) {
        'enrolled' => [
            'badge' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'dot' => 'bg-emerald-500',
            'panel' => 'from-emerald-500/20 via-white/70 to-white/40',
        ],
        'pending' => [
            'badge' => 'bg-amber-100 text-amber-700 ring-amber-200',
            'dot' => 'bg-amber-500',
            'panel' => 'from-amber-500/20 via-white/70 to-white/40',
        ],
        'rejected' => [
            'badge' => 'bg-rose-100 text-rose-700 ring-rose-200',
            'dot' => 'bg-rose-500',
            'panel' => 'from-rose-500/20 via-white/70 to-white/40',
        ],
        default => [
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'dot' => 'bg-slate-400',
            'panel' => 'from-slate-400/20 via-white/70 to-white/40',
        ],
    };

    $quickLinks = [
        [
            'label' => 'Enrollment',
            'description' => 'Submit or review your enrollment for this school year.',
            'href' => route('student.enrollment'),
            'tone' => 'bg-[#296374] text-white shadow-lg shadow-[#296374]/20',
            'icon' => 'document',
        ],
        [
            'label' => 'My Information',
            'description' => 'View your student details and personal record.',
            'href' => route('student.profile'),
            'tone' => 'bg-white/90 text-slate-700 ring-1 ring-slate-200',
            'icon' => 'user',
        ],
        [
            'label' => 'Grades',
            'description' => 'Check your released grades.',
            'href' => route('student.grades'),
            'tone' => 'bg-white/90 text-slate-700 ring-1 ring-slate-200',
            'icon' => 'chart',
        ],
        [
            'label' => 'Documents',
            'description' => 'Open your uploaded documents.',
            'href' => route('student.documents'),
            'tone' => 'bg-white/90 text-slate-700 ring-1 ring-slate-200',
            'icon' => 'folder',
        ],
    ];
@endphp

<div class="space-y-6 md:space-y-8">
    <section class="relative overflow-hidden rounded-[2rem] border border-white/60 bg-white/82 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.45)] backdrop-blur-xl">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(41,99,116,0.22),_transparent_42%),radial-gradient(circle_at_bottom_right,_rgba(251,191,36,0.16),_transparent_28%)]"></div>
        <div class="absolute -top-20 right-0 h-56 w-56 rounded-full bg-[#296374]/10 blur-3xl"></div>
        <div class="relative grid gap-8 px-6 py-7 md:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)] md:px-8 md:py-9">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#296374] ring-1 ring-[#296374]/15 backdrop-blur-sm bg-white/75">
                        Student Portal
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusStyles['badge'] }}">
                        <span class="h-2.5 w-2.5 rounded-full {{ $statusStyles['dot'] }}"></span>
                        {{ $enrollmentStatus }}
                    </span>
                </div>

                <div class="max-w-2xl space-y-3">
                    <p class="text-sm font-medium uppercase tracking-[0.25em] text-slate-500">Agusan National High School</p>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-4xl">
                        Welcome back, {{ $firstName }}.
                    </h1>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white/70 px-4 py-4 ring-1 ring-slate-200/70 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Student Name</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 md:text-base">{{ $studentName }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/70 px-4 py-4 ring-1 ring-slate-200/70 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">LRN</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 md:text-base">{{ optional($student)->lrn ?? 'Not Set' }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/70 px-4 py-4 ring-1 ring-slate-200/70 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">School Year</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 md:text-base">{{ $schoolYear }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-gradient-to-br {{ $statusStyles['panel'] }} p-[1px] shadow-[0_25px_60px_-40px_rgba(15,23,42,0.22)]">
                <div class="h-full rounded-[1.7rem] bg-[linear-gradient(145deg,rgba(41,99,116,0.94),rgba(74,129,143,0.92))] px-5 py-6 text-white md:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">Current Enrollment</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight">{{ $gradeLabel !== 'N/A' ? $gradeLabel : 'No active grade yet' }}</h2>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-right">
                            <p class="text-[10px] uppercase tracking-[0.22em] text-white/60">Semester</p>
                            <p class="mt-1 text-sm font-semibold">{{ $semesterLabel }}</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                            <span class="text-sm text-white/75">Section</span>
                            <span class="text-sm font-semibold">{{ $sectionName }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                            <span class="text-sm text-white/75">Cluster</span>
                            <span class="text-sm font-semibold">{{ $clusterName }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                            <span class="text-sm text-white/75">Preferred Course</span>
                            <span class="text-sm font-semibold text-right">{{ $preferredCourseName }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                            <span class="text-sm text-white/75">School Year</span>
                            <span class="text-sm font-semibold">{{ $schoolYear }}</span>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-white/15 bg-white/10 px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/60">Status Note</p>
                        <p class="mt-2 text-sm leading-6 text-white/85">
                            {{ $currentEnrollment
                                ? 'Your latest enrollment record is reflected here so you can quickly confirm your assigned details.'
                                : 'Once you submit enrollment for the active school year, your assigned details will appear here.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($needsEnrollment)
        <section class="rounded-[1.8rem] border border-amber-200/80 bg-[linear-gradient(135deg,rgba(255,251,235,0.96),rgba(255,255,255,0.92))] p-5 shadow-[0_18px_40px_-34px_rgba(245,158,11,0.55)] backdrop-blur-xl md:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M10.29 3.86l-7.4 12.82A1 1 0 003.75 18h16.5a1 1 0 00.86-1.5l-7.4-12.82a1 1 0 00-1.72 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-600">Enrollment Needed</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">You have not enrolled yet.</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            Submit your enrollment for the active school year so your section and academic details can appear here.
                        </p>
                    </div>
                </div>

                <a href="{{ route('student.enrollment') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#296374] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#296374]/20 transition hover:bg-[#214e5c]">
                    <span>Go to Enrollment</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h4m0 0v4m0-4L10 14"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5h6v6H5zM5 13h6v6H5zM13 13h6v6h-6z"></path>
                    </svg>
                </a>
            </div>
        </section>
    @endif

    <section>
        <div class="rounded-[1.8rem] border border-white/60 bg-white/86 p-6 shadow-[0_20px_60px_-40px_rgba(15,23,42,0.45)] backdrop-blur-xl md:p-7">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Quick Access</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Common student tasks</h2>
            </div>

            <div class="mt-6 space-y-3">
                @foreach ($quickLinks as $link)
                    <a href="{{ $link['href'] }}" class="group flex items-center justify-between gap-4 rounded-[1.4rem] px-4 py-4 transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_20px_30px_-28px_rgba(15,23,42,0.5)] {{ $link['tone'] }}">
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ str_contains($link['tone'], 'text-white') ? 'bg-white/12 text-white' : 'bg-[#296374]/10 text-[#296374]' }}">
                                @if ($link['icon'] === 'document')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                @elseif ($link['icon'] === 'user')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A12.07 12.07 0 0112 15.75c2.54 0 4.897.786 6.879 2.054M15 11a3 3 0 11-6 0 3 3 0 016 0zm6 1a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @elseif ($link['icon'] === 'chart')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold tracking-tight {{ str_contains($link['tone'], 'text-white') ? 'text-white' : 'text-slate-900' }}">{{ $link['label'] }}</h3>
                                <p class="mt-1 text-sm leading-6 {{ str_contains($link['tone'], 'text-white') ? 'text-white/75' : 'text-slate-600' }}">{{ $link['description'] }}</p>
                            </div>
                        </div>
                        <div class="shrink-0 text-sm font-medium {{ str_contains($link['tone'], 'text-white') ? 'text-white/80' : 'text-slate-400' }} transition group-hover:translate-x-1">
                            Open
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
