@extends('users.guidance.layout')

@section('title', 'Enrollment Details')

@section('content')
@if (session('toast_success'))
<div id="guidanceEnrollmentSuccessToast" role="status" aria-live="polite" class="fixed right-5 top-24 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <span>{{ session('toast_success') }}</span>
    <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" data-dismiss-guidance-enrollment-toast aria-label="Close notification">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
    </button>
</div>
<script>
    document.querySelector('[data-dismiss-guidance-enrollment-toast]')?.addEventListener('click', () => document.getElementById('guidanceEnrollmentSuccessToast')?.remove());
    window.setTimeout(() => document.getElementById('guidanceEnrollmentSuccessToast')?.remove(), 4000);
</script>
@endif

@if (session('status') && ! session('enrollment_result'))
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
    {{ session('status') }}
</div>
@endif

@if (session('success'))
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
    {{ session('success') }}
</div>
@endif

@if ($errors->any())
<div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    {{ $errors->first() }}
</div>
@endif

<style>
    .guidance-detail-wizard {
        --wizard-accent: #296374;
        --wizard-line: #e5e7eb;
    }

    .guidance-detail-step {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-height: 3.25rem;
        border-right: 1px solid var(--wizard-line);
        background: #fff;
        padding: 0.75rem 1.25rem;
        color: #4b5563;
        font-size: 0.84rem;
        font-weight: 700;
        white-space: nowrap;
        transition: color 150ms ease, box-shadow 150ms ease;
    }

    .guidance-detail-step:last-child {
        border-right: 0;
    }

    .guidance-detail-dot {
        display: inline-flex;
        height: 1rem;
        width: 1rem;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        border: 1.5px solid #9ca3af;
        color: transparent;
        font-size: 0.65rem;
        line-height: 1;
    }

    .guidance-detail-step.active {
        color: var(--wizard-accent);
        box-shadow: inset 0 -2px 0 var(--wizard-accent);
    }

    .guidance-detail-step.active .guidance-detail-dot,
    .guidance-detail-step.done .guidance-detail-dot {
        border-color: #4bb878;
        color: #4bb878;
    }

    .guidance-detail-section {
        display: none;
    }

    .guidance-detail-section.active {
        display: block;
    }

    .guidance-detail-page {
        overflow: visible;
    }

    .guidance-detail-page .guidance-detail-tabs {
        display: none;
    }

    .guidance-detail-page .guidance-detail-section {
        display: block;
        scroll-margin-top: 6.5rem;
        border: 1px solid var(--wizard-line);
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .guidance-detail-nav-link {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border-radius: 0.5rem;
        padding: 0.7rem 0.75rem;
        color: #4b5563;
        font-size: 0.875rem;
        font-weight: 600;
        transition: background-color 150ms ease, color 150ms ease;
    }

    .guidance-detail-nav-link:hover,
    .guidance-detail-nav-link:focus-visible,
    .guidance-detail-nav-link.is-active {
        background: rgba(41, 99, 116, 0.1);
        color: var(--wizard-accent);
        outline: none;
    }

    .guidance-detail-nav-number {
        display: inline-flex;
        height: 1.5rem;
        width: 1.5rem;
        flex: none;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background: #eef2f7;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .guidance-detail-nav-link.is-active .guidance-detail-nav-number {
        background: var(--wizard-accent);
        color: #fff;
    }

    @media (max-width: 1024px) {
        .guidance-detail-step {
            min-width: 210px;
            border-bottom: 1px solid var(--wizard-line);
        }
    }
</style>

<div class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
        <h1 class="mb-1 text-xl font-bold tracking-tight text-gray-700 md:text-2xl">Enrollment Details</h1>
        <p class="text-xs font-medium text-gray-500 md:text-sm">Submitted {{ $enrollment->created_at?->format('F d, Y \a\t H:i') ?? '-' }}</p>
    </div>
    <div class="flex flex-wrap gap-3 shrink-0 items-center">
        @php
            $approveStudent = $enrollment->student;
            $approveApplication = $approveStudent?->application;
            $approveFirstName = $approveApplication?->first_name ?? $approveStudent?->first_name;
            $approveLastName = $approveApplication?->last_name ?? $approveStudent?->last_name;
            $approveMiddleName = $approveApplication?->middle_name ?? $approveStudent?->middle_name;
            $approveStudentDisplayName = trim(($approveLastName ? $approveLastName.', ' : '').($approveFirstName ?? '').($approveMiddleName ? ' '.$approveMiddleName : '')) ?: 'this student';
        @endphp
        <div class="relative" id="enrollmentSettings">
            <button type="button" id="enrollmentSettingsButton"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-[#296374]/30 hover:text-[#296374]"
                aria-haspopup="true" aria-expanded="false" aria-controls="enrollmentSettingsMenu" title="Enrollment settings">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="sr-only">Enrollment settings</span>
            </button>
            <div id="enrollmentSettingsMenu" role="menu" class="absolute right-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                <a href="{{ route('guidance.enrollments.edit', array_filter(['enrollment' => $enrollment, 'from_section' => $fromSectionId])) }}" role="menuitem"
                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit details
                </a>
                <button type="button" id="openEnrollmentStatusModal" role="menuitem"
                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"></path>
                    </svg>
                    Update enrollment status
                </button>
            </div>
        </div>
        <a href="{{ route('guidance.enrollments.print', $enrollment) }}" target="_blank" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-600 text-white shadow-md transition hover:opacity-90" title="Print enrollment">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-12 0h12v4H6v-4z"></path></svg>
        </a>
    </div>
</div>

@php
$student = $enrollment->student;
$application = $student?->application;
$profile = $student?->profile;
$guardians = $student?->guardians ?? collect();
$father = $guardians->firstWhere('relationship', 'father');
$mother = $guardians->firstWhere('relationship', 'mother');
$guardian = $guardians->firstWhere('relationship', 'guardian');
$gradeLabel = $enrollment->gradeLevel?->grade_label
    ?? strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
$placementAssessment = $enrollment->placementAssessmentRecommendation();
$addressGroups = $student?->addresses?->groupBy('address_type') ?? collect();
$currentAddress = optional($addressGroups->get('current'))->first();
$permanentAddress = optional($addressGroups->get('permanent'))->first();
$formatGradeLevel = function ($gradeLevel): string {
    if (! $gradeLevel) {
        return '—';
    }

    return 'Grade '.str_replace('grade_', '', (string) $gradeLevel);
};
$addressFieldGroups = [
    'current' => [
        'title' => 'Current Address',
        'address' => $currentAddress,
    ],
    'permanent' => [
        'title' => 'Permanent Address',
        'address' => $permanentAddress,
    ],
];
$documentLabels = [
    'birth_certificate' => 'Birth Certificate',
    'form_137' => 'Form 137 / SF9',
    'good_moral' => 'Good Moral Certificate',
    'other' => 'Other Supporting Document',
];
$labelClass = 'mb-1.5 block text-sm text-gray-500';
$valueClass = 'min-h-[2.75rem] w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-[#296374] shadow-sm';
$headingClass = 'mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]';
$display = fn ($value) => filled($value) ? $value : '—';
$stepIndexes = ['enrollment' => 0, 'personal' => 1, 'addresses' => 2, 'parents' => 3, 'documents' => 4];
$currentStepIndex = $stepIndexes[$activeStep ?? ''] ?? 0;
@endphp

@php
    $placementStatus = $enrollment->placement_status ?: \App\Models\PlacementStatus::PENDING;
    $placementCardClasses = ($placementStatus === \App\Models\PlacementStatus::PENDING && $placementAssessment)
        ? 'border-amber-200 bg-amber-50/80'
        : \App\Models\PlacementStatus::cardClasses($placementStatus);
    $placementIconClasses = match ($placementStatus) {
        \App\Models\PlacementStatus::AGE_APPROPRIATE => 'bg-sky-100 text-sky-700',
        \App\Models\PlacementStatus::RECOMMENDED => 'bg-[#296374]/10 text-[#296374]',
        \App\Models\PlacementStatus::PASSED => 'bg-emerald-100 text-emerald-700',
        \App\Models\PlacementStatus::FAILED => 'bg-red-100 text-red-700',
        \App\Models\PlacementStatus::RESOLVED => 'bg-slate-200 text-slate-700',
        default => $placementAssessment ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600',
    };
    $showPlacementTestStatus = $placementAssessment || in_array($placementStatus, [
        \App\Models\PlacementStatus::RECOMMENDED,
        \App\Models\PlacementStatus::PASSED,
        \App\Models\PlacementStatus::FAILED,
        \App\Models\PlacementStatus::RESOLVED,
    ], true);
@endphp
@if ($showPlacementTestStatus)
<div class="mb-6 rounded-xl border px-5 py-4 shadow-sm {{ $placementCardClasses }}">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $placementIconClasses }}">
                @if ($placementStatus === \App\Models\PlacementStatus::PASSED)
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
                @elseif ($placementStatus === \App\Models\PlacementStatus::FAILED)
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path></svg>
                @elseif ($placementStatus === \App\Models\PlacementStatus::RECOMMENDED)
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
                @else
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                @endif
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-bold text-gray-900">Placement test</p>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ \App\Models\PlacementStatus::badgeClasses($placementStatus) }}">
                        {{ $enrollment->placement_status_label ?: 'Pending' }}
                    </span>
                </div>
                @if ($placementAssessment)
                    <p class="mt-0.5 text-sm text-gray-700">{{ $placementAssessment['summary'] }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $placementAssessment['detail'] }} Placement assessment recommended.</p>
                @elseif ($enrollment->hasPlacementStatusMark())
                    <p class="mt-0.5 text-sm text-gray-600">Update this student's placement test result as it changes.</p>
                @else
                    <p class="mt-0.5 text-sm text-gray-600">No placement test is currently recommended for this student.</p>
                @endif
            </div>
        </div>
        <form action="{{ route('guidance.enrollments.placement-test', $enrollment) }}" method="POST" class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
            @csrf
            @method('PATCH')
            <input type="hidden" name="step" value="{{ $activeStep ?: 'enrollment' }}" data-guidance-step-field>
            @if ($fromSectionId)
                <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
            @endif
            <select name="placement_status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach (\App\Models\PlacementStatus::options() as $slug => $label)
                    <option value="{{ $slug }}" @selected($placementStatus === $slug)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#296374] px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition hover:opacity-95">
                Update status
            </button>
        </form>
    </div>
</div>
@endif


<div class="guidance-detail-wizard guidance-detail-page rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="guidance-detail-tabs grid overflow-x-auto border-b border-gray-200 bg-white lg:grid-cols-5">
        <button type="button" class="guidance-detail-step {{ $currentStepIndex === 0 ? 'active' : '' }}" data-guidance-step="0">
            <span class="guidance-detail-dot">@if($currentStepIndex >= 0)&#10003;@endif</span>
            Enrollment
        </button>
        <button type="button" class="guidance-detail-step {{ $currentStepIndex === 1 ? 'active' : '' }}" data-guidance-step="1">
            <span class="guidance-detail-dot">@if($currentStepIndex >= 1)&#10003;@endif</span>
            Personal
        </button>
        <button type="button" class="guidance-detail-step {{ $currentStepIndex === 2 ? 'active' : '' }}" data-guidance-step="2">
            <span class="guidance-detail-dot">@if($currentStepIndex >= 2)&#10003;@endif</span>
            Addresses
        </button>
        <button type="button" class="guidance-detail-step {{ $currentStepIndex === 3 ? 'active' : '' }}" data-guidance-step="3">
            <span class="guidance-detail-dot">@if($currentStepIndex >= 3)&#10003;@endif</span>
            Parents / Guardians
        </button>
        <button type="button" class="guidance-detail-step {{ $currentStepIndex === 4 ? 'active' : '' }}" data-guidance-step="4">
            <span class="guidance-detail-dot">@if($currentStepIndex >= 4)&#10003;@endif</span>
            Documents
        </button>
    </div>

    <div class="grid gap-6 bg-gray-50/70 p-5 lg:grid-cols-[13rem_minmax(0,1fr)] lg:p-6">
        <aside class="self-start lg:sticky lg:top-24">
            <nav class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm" aria-label="Enrollment details sections">
                <p class="px-3 pb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Jump to section</p>
                <div class="space-y-1">
                    <a href="#detail-enrollment" class="guidance-detail-nav-link is-active" data-guidance-detail-nav="detail-enrollment"><span class="guidance-detail-nav-number">01</span>Enrollment</a>
                    <a href="#detail-personal" class="guidance-detail-nav-link" data-guidance-detail-nav="detail-personal"><span class="guidance-detail-nav-number">02</span>Personal</a>
                    <a href="#detail-addresses" class="guidance-detail-nav-link" data-guidance-detail-nav="detail-addresses"><span class="guidance-detail-nav-number">03</span>Addresses</a>
                    <a href="#detail-parents" class="guidance-detail-nav-link" data-guidance-detail-nav="detail-parents"><span class="guidance-detail-nav-number">04</span>Family</a>
                    <a href="#detail-documents" class="guidance-detail-nav-link" data-guidance-detail-nav="detail-documents"><span class="guidance-detail-nav-number">05</span>Documents</a>
                </div>
            </nav>
        </aside>
        <div class="space-y-5">

<div id="detail-enrollment" class="guidance-detail-section {{ $currentStepIndex === 0 ? 'active' : '' }} space-y-8 px-6 py-6" data-guidance-section>
    <div>
        <h2 class="{{ $headingClass }}">Enrollment Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="{{ $labelClass }}">LRN</label>
                <div class="{{ $valueClass }}">{{ $display($student?->lrn) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Grade Level</label>
                <div class="{{ $valueClass }}">{{ $display($gradeLabel) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Learner Type</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->learner_type_label) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->enrollment_status_label) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">School Year</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->academicYear?->school_year) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Section</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->section?->name ?? 'Not Assigned') }}</div>
            </div>
            @if(!empty($enrollment->semester) || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Semester</label>
                <div class="{{ $valueClass }}">{{ $enrollment->semester ? ucfirst($enrollment->semester).' Semester' : '—' }}</div>
            </div>
            @endif
            @if($enrollment->cluster || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Cluster</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->cluster?->name) }}</div>
            </div>
            @endif
            @if($enrollment->preferredCourse || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Preferred Course</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->preferredCourse?->name) }}</div>
            </div>
            @endif
            <div>
                <label class="{{ $labelClass }}">Last School Attended</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->last_school_attended) }}</div>
            </div>
        </div>
    </div>
    @if($enrollment->requiresPreviousSchoolDetails())
    <div>
        <h3 class="{{ $headingClass }}">Previous School Details</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="{{ $labelClass }}">Last Grade Level Completed</label>
                <div class="{{ $valueClass }}">{{ $formatGradeLevel($enrollment->last_grade_level_completed) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Last School Year Completed</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->last_school_year_completed) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Previous School ID</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->school_id_from_previous_school) }}</div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($student)
<div id="detail-personal" class="guidance-detail-section {{ $currentStepIndex === 1 ? 'active' : '' }} space-y-8 px-6 py-6" data-guidance-section>
    <div>
        <h2 class="{{ $headingClass }}">Personal Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="{{ $labelClass }}">Last Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->last_name ?? $student->last_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">First Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->first_name ?? $student->first_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Middle Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->middle_name ?? $student->middle_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Suffix</label>
                <div class="{{ $valueClass }}">{{ $display(($application?->suffix ?? $student->suffix) ?: 'None') }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Birthdate</label>
                <div class="{{ $valueClass }}">{{ $display($application?->birthdate?->format('M d, Y')) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Age at School Year Start</label>
                <div class="{{ $valueClass }} {{ $placementAssessment ? '!text-amber-700' : '' }}">
                    {{ $placementAssessment['age'] ?? ($application?->birthdate ? $application->birthdate->diff($enrollment->academicYear?->start_date ?? $enrollment->created_at ?? now())->y : '—') }}
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Sex</label>
                <div class="{{ $valueClass }}">{{ $display($student->sex ? ucfirst($student->sex) : null) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Contact Number</label>
                <div class="{{ $valueClass }}">{{ $display($application?->contact_no) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Email Address</label>
                <div class="{{ $valueClass }}">{{ $display($application?->email ?? $student->email) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Religion</label>
                <div class="{{ $valueClass }}">{{ $display($student->religion) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Mother Tongue</label>
                <div class="{{ $valueClass }}">{{ $display($student->mother_tongue) }}</div>
            </div>
        </div>
    </div>
    <div>
        <h3 class="{{ $headingClass }}">4Ps / IP / PWD</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="{{ $labelClass }}">4Ps Beneficiary</label>
                <div class="{{ $valueClass }}">{{ $profile?->is_4ps ? 'Yes' : 'No' }}</div>
                @if($profile?->is_4ps && $profile->four_ps_household_id)
                    <p class="mt-2 text-xs text-gray-500">Household ID: <span class="font-semibold text-[#296374]">{{ $profile->four_ps_household_id }}</span></p>
                @endif
            </div>
            <div>
                <label class="{{ $labelClass }}">Indigenous People (IP)</label>
                <div class="{{ $valueClass }}">{{ $profile?->is_ip ? ($profile->ip_community ?: 'Yes') : 'No' }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Person with Disability (PWD)</label>
                <div class="{{ $valueClass }}">{{ $profile?->has_disability ? ($profile->disability_name ?: 'Yes') : 'No' }}</div>
            </div>
        </div>
    </div>
</div>

<div id="detail-addresses" class="guidance-detail-section {{ $currentStepIndex === 2 ? 'active' : '' }} space-y-8 px-6 py-6" data-guidance-section>
    @if($student?->addresses && $student->addresses->isNotEmpty())
        @foreach($addressFieldGroups as $addressGroup)
            @php
                $addr = $addressGroup['address'];
            @endphp
            <div>
                <h2 class="{{ $headingClass }}">{{ $addressGroup['title'] }}</h2>
                @if($addr)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Province</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->province) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Municipality / City</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->municipality) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Barangay</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->barangay) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Zip Code</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->zip_code) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">House No.</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->house_no) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Street/Sitio</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->street_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Country</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->country) }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No {{ strtolower($addressGroup['title']) }} recorded.</p>
                @endif
            </div>
        @endforeach
    @else
        <h2 class="{{ $headingClass }}">Addresses</h2>
        <p class="text-sm text-gray-500">No addresses recorded.</p>
    @endif
</div>

<div id="detail-parents" class="guidance-detail-section {{ $currentStepIndex === 3 ? 'active' : '' }} space-y-8 px-6 py-6" data-guidance-section>
    <div>
        <h2 class="{{ $headingClass }}">Parent's / Guardian's Information</h2>
        @if($guardians->isNotEmpty())
            @foreach ([
                ['record' => $father, 'title' => "Father's Full Name"],
                ['record' => $mother, 'title' => "Mother's Full Name"],
                ['record' => $guardian, 'title' => "Guardian's Full Name"],
            ] as $guardianGroup)
                @php
                    $record = $guardianGroup['record'];
                @endphp
                <div class="{{ ! $loop->last ? 'mb-6' : '' }}">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-600">{{ $guardianGroup['title'] }}</p>
                        @if($record?->is_deceased)
                            <span class="text-xs font-semibold text-gray-600">Deceased</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <label class="{{ $labelClass }}">Last Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->last_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Given Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->first_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Middle Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->middle_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Suffix</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->suffix ?: 'None') }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Contact No.</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->is_deceased ? null : $record?->contact_no) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-sm text-gray-500">No parent or guardian records found.</p>
        @endif
    </div>
</div>

<div id="detail-documents" class="guidance-detail-section {{ $currentStepIndex === 4 ? 'active' : '' }} space-y-6 px-6 py-6" data-guidance-section>
    @php
        $pendingDocuments = isset($documents) ? $documents->where('status', 'pending') : collect();
        $documentReturnReasons = $documentReturnReasons ?? collect();
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="{{ $headingClass }} !mb-1">Supporting Enrollment Documents</h2>
            <p class="text-sm text-gray-500">Guidance can review, verify, return, or unverify the student's uploaded requirements here.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($pendingDocuments->isNotEmpty())
            <form id="bulk-verify-form" action="{{ route('guidance.documents.bulk-verify') }}" method="POST" class="document-action-form inline" data-confirm-title="Verify selected documents?" data-confirm-message="The selected documents will be marked as verified.">
                @csrf
                <input type="hidden" name="enrollment_ID" value="{{ $enrollment->enrollment_ID }}">
                @if ($fromSectionId)
                    <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
                @endif
                <button type="submit" id="bulk-verify-submit" disabled class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white bg-green-600 hover:bg-green-700 transition-colors disabled:cursor-not-allowed disabled:bg-green-600/50">
                    Verify selected
                </button>
            </form>
            @endif
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-100 text-slate-700">
                {{ isset($documents) ? $documents->count() : 0 }} uploaded
            </span>
        </div>
    </div>

    @if(isset($documents) && $documents->isNotEmpty())
    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="w-12 px-4 py-3"><span class="sr-only">Select</span></th>
                    <th class="px-4 py-3">Document</th>
                    <th class="px-4 py-3">Uploaded</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Remarks</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
        @foreach($documents as $doc)
        <tr class="align-top hover:bg-slate-50/70">
            <td class="px-4 py-4">
                @if(($doc->status ?? '') === 'pending')
                <input type="checkbox" form="bulk-verify-form" name="document_ids[]" value="{{ $doc->doc_ID }}" class="document-verify-checkbox h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]" aria-label="Select document">
                @endif
            </td>
            <td class="px-4 py-4 font-semibold text-[#296374]">{{ $documentLabels[$doc->doc_type] ?? ucwords(str_replace('_', ' ', $doc->doc_type ?? 'document')) }}</td>
            <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-600">{{ $doc->date_uploaded?->format('M d, Y h:i A') ?? '-' }}</td>
            <td class="px-4 py-4">
                @if(($doc->status ?? '') === 'verified')
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold bg-green-100 text-green-800">Verified</span>
                @elseif(($doc->status ?? '') === 'returned')
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold bg-red-100 text-red-800">Returned</span>
                @else
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold bg-amber-100 text-amber-800">Pending</span>
                @endif
            </td>
            <td class="px-4 py-4 text-xs text-gray-600">
                @if(($doc->status ?? '') === 'returned' && $doc->returnReason)
                    <span class="text-red-700">{{ $doc->returnReason->name }}</span>
                @elseif($doc->date_verified)
                    Reviewed {{ $doc->date_verified->format('M d, Y h:i A') }}
                @else
                    Awaiting review
                @endif
            </td>
            <td class="px-4 py-4">
                <div class="flex flex-wrap justify-end gap-2">

            @if($doc->file_path)
            <a href="{{ route('guidance.documents.view', $doc) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-1 rounded-lg border border-[#296374]/20 px-3 py-2 text-xs font-bold uppercase tracking-wide text-[#296374] hover:bg-[#296374]/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                View document
            </a>
            @else
            <span class="text-xs text-gray-500">No file attached.</span>
            @endif

            @if(($doc->status ?? '') === 'pending')
                <form action="{{ route('guidance.documents.verify', $doc) }}" method="POST" class="document-action-form inline" data-confirm-title="Verify document?" data-confirm-message="This marks the document as verified.">
                    @csrf
                    @if ($fromSectionId)
                        <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
                    @endif
                    <button type="submit" class="rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wide text-white bg-green-600 hover:bg-green-700 transition-colors">
                        Verify
                    </button>
                </form>
                <button type="button"
                    data-return-document-id="{{ $doc->doc_ID }}"
                    data-return-action="{{ route('guidance.documents.reject', $doc) }}"
                    class="document-return-trigger rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white bg-red-600 hover:bg-red-700 transition-colors">
                    Return
                </button>
            @elseif($doc->isVerified())
                <form action="{{ route('guidance.documents.unverify', $doc) }}" method="POST" class="document-action-form inline" data-confirm-title="Unverify document?" data-confirm-message="The student will be able to replace this document.">
                    @csrf
                    @if ($fromSectionId)
                        <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
                    @endif
                    <button type="submit" class="rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wide text-white bg-amber-600 hover:bg-amber-700 transition-colors">
                        Unverify
                    </button>
                </form>
            @endif
                </div>
            </td>
        </tr>
        @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-center">
        <p class="text-sm font-semibold text-gray-700">No supporting documents uploaded yet.</p>
        <p class="text-xs text-gray-500 mt-1">The guidance office will see uploaded requirements here once the student submits them.</p>
    </div>
    @endif
</div>
@endif

        </div>
    </div>
    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-7 py-6 sm:flex-row sm:items-center sm:justify-between">
        @if ($returnSection)
            <a href="{{ route('guidance.sections.show', $returnSection) }}" class="inline-flex items-center justify-center rounded-lg px-6 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">Back to {{ $returnSection->name }}</a>
        @else
            <a href="{{ route('guidance.enrollments.index') }}" class="inline-flex items-center justify-center rounded-lg px-6 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">Back to List</a>
        @endif
    </div>
</div>
<button type="button" id="scroll-to-top" class="fixed bottom-8 right-8 z-50 hidden w-12 h-12 rounded-full shadow-lg text-white flex items-center justify-center transition-opacity hover:opacity-90" style="background-color: #296374;" aria-label="Scroll to top">
    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('sidebar-collapsed');
        var sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
            sidebarToggle.setAttribute('aria-label', 'Expand sidebar');
            sidebarToggle.title = 'Expand sidebar';
        }

        var sections = Array.prototype.slice.call(document.querySelectorAll('[data-guidance-section]'));
        var steps = Array.prototype.slice.call(document.querySelectorAll('[data-guidance-step]'));
        var back = document.getElementById('guidanceDetailBack');
        var next = document.getElementById('guidanceDetailNext');
        var stepNames = ['enrollment', 'personal', 'addresses', 'parents', 'documents'];
        var stepMap = { enrollment: 0, personal: 1, addresses: 2, parents: 3, documents: 4 };
        var detailNavigation = Array.prototype.slice.call(document.querySelectorAll('[data-guidance-detail-nav]'));
        var active = 0;

        function setDetailNavigation(index) {
            detailNavigation.forEach(function(link, linkIndex) {
                link.classList.toggle('is-active', linkIndex === index);
            });
        }

        function syncStepLocation(index) {
            var name = stepNames[index] || 'enrollment';
            var url = new URL(window.location.href);
            url.searchParams.set('step', name);
            window.history.replaceState({}, '', url);

            document.querySelectorAll('[data-guidance-step-field]').forEach(function(field) {
                field.value = name;
            });
        }

        function setActive(index, shouldSyncLocation) {
            if (!sections.length) return;

            active = Math.max(0, Math.min(index, sections.length - 1));

            if (detailNavigation.length) {
                setDetailNavigation(active);
                if (shouldSyncLocation !== false) {
                    syncStepLocation(active);
                }
                return;
            }

            sections.forEach(function(section, sectionIndex) {
                section.classList.toggle('active', sectionIndex === active);
            });

            steps.forEach(function(step, stepIndex) {
                var dot = step.querySelector('.guidance-detail-dot');
                var isVisibleStep = stepIndex < sections.length;

                step.classList.toggle('hidden', !isVisibleStep);
                step.classList.toggle('active', stepIndex === active);
                step.classList.toggle('done', stepIndex < active);

                if (dot) {
                    dot.innerHTML = stepIndex <= active ? '&#10003;' : '';
                }
            });

            if (back) {
                back.classList.toggle('invisible', active === 0);
            }

            if (next) {
                next.classList.toggle('hidden', active === sections.length - 1);
            }

            if (shouldSyncLocation !== false) {
                syncStepLocation(active);
            }
        }

        steps.forEach(function(step, index) {
            step.addEventListener('click', function() {
                setActive(index);
            });
        });

        detailNavigation.forEach(function(link, index) {
            link.addEventListener('click', function(event) {
                var section = sections[index];
                if (!section) return;

                event.preventDefault();
                setActive(index);
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + section.id);
            });
        });

        back?.addEventListener('click', function() {
            setActive(active - 1);
        });

        next?.addEventListener('click', function() {
            setActive(active + 1);
        });

        var params = new URLSearchParams(window.location.search);
        var requestedStep = params.get('step');
        var initialStep = Object.prototype.hasOwnProperty.call(stepMap, requestedStep) ? stepMap[requestedStep] : 0;
        setActive(initialStep, false);

        if (detailNavigation.length && 'IntersectionObserver' in window) {
            var sectionObserver = new IntersectionObserver(function(entries) {
                var visibleSection = entries
                    .filter(function(entry) { return entry.isIntersecting; })
                    .sort(function(first, second) { return second.intersectionRatio - first.intersectionRatio; })[0];
                var index = sections.indexOf(visibleSection?.target);

                if (index >= 0) {
                    setDetailNavigation(index);
                    document.querySelectorAll('[data-guidance-step-field]').forEach(function(field) {
                        field.value = stepNames[index];
                    });
                }
            }, { rootMargin: '-20% 0px -65% 0px', threshold: [0.05, 0.25, 0.5] });

            sections.forEach(function(section) { sectionObserver.observe(section); });
        }

        var bulkForm = document.getElementById('bulk-verify-form');
        var bulkSubmit = document.getElementById('bulk-verify-submit');
        var bulkCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.document-verify-checkbox'));

        function syncBulkVerifyState() {
            if (!bulkSubmit) return;
            bulkSubmit.disabled = !bulkCheckboxes.some(function(checkbox) { return checkbox.checked; });
        }

        bulkCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', syncBulkVerifyState);
        });
        syncBulkVerifyState();

        function showActionConfirmation(form) {
            var previous = document.getElementById('document-action-toast');
            if (previous) previous.remove();

            var toast = document.createElement('div');
            toast.id = 'document-action-toast';
            toast.className = 'fixed bottom-5 right-5 z-[110] w-[min(24rem,calc(100vw-2.5rem))] rounded-xl border border-slate-200 bg-white p-4 shadow-xl';
            toast.innerHTML = '<p class="text-sm font-bold text-slate-800"></p><p class="mt-1 text-xs leading-relaxed text-slate-600"></p><div class="mt-4 flex justify-end gap-2"><button type="button" data-toast-cancel class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold uppercase tracking-wide text-gray-700">Cancel</button><button type="button" data-toast-confirm class="rounded-lg bg-[#296374] px-3 py-2 text-xs font-bold uppercase tracking-wide text-white">Confirm</button></div>';
            toast.querySelector('p:first-child').textContent = form.dataset.confirmTitle || 'Confirm action?';
            toast.querySelector('p:nth-child(2)').textContent = form.dataset.confirmMessage || 'This action will update the document.';
            toast.querySelector('[data-toast-cancel]').addEventListener('click', function() { toast.remove(); });
            toast.querySelector('[data-toast-confirm]').addEventListener('click', function() {
                form.dataset.confirmed = 'true';
                toast.remove();
                form.requestSubmit();
            });
            document.body.appendChild(toast);
        }

        document.querySelectorAll('.document-action-form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (form.dataset.confirmed === 'true') return;
                event.preventDefault();
                if (form === bulkForm && bulkSubmit && bulkSubmit.disabled) return;
                showActionConfirmation(form);
            });
        });

        var returnModal = document.getElementById('documentReturnModal');
        var returnForm = document.getElementById('documentReturnForm');
        var returnReason = document.getElementById('document_return_reason_ID');
        var returnCancel = document.getElementById('documentReturnCancel');

        function openReturnModal(action) {
            if (!returnModal || !returnForm) return;
            returnForm.action = action;
            returnModal.classList.remove('hidden');
            returnModal.classList.add('flex');
            if (returnReason) {
                returnReason.focus();
            }
        }

        function closeReturnModal() {
            if (!returnModal) return;
            returnModal.classList.add('hidden');
            returnModal.classList.remove('flex');
        }

        document.querySelectorAll('.document-return-trigger').forEach(function(button) {
            button.addEventListener('click', function() {
                var hiddenId = document.getElementById('returning_document_id');
                if (hiddenId) {
                    hiddenId.value = button.getAttribute('data-return-document-id') || '';
                }
                openReturnModal(button.getAttribute('data-return-action'));
            });
        });

        returnCancel?.addEventListener('click', closeReturnModal);
        returnModal?.addEventListener('click', function(event) {
            if (event.target === returnModal) {
                closeReturnModal();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && returnModal && !returnModal.classList.contains('hidden')) {
                closeReturnModal();
            }
        });

        @if ($errors->has('return_reason_ID'))
            var returningId = @json((string) old('returning_document_id'));
            var trigger = returningId
                ? document.querySelector('.document-return-trigger[data-return-document-id="' + returningId + '"]')
                : document.querySelector('.document-return-trigger');
            if (trigger) {
                var hiddenId = document.getElementById('returning_document_id');
                if (hiddenId) {
                    hiddenId.value = returningId || trigger.getAttribute('data-return-document-id') || '';
                }
                openReturnModal(trigger.getAttribute('data-return-action'));
            }
        @endif

        var btn = document.getElementById('scroll-to-top');
        if (!btn) return;

        function toggle() {
            btn.classList.toggle('hidden', window.scrollY < 300);
        }
        window.addEventListener('scroll', toggle);
        toggle();
        btn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
</script>

<div id="documentReturnModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="documentReturnTitle">
    <div class="mx-auto w-full max-w-md overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Documents</p>
            <h3 id="documentReturnTitle" class="mt-1 text-lg font-bold tracking-tight text-white">Return document</h3>
        </div>
        <form id="documentReturnForm" method="POST" action="" class="document-action-form" data-confirm-title="Return document?" data-confirm-message="The student will be asked to resubmit this document.">
            @csrf
            @if ($fromSectionId)
                <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
            @endif
            <input type="hidden" id="returning_document_id" name="returning_document_id" value="{{ old('returning_document_id') }}">
            <div class="space-y-4 px-6 py-5">
                <p class="text-sm leading-relaxed text-gray-600">Select a reason so the student knows why this document must be submitted again.</p>
                <div>
                    <label for="document_return_reason_ID" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Return reason <span class="text-red-500">*</span></label>
                    <select id="document_return_reason_ID" name="return_reason_ID" required
                        class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('return_reason_ID') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select a reason</option>
                        @foreach (($documentReturnReasons ?? collect()) as $reason)
                            <option value="{{ $reason->reason_ID }}" @selected((string) old('return_reason_ID') === (string) $reason->reason_ID)>{{ $reason->name }}</option>
                        @endforeach
                    </select>
                    @error('return_reason_ID')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" id="documentReturnCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95 bg-red-600 hover:bg-red-700">Return document</button>
            </div>
        </form>
    </div>
</div>

@include('users.guidance.enrollments.partials.enrollment-flow-modals', [
    'confirmContext' => 'single',
    'studentDisplayName' => $approveStudentDisplayName ?? 'this student',
    'returnSectionUrl' => $returnSection ? route('guidance.sections.show', $returnSection) : null,
    'currentEnrollmentStatus' => $enrollment->enrollment_status,
])
@endsection

