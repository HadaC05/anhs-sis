@extends('users.student.layout')

@section('title', 'Dashboard')

@section('content')
@push('modals')
@if ($profileCompletionRequired ?? false)
    <style>
        @media (min-width: 768px) {
            body.sidebar-collapsed .profile-completion-backdrop {
                left: 5.5rem;
            }
        }

        @media (max-width: 767px) {
            .app-sidebar {
                z-index: 201;
                pointer-events: none;
            }
        }
    </style>
    <div class="profile-completion-backdrop fixed inset-y-0 right-0 top-20 left-0 z-[200] bg-slate-900/60 md:left-72" aria-hidden="true"></div>
    <div class="fixed inset-0 z-[202] flex items-center justify-center px-4" role="dialog" aria-modal="true" aria-labelledby="profile-completion-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /></svg>
            </div>
            <h2 id="profile-completion-title" class="mt-4 text-xl font-bold text-gray-900">Complete your student profile</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">Some required information is still missing. Update and save your student profile before you can access the other parts of the portal.</p>
            <a href="{{ route('student.profile') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-[#296374] px-4 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#214e5c]">Update Student Profile</a>
        </div>
    </div>
@endif
@endpush

@php
    $studentName = optional($application)->last_name
        ? trim(optional($application)->last_name.', '.optional($application)->first_name.(optional($application)->middle_name ? ' '.optional($application)->middle_name : '').(optional($application)->suffix ? ' '.optional($application)->suffix : ''))
        : (Auth::user()->name ?? 'Student');
    $firstName = optional($application)->first_name ?: \Illuminate\Support\Str::of(Auth::user()->name)->before(' ');
    $enrollmentStatus = $currentEnrollment?->enrollment_status_label
        ? strtoupper($currentEnrollment->enrollment_status_label)
        : 'NOT ENROLLED';
    $gradeLabel = $currentEnrollment?->gradeLevel?->grade_label
        ?? ($currentEnrollment?->grade_level ? str_replace(['grade_', '_'], ['Grade ', ' '], $currentEnrollment->grade_level) : '—');
    $sectionName = $currentEnrollment?->section?->name ?? 'Not Assigned';
    $isSeniorHigh = $currentEnrollment?->isSeniorHigh() ?? false;
    $clusterName = $currentEnrollment?->cluster?->name ?? '—';
    $preferredCourseName = $currentEnrollment?->preferredCourse?->name ?? '—';
    $semesterLabel = $currentEnrollment?->semester ? ucfirst($currentEnrollment->semester).' Semester' : null;
    $schoolYear = $currentEnrollment?->academicYear?->school_year ?? $activeYear?->school_year ?? '—';
    $needsEnrollment = $currentEnrollment === null;
    $initials = $student?->initials() ?? strtoupper(\Illuminate\Support\Str::substr($firstName, 0, 1));
    $infoRows = [
        ['label' => 'Enrollment Status', 'value' => $enrollmentStatus],
        ['label' => 'Grade Level', 'value' => $gradeLabel],
        ['label' => 'Section', 'value' => $sectionName],
        ['label' => 'School Year', 'value' => $schoolYear],
    ];

    if ($isSeniorHigh) {
        if ($semesterLabel) {
            $infoRows[] = ['label' => 'Semester', 'value' => $semesterLabel];
        }

        $infoRows[] = ['label' => 'Cluster', 'value' => $clusterName];
        $infoRows[] = ['label' => 'Preferred Course', 'value' => $preferredCourseName];
    }

    $quickLinks = [
        [
            'label' => 'Student Profile',
            'href' => route('student.profile'),
            'card' => 'bg-[#296374] text-white hover:bg-[#214e5c]',
            'iconWrap' => 'bg-white/15 text-white',
            'icon' => 'user',
        ],
        [
            'label' => 'Subjects',
            'href' => route('student.subjects'),
            'card' => 'bg-emerald-500 text-white hover:bg-emerald-600',
            'iconWrap' => 'bg-white/15 text-white',
            'icon' => 'book',
        ],
        [
            'label' => 'Grades',
            'href' => route('student.grades'),
            'card' => 'bg-amber-400 text-amber-950 hover:bg-amber-300',
            'iconWrap' => 'bg-amber-950/10 text-amber-950',
            'icon' => 'chart',
        ],
        [
            'label' => 'Documents',
            'href' => route('student.documents'),
            'card' => 'bg-sky-500 text-white hover:bg-sky-600',
            'iconWrap' => 'bg-white/15 text-white',
            'icon' => 'folder',
        ],
    ];
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-7 xl:col-span-8">
            <section>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            </section>

            <section>
                <div class="mb-4">
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">Quick Access</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['href'] }}" class="group flex h-full min-w-0 flex-col rounded-2xl p-4 shadow-lg transition hover:-translate-y-0.5 sm:p-5 {{ $link['card'] }}">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $link['iconWrap'] }}">
                                @if ($link['icon'] === 'user')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A12.07 12.07 0 0112 15.75c2.54 0 4.897.786 6.879 2.054M15 11a3 3 0 11-6 0 3 3 0 016 0zm6 1a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                @elseif ($link['icon'] === 'book')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                @elseif ($link['icon'] === 'chart')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                @endif
                            </div>
                            <h3 class="mt-4 text-sm font-bold tracking-tight sm:text-lg">{{ $link['label'] }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-24">
            <section class="overflow-hidden rounded-md border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-300 bg-[#296374] px-6 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-white">Student Record</h2>
                </div>

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center border border-slate-300 bg-slate-50 text-sm font-semibold tracking-wide text-slate-800">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight text-slate-900">{{ $studentName }}</h3>
                            <p class="mt-1 text-sm text-slate-600">LRN {{ optional($student)->lrn ?? 'Not Set' }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Enrollment snapshot</h3>

                    <dl class="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                        @foreach ($infoRows as $row)
                            <div class="flex items-start justify-between gap-4 py-2.5">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $row['label'] }}</dt>
                                <dd class="text-right text-sm font-medium text-slate-900">{{ $row['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

            </section>
        </aside>
    </div>

    @if ($needsEnrollment)
        <section class="rounded-md border border-slate-300 bg-white px-6 py-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">You have not enrolled yet.</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Check Student Profile for your current enrollment status, or contact the guidance office if you still need to complete registration.
                    </p>
                </div>
                <a href="{{ route('student.profile') }}" class="inline-flex items-center justify-center rounded-md bg-[#296374] px-5 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-[#214e5c]">
                    View Student Profile
                </a>
            </div>
        </section>
    @endif

</div>
@endsection
