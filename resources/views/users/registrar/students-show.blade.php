@extends('users.registrar.layout')

@section('title', 'Student Details')

@section('content')
@php
    $application = $student->application;
    $profile = $student->profile;
    $guardians = $student->guardians;
    $father = $guardians->firstWhere('relationship', 'father');
    $mother = $guardians->firstWhere('relationship', 'mother');
    $guardian = $guardians->firstWhere('relationship', 'guardian');
    $fullName = trim(($student->last_name ? $student->last_name.', ' : '').$student->first_name.' '.$student->middle_name.' '.$student->suffix);
    $gradeLabel = $enrollment ? strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level)) : null;
    $placementAssessment = $enrollment?->placementAssessmentRecommendation();
    $addressFieldGroups = [
        ['title' => 'Current Address', 'address' => $student->addresses->firstWhere('address_type', 'current')],
        ['title' => 'Permanent Address', 'address' => $student->addresses->firstWhere('address_type', 'permanent')],
    ];
    $labelClass = 'mb-1.5 block text-sm text-gray-500';
    $valueClass = 'min-h-[2.75rem] w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-[#296374] shadow-sm';
    $headingClass = 'mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]';
    $display = fn ($value) => filled($value) ? $value : '—';
    $formatGradeLevel = fn ($gradeLevel) => $gradeLevel ? 'Grade '.str_replace('grade_', '', $gradeLevel) : '—';
    $recordSections = ['enrollment' => 'Enrollment Information', 'personal' => 'Personal Information', 'addresses' => 'Addresses', 'parents' => 'Parents / Guardian', 'documents' => 'Documents', 'history' => 'Academic History'];
@endphp
<style>
    .registrar-record-section { scroll-margin-top: 6.5rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: white; box-shadow: 0 1px 2px rgb(15 23 42 / .04); }
    .registrar-record-nav a { display: flex; align-items: center; gap: .65rem; border-radius: .5rem; padding: .7rem .75rem; color: #4b5563; font-size: .875rem; font-weight: 600; }
    .registrar-record-nav a:hover, .registrar-record-nav a:focus-visible, .registrar-record-nav a[aria-current] { background: rgb(41 99 116 / .1); color: #296374; }
    .registrar-record-grid { display: grid; gap: 1.5rem; }
    @container (min-width: 1000px) {
        .registrar-record-grid { grid-template-columns: 230px minmax(0, 1fr); }
        .registrar-record-nav { position: sticky; top: 6.5rem; }
    }
</style>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">Student Details</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $fullName ?: 'Unnamed student' }} &middot; LRN {{ $student->lrn ?: '—' }}</p>
    </div>
    <a href="{{ route('registrar.students') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Back to Masterlist</a>
</div>
@if ($errors->any())
<div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
@endif
<div class="registrar-record-grid">
    <aside>
        <nav class="registrar-record-nav rounded-xl border border-gray-200 bg-white p-3 shadow-sm" aria-label="Student details sections">
            <p class="px-3 pb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Jump to section</p>
            @foreach ($recordSections as $key => $label)
            <a href="#detail-{{ $key }}"><span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-bold">{{ sprintf('%02d', $loop->iteration) }}</span>{{ $label }}@if ($key === 'documents') <span class="ml-auto text-xs">{{ $student->documents->count() }}</span>@endif</a>
            @endforeach
        </nav>
    </aside>
    <div class="min-w-0 space-y-6">
        @if ($student->enrollments->count() > 1)
        <form method="GET" action="{{ route('registrar.students.show', $student) }}" class="flex flex-wrap items-center gap-3">
            <label for="record-enrollment" class="text-sm font-semibold text-gray-600">Enrollment record</label>
            <select id="record-enrollment" name="enrollment_id" onchange="this.form.requestSubmit()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                @foreach ($student->enrollments as $record)
                <option value="{{ $record->enrollment_ID }}" @selected($enrollment?->enrollment_ID === $record->enrollment_ID)>{{ $record->academicYear?->school_year }} — {{ strtoupper(str_replace('grade_', 'Grade ', $record->grade_level)) }} — {{ $record->section?->name ?? 'Unassigned' }}</option>
                @endforeach
            </select>
        </form>
        @endif
        @include('users.registrar.partials.student-record-sections')
        @include('users.registrar.partials.student-documents')
        @include('users.registrar.partials.student-academic-history')
    </div>
</div>
<script>
    (() => {
        const links = [...document.querySelectorAll('.registrar-record-nav a')];
        const markSection = (id) => links.forEach(link => {
            if (link.hash === '#' + id) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        links.forEach(link => link.addEventListener('click', () => markSection(link.hash.slice(1))));
        markSection(location.hash.slice(1) || 'detail-enrollment');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                const visible = entries.find(entry => entry.isIntersecting);
                if (visible) markSection(visible.target.id);
            }, { rootMargin: '-100px 0px -55% 0px' });
            document.querySelectorAll('[data-record-section]').forEach(section => observer.observe(section));
        }
    })();
</script>
@endsection