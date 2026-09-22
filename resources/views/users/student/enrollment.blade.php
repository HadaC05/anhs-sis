@extends($layout ?? 'users.student.layout')

@section('title', $pageTitle ?? 'Enrollment')

@section('content')
@php
    $adviserName = optional(optional($currentEnrollment)->section?->adviser)->last_name
        ? optional($currentEnrollment->section->adviser)->last_name . ', ' . optional($currentEnrollment->section->adviser)->first_name
            . (optional($currentEnrollment->section->adviser)->middle_name ? ' ' . substr(optional($currentEnrollment->section->adviser)->middle_name, 0, 1) . '.' : '')
            . (optional($currentEnrollment->section->adviser)->suffix ? ' ' . optional($currentEnrollment->section->adviser)->suffix : '')
        : 'Not assigned';
    $enrollmentStatus = $currentEnrollment?->enrollment_status_label
        ?: 'Not enrolled';
    $gradeLevelLabel = $currentEnrollment?->grade_level
        ? strtoupper(str_replace('grade_', 'Grade ', $currentEnrollment->grade_level))
        : '-';
    $preferredCoursesByCluster = ($clusters ?? collect())->mapWithKeys(fn ($cluster) => [
        (string) $cluster->cluster_ID => $cluster->preferredCourses->map(fn ($course) => [
            'id' => (string) $course->course_ID,
            'name' => $course->name,
        ])->values(),
    ]);
    $availableGradeLevels = collect($gradeLevels ?? [])->filter(function ($level) {
        return in_array(str_replace('grade_', '', $level['value']), ['7', '8', '9', '10', '11', '12'], true);
    });
    $defaults = $formDefaults ?? [];
    $value = fn (string $key, mixed $fallback = '') => old($key, $defaults[$key] ?? $fallback);
    $previousSchoolYear = $value('last_school_year_completed');
    $lastSchoolYearMaxStart = preg_match('/^(\d{4})-\d{4}$/', (string) $activeYear?->school_year, $matches)
        ? min((int) $matches[1], now()->year)
        : now()->year;
    $isPublicEnrollment = ($layout ?? null) === 'layouts.public-enrollment';
    $isGuidanceEdit = ($layout ?? null) === 'users.guidance.layout' && ! empty($enrollmentMethod);
    $cancelUrl = $cancelUrl ?? route('home');
    $formAction = $enrollmentStoreUrl ?? route($enrollmentStoreRoute ?? 'register.store');
    $birthdateValue = $value('birthdate', optional(optional($application)->birthdate)->format('Y-m-d'));
    $computedAge = $birthdateValue ? \Illuminate\Support\Carbon::parse($birthdateValue)->age : '';
@endphp
<style>
    .enrollment-clean-shell {
        --enroll-accent: #296374;
        --enroll-line: #e5e7eb;
        --enroll-muted: #6b7280;
        color: #2f343b;
    }

    .enrollment-clean-shell label {
        margin-bottom: 0.45rem;
        color: #4b5563;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0;
    }

    .enrollment-clean-shell input:not([type="radio"]):not([type="checkbox"]),
    .enrollment-clean-shell select {
        width: 100%;
        min-height: 2.75rem;
        border: 1px solid #d8dde5;
        border-radius: 0.375rem;
        background: #fff;
        padding: 0.65rem 0.85rem;
        color: #374151;
        font-size: 0.9rem;
        box-shadow: none;
        outline: none;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .enrollment-clean-shell input:not([type="radio"]):not([type="checkbox"]):focus,
    .enrollment-clean-shell select:focus {
        border-color: var(--enroll-accent);
        box-shadow: 0 0 0 3px rgba(41, 99, 116, 0.12);
    }

    .enrollment-clean-shell input[readonly] {
        background: #f8fafc;
    }

    .enrollment-clean-shell .field-invalid {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
    }

    .enrollment-clean-shell .choice-group.field-invalid {
        outline: 1px solid #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .enrollment-progress {
        overflow-x: auto;
    }

    .enrollment-step {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-height: 3.25rem;
        border-right: 1px solid var(--enroll-line);
        padding: 0.75rem 1.25rem;
        color: #4b5563;
        background: #fff;
        font-size: 0.84rem;
        font-weight: 600;
        white-space: nowrap;
        transition: color 150ms ease, box-shadow 150ms ease;
    }

    .enrollment-step:last-child {
        border-right: 0;
    }

    .enrollment-step-dot {
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

    .enrollment-step.active {
        color: var(--enroll-accent);
        box-shadow: inset 0 -2px 0 var(--enroll-accent);
    }

    .enrollment-step.active .enrollment-step-dot,
    .enrollment-step.done .enrollment-step-dot {
        border-color: #4bb878;
        color: #4bb878;
    }

    .enrollment-section {
        display: none;
    }

    .enrollment-section.active {
        display: block;
    }

    .enrollment-section-title {
        margin-bottom: 1.75rem;
        color: #2f343b;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: 0;
    }

    .enrollment-subtitle {
        margin: 1.25rem 0 0.9rem;
        color: var(--enroll-accent);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .enrollment-clean-shell .choice-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        border-radius: 0.375rem;
        background: #f4f6f9;
        padding: 0.65rem 0.8rem;
    }

    .enrollment-clean-shell .choice-group label {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #4b5563;
        font-size: 0.86rem;
        font-weight: 600;
    }

    #required_field {
        color: red;
    }

    .optional-field {
        color: #9ca3af;
        font-weight: 400;
        font-size: 0.75rem;
    }

    @media (max-width: 1024px) {
        .enrollment-step {
            min-width: 220px;
            border-bottom: 1px solid var(--enroll-line);
        }
    }

    @media (max-width: 640px) {
        .enrollment-step {
            min-width: 200px;
        }

        .enrollment-section {
            scroll-margin-top: 5.5rem;
        }
    }

    .guidance-edit-form {
        overflow: visible;
    }

    .guidance-edit-form .enrollment-section {
        display: block;
        scroll-margin-top: 6.5rem;
        border: 1px solid var(--enroll-line);
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .guidance-edit-nav-link {
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

    .guidance-edit-nav-link:hover,
    .guidance-edit-nav-link:focus-visible,
    .guidance-edit-nav-link.is-active {
        background: rgba(41, 99, 116, 0.1);
        color: var(--enroll-accent);
        outline: none;
    }

    .guidance-edit-nav-number {
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

    .guidance-edit-nav-link.is-active .guidance-edit-nav-number {
        background: var(--enroll-accent);
        color: #fff;
    }

    .guidance-edit-actions {
        position: fixed;
        right: 1.5rem;
        bottom: 1.5rem;
        z-index: 30;
        border: 1px solid #dbe2ea;
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.96);
        padding: 0.75rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(10px);
    }

    @media (max-width: 640px) {
        .guidance-edit-actions {
            right: 0.75rem;
            bottom: 0.75rem;
            left: 6.25rem;
        }
    }
</style>
<div class="space-y-6">
    @if ($isPublicEnrollment && session('registration_submitted'))
    <div id="registrationSuccessModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="registrationSuccessTitle">
        <div class="absolute inset-0 bg-slate-900/55"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-2xl">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h2 id="registrationSuccessTitle" class="text-2xl font-bold tracking-tight text-gray-700">
                Enrollment Submitted Successfully
            </h2>
            <p class="mt-3 text-sm leading-6 text-gray-600 md:text-base">
                {{ session('status', 'Use Check Enrollment Status for updates on your application.') }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('home') }}" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Back to Home
                </a>
                <a href="{{ route('login') }}" class="inline-flex justify-center rounded-lg px-6 py-3 text-sm font-bold text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
                    Go to Login
                </a>
            </div>
        </div>
    </div>
    @endif

    @if ($hasEnrollment)
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">{{ $heading ?? 'Enrollment Entry' }}</h1>
        <p class="text-gray-600 text-sm md:text-base">{{ $subheading ?? 'Complete your enrollment information.' }}</p>
    </div>
    @endif

    @if (session('status') && ! session('registration_submitted'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    @if ($errors->has('enrollment') || $errors->has('LRN') || $errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ $errors->first('enrollment') ?: $errors->first('LRN') ?: $errors->first() }}
    </div>
    @endif

    @if (! $activeYear)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Enrollment is currently unavailable because no active school year is configured.
    </div>
    @endif

    @if ($hasEnrollment)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        You already submitted an enrollment for the current school year.
    </div>
    @endif

    @if ($hasEnrollment && $currentEnrollment)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="shadow-xl rounded-2xl p-6 md:col-span-2" style="background-color: #296374;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Enrollment Status</p>
            <p class="text-2xl font-bold text-white">{{ $enrollmentStatus }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #10b981;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">School Year</p>
            <p class="text-xl font-bold text-white">{{ $currentEnrollment->academicYear?->school_year ?? ($activeYear?->school_year ?? '-') }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #f59e0b;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Grade Level</p>
            <p class="text-xl font-bold text-white">{{ $gradeLevelLabel }}</p>
        </div>
    </div>

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
        <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">Enrollment Information</span>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Status</label>
                <p class="text-sm font-semibold text-gray-800">{{ $enrollmentStatus }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">School Year Enrolled</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->academicYear?->school_year ?? ($activeYear?->school_year ?? '-') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Grade Level</label>
                <p class="text-sm font-semibold text-gray-800">{{ $gradeLevelLabel }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Section</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->section?->name ?? 'Not assigned' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Adviser</label>
                <p class="text-sm font-semibold text-gray-800">{{ $adviserName }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Semester Enrolled</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->semester ? ucfirst($currentEnrollment->semester) . ' Semester' : 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Cluster</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->cluster?->name ?? 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Preferred Course</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->preferredCourse?->name ?? 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Submitted On</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->created_at?->format('M d, Y h:i A') ?? '-' }}</p>
            </div>
        </div>
    </div>
    @else
    <form action="{{ $formAction }}" method="POST" class="enrollment-clean-shell {{ $isGuidanceEdit ? 'guidance-edit-form' : 'overflow-hidden' }} rounded-lg border border-gray-200 bg-white shadow-xl" id="enrollmentForm" data-restrict-unsafe-input novalidate autocomplete="off">
        @csrf
        @if (! empty($enrollmentMethod))
            @method($enrollmentMethod)
        @endif
        @if (! empty($fromSectionId))
            <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
        @endif
        <div class="px-7 py-7 md:px-9">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-800">{{ $heading ?? 'Enrollment Entry' }}</h2>
            @unless ($isGuidanceEdit)
                <p class="mt-1 text-sm text-gray-500">{{ $subheading ?? 'Complete your enrollment information.' }}</p>
            @endunless
        </div>

        <div class="enrollment-progress {{ $isGuidanceEdit ? 'hidden' : 'grid' }} border-y border-gray-200 bg-white lg:grid-cols-4">
            <button type="button" class="enrollment-step active" data-step-target="0">
                <span class="enrollment-step-dot">&#10003;</span>
                Enrollment Details
            </button>
            <button type="button" class="enrollment-step" data-step-target="1">
                <span class="enrollment-step-dot"></span>
                Personal Information
            </button>
            <button type="button" class="enrollment-step" data-step-target="2">
                <span class="enrollment-step-dot"></span>
                Address & Family
            </button>
            <button type="button" class="enrollment-step" data-step-target="3">
                <span class="enrollment-step-dot"></span>
                Educational Background
            </button>
        </div>

        @if ($isGuidanceEdit)
        <div class="grid gap-6 bg-gray-50/70 p-5 lg:grid-cols-[13rem_minmax(0,1fr)] lg:p-6">
            <aside class="self-start lg:sticky lg:top-24">
                <nav class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm" aria-label="Edit student details sections">
                    <p class="px-3 pb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Jump to section</p>
                    <div class="space-y-1">
                        <a href="#enrollment-details" class="guidance-edit-nav-link is-active" data-guidance-edit-nav="enrollment-details"><span class="guidance-edit-nav-number">01</span>Enrollment</a>
                        <a href="#personal-information" class="guidance-edit-nav-link" data-guidance-edit-nav="personal-information"><span class="guidance-edit-nav-number">02</span>Personal</a>
                        <a href="#address-family" class="guidance-edit-nav-link" data-guidance-edit-nav="address-family"><span class="guidance-edit-nav-number">03</span>Address &amp; Family</a>
                        <a href="#educational-background" class="guidance-edit-nav-link" data-guidance-edit-nav="educational-background"><span class="guidance-edit-nav-number">04</span>Education</a>
                    </div>
                </nav>
            </aside>
            <div class="space-y-5">
        @endif

        <div id="enrollment-details" class="enrollment-section active p-8" data-step-section>
            <h3 class="enrollment-section-title">01. Enrollment Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Grade Level to Enroll<span id="required_field">*</span></label>
                    <select name="grade_level" id="grade_level" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                        <option value="">Select Level</option>
                        @foreach ($availableGradeLevels as $level)
                            @php($number = str_replace('grade_', '', $level['value']))
                            <option value="{{ $number }}" {{ (string) $value('grade_level') === (string) $number ? 'selected' : '' }}>{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Learner Reference Number (LRN)<span id="required_field">*</span></label>
                    <input type="text" name="LRN" id="lrn_input" placeholder="Enter your 12-digit LRN" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" value="{{ $value('LRN', optional($student)->lrn) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required autocomplete="off" {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                    <p id="lrn_feedback" class="mt-1 text-xs text-red-600 hidden" role="alert"></p>
                </div>
            </div>

            <div id="semester_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Semester</label>
                <div class="choice-group mt-2">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="semester" value="first" class="mr-2 accent-[#296374]" {{ $value('semester') === 'first' ? 'checked' : '' }}> 1st Semester
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="semester" value="second" class="mr-2 accent-[#296374]" {{ $value('semester') === 'second' ? 'checked' : '' }}> 2nd Semester
                    </label>
                </div>
            </div>

            <div id="cluster_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Cluster</label>
                <select name="cluster_ID" id="cluster_ID" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    <option value="">Select Cluster</option>
                    @foreach ($clusters as $cluster)
                    <option value="{{ $cluster->cluster_ID }}" {{ (string) $value('cluster_ID') === (string) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="preferred_course_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Preferred Course</label>
                <select name="course_ID" id="course_ID" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    <option value="">Select Preferred Course</option>
                </select>
            </div>

            <div class="mb-0 mt-6">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Learner Type <span class="text-gray-400 font-normal text-xs">(Ignore if not applicable.)</span></label>
                <div class="choice-group mt-2 mb-2">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="regular" class="mr-2 accent-[#296374]" {{ $value('learner_type', 'regular') === 'regular' ? 'checked' : '' }}> New
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="transferee" class="mr-2 accent-[#296374]" {{ $value('learner_type') === 'transferee' ? 'checked' : '' }}> Transferee
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="balik_aral" class="mr-2 accent-[#296374]" {{ in_array($value('learner_type'), ['balik_aral', 'returnee'], true) ? 'checked' : '' }}> Balik Aral
                    </label>
                </div>
                <div id="learner_details_container" class="mt-4 space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Last Grade Level Completed<span id="required_field">*</span></label>
                            <select name="last_grade_level_completed" id="last_grade_level_completed" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                                <option value="">Select Grade Level</option>
                                <option value="6" data-show-for-grade="7" {{ (string) $value('last_grade_level_completed') === '6' ? 'selected' : '' }}>Grade 6</option>
                                @foreach ($gradeLevels as $level)
                                    @php($number = str_replace('grade_', '', $level['value']))
                                    <option value="{{ $number }}" {{ (string) $value('last_grade_level_completed') === (string) $number ? 'selected' : '' }}>{{ $level['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Last School Year Completed<span id="required_field">*</span></label>
                            <select name="last_school_year_completed" id="last_school_year_completed" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                                <option value="">Select School Year</option>
                                @for ($startYear = $lastSchoolYearMaxStart; $startYear >= 2000; $startYear--)
                                    @php($schoolYear = $startYear . '-' . ($startYear + 1))
                                    <option value="{{ $schoolYear }}" @selected((string) $previousSchoolYear === $schoolYear)>{{ $schoolYear }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-2">School ID from Previous School<span id="required_field">*</span></label>
                        <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="school_id_from_previous_school" placeholder="Enter 6-digit school ID" value="{{ $value('school_id_from_previous_school') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div id="personal-information" class="enrollment-section p-8" data-step-section>
            <h3 class="enrollment-section-title">02. Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Last Name<span id="required_field">*</span></label>
                    <input type="text" name="last_name" placeholder="Enter last name" value="{{ $value('last_name', optional($application)->last_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">First Name<span id="required_field">*</span></label>
                    <input type="text" name="first_name" placeholder="Enter first name" value="{{ $value('first_name', optional($application)->first_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Middle Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="middle_name" placeholder="Enter middle name" value="{{ $value('middle_name', optional($application)->middle_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Suffix <span class="optional-field">(Optional)</span></label>
                    <select name="suffix" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        <option value="">None</option>
                        @foreach ($suffixOptions as $suffixOption)
                            <option value="{{ $suffixOption }}" {{ $value('suffix', optional($application)->suffix) === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Birthdate<span id="required_field">*</span></label>
                    <input type="date" name="birthdate" id="birthdateInput" min="{{ $earliestBirthdate }}" value="{{ $birthdateValue }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Age</label>
                    <input type="text" id="ageDisplay" value="{{ $computedAge }}" placeholder="Auto-computed" readonly tabindex="-1" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Birthplace<span id="required_field">*</span></label>
                    <input type="text" name="birthplace" placeholder="Enter birthplace" value="{{ $value('birthplace', optional($application)->birthplace) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Sex<span id="required_field">*</span></label>
                    <div class="choice-group mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="gender" value="Male" class="mr-2 accent-[#296374]" {{ $value('gender', filled(optional($application)->sex) ? ucfirst((string) optional($application)->sex) : '') === 'Male' ? 'checked' : '' }} required> Male
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="gender" value="Female" class="mr-2 accent-[#296374]" {{ $value('gender', filled(optional($application)->sex) ? ucfirst((string) optional($application)->sex) : '') === 'Female' ? 'checked' : '' }} required> Female
                        </label>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Contact Number<span id="required_field">*</span></label>
                    <input type="tel" name="contact_no" placeholder="+639XXXXXXXXX" value="{{ $value('contact_no', optional($application)->contact_no) }}" maxlength="13" inputmode="numeric" pattern="\+63\d{10}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Email<span id="required_field">*</span></label>
                    <input type="email" name="email" id="email_input" placeholder="Enter email address" value="{{ $value('email', optional($application)->email) }}" autocomplete="email" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required>
                    <p id="email_feedback" class="mt-1 text-xs text-red-600 hidden" role="alert"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Religion<span id="required_field">*</span></label>
                    <select name="religion" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required>
                        <option value="">Select Religion</option>
                        @foreach ($religions as $religion)
                            <option value="{{ $religion }}" {{ $value('religion', optional($application)->religion) === $religion ? 'selected' : '' }}>{{ $religion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Mother Tongue<span id="required_field">*</span></label>
                    <input type="text" name="mother_tongue" placeholder="Enter mother tongue" value="{{ $value('mother_tongue', optional($application)->mother_tongue) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Belonging to any Indigenous People (IP) Community?</label>
                    <div class="choice-group mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="ip_community" value="No" class="mr-2 accent-[#296374]" {{ $value('ip_community', 'No') === 'No' ? 'checked' : '' }}> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="ip_community" value="Yes" class="mr-2 accent-[#296374]" {{ $value('ip_community', 'No') === 'Yes' ? 'checked' : '' }}> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="ip_details_container">
                        <input type="text" name="ip_details" placeholder="Specify IP/Community" value="{{ $value('ip_details') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Is your family a beneficiary of 4Ps?</label>
                    <div class="choice-group mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="four_ps_beneficiary" value="No" class="mr-2 accent-[#296374]" {{ $value('four_ps_beneficiary', 'No') === 'No' ? 'checked' : '' }}> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="four_ps_beneficiary" value="Yes" class="mr-2 accent-[#296374]" {{ $value('four_ps_beneficiary', 'No') === 'Yes' ? 'checked' : '' }}> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="four_ps_details_container">
                        <input type="text" name="four_ps_details" placeholder="4Ps Household ID Number" value="{{ $value('four_ps_details') }}" minlength="17" maxlength="21" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        <p class="mt-1 text-xs text-gray-500">Must be 17 to 21 characters.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Is the learner a Person with Disability (PWD)?</label>
                    <div class="choice-group mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="pwd" value="No" class="mr-2 accent-[#296374]" {{ $value('pwd', 'No') === 'No' ? 'checked' : '' }}> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="pwd" value="Yes" class="mr-2 accent-[#296374]" {{ $value('pwd', 'No') === 'Yes' ? 'checked' : '' }}> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="pwd_details_container">
                        <input type="text" name="pwd_details" placeholder="Specify Disability" value="{{ $value('pwd_details') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div id="address-family" class="enrollment-section p-8" data-step-section>
            <h3 class="enrollment-section-title">03. Address & Family Details</h3>
            <div class="enrollment-subtitle">Current Address</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <input type="hidden" name="curr_country" value="{{ $value('curr_country', 'Philippines') }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Province<span id="required_field">*</span></label>
                    <select id="curr_province_select" data-address-role="province" data-address-prefix="curr" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required></select>
                    <input type="hidden" name="curr_province" value="{{ $value('curr_province') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Municipality / City<span id="required_field">*</span></label>
                    <select id="curr_municipality_city_select" data-address-role="municipality" data-address-prefix="curr" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required disabled></select>
                    <input type="hidden" name="curr_municipality_city" value="{{ $value('curr_municipality_city') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Barangay<span id="required_field">*</span></label>
                    <select id="curr_barangay_select" data-address-role="barangay" data-address-prefix="curr" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required disabled></select>
                    <input type="hidden" name="curr_barangay" value="{{ $value('curr_barangay') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Zip Code<span id="required_field">*</span></label>
                    <input type="text" name="curr_zip_code" value="{{ $value('curr_zip_code') }}" placeholder="Auto-generated" readonly required class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">House No. <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="curr_house_no" value="{{ $value('curr_house_no') }}" placeholder="House No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Street/Sitio <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="curr_street_name" value="{{ $value('curr_street_name') }}" placeholder="Street/Sitio" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <input id="same_address" name="same_address" type="checkbox" value="1" class="h-4 w-4 accent-[#296374]" {{ $value('same_address') ? 'checked' : '' }}>
                <label for="same_address" class="text-xs font-semibold text-gray-700">Permanent address is the same as current address</label>
            </div>

            <div class="enrollment-subtitle">Permanent Address</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <input type="hidden" name="perm_country" value="{{ $value('perm_country', 'Philippines') }}" data-perm-field>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Province<span id="required_field">*</span></label>
                    <select id="perm_province_select" data-address-role="province" data-address-prefix="perm" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field required></select>
                    <input type="hidden" name="perm_province" value="{{ $value('perm_province') }}" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Municipality / City<span id="required_field">*</span></label>
                    <select id="perm_municipality_city_select" data-address-role="municipality" data-address-prefix="perm" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field required disabled></select>
                    <input type="hidden" name="perm_municipality_city" value="{{ $value('perm_municipality_city') }}" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Barangay<span id="required_field">*</span></label>
                    <select id="perm_barangay_select" data-address-role="barangay" data-address-prefix="perm" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field required disabled></select>
                    <input type="hidden" name="perm_barangay" value="{{ $value('perm_barangay') }}" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Zip Code<span id="required_field">*</span></label>
                    <input type="text" name="perm_zip_code" value="{{ $value('perm_zip_code') }}" placeholder="Auto-generated" readonly required class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">House No. <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="perm_house_no" value="{{ $value('perm_house_no') }}" placeholder="House No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Street/Sitio <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="perm_street_name" value="{{ $value('perm_street_name') }}" placeholder="Street/Sitio" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
            </div>

            <div class="enrollment-subtitle">Parent's / Guardian's Information</div>
            <p class="mb-4 text-xs text-gray-500">Enter <span class="font-semibold">N/A</span> in the name fields if not applicable. Mark the checkbox if a parent or guardian is deceased.</p>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <span class="block text-xs font-semibold text-gray-500">Father's Full Name<span id="required_field">*</span></span>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                    <input type="checkbox" name="father_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="father_contact_no" {{ $value('father_is_deceased') ? 'checked' : '' }}>
                    Deceased
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Last Name<span id="required_field">*</span></label>
                    <input type="text" name="father_lname" placeholder="Last Name" value="{{ $value('father_lname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Given Name<span id="required_field">*</span></label>
                    <input type="text" name="father_fname" placeholder="Given Name" value="{{ $value('father_fname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Middle Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="father_mname" placeholder="Middle Name" value="{{ $value('father_mname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Suffix <span class="optional-field">(Optional)</span></label>
                    <select name="father_suffix" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        <option value="">None</option>
                        @foreach ($suffixOptions as $suffixOption)
                            <option value="{{ $suffixOption }}" {{ $value('father_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Contact No. <span class="optional-field">(Optional)</span></label>
                    <input type="tel" name="father_contact_no" placeholder="+639XXXXXXXXX" value="{{ $value('father_contact_no') }}" maxlength="13" inputmode="numeric" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>

            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <span class="block text-xs font-semibold text-gray-500">Mother's Full Name<span id="required_field">*</span></span>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                    <input type="checkbox" name="mother_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="mother_contact_no" {{ $value('mother_is_deceased') ? 'checked' : '' }}>
                    Deceased
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Last Name<span id="required_field">*</span></label>
                    <input type="text" name="mother_lname" placeholder="Last Name" value="{{ $value('mother_lname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Given Name<span id="required_field">*</span></label>
                    <input type="text" name="mother_fname" placeholder="Given Name" value="{{ $value('mother_fname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Middle Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="mother_mname" placeholder="Middle Name" value="{{ $value('mother_mname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Suffix <span class="optional-field">(Optional)</span></label>
                    <select name="mother_suffix" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        <option value="">None</option>
                        @foreach ($suffixOptions as $suffixOption)
                            <option value="{{ $suffixOption }}" {{ $value('mother_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Contact No. <span class="optional-field">(Optional)</span></label>
                    <input type="tel" name="mother_contact_no" placeholder="+639XXXXXXXXX" value="{{ $value('mother_contact_no') }}" maxlength="13" inputmode="numeric" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>

            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <span class="block text-xs font-semibold text-gray-500">Guardian's Full Name <span class="optional-field">(Optional)</span></span>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                    <input type="checkbox" name="guardian_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="guardian_contact_no" {{ $value('guardian_is_deceased') ? 'checked' : '' }}>
                    Deceased
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Last Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="guardian_lname" placeholder="Last Name" value="{{ $value('guardian_lname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Given Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="guardian_fname" placeholder="Given Name" value="{{ $value('guardian_fname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Middle Name <span class="optional-field">(Optional)</span></label>
                    <input type="text" name="guardian_mname" placeholder="Middle Name" value="{{ $value('guardian_mname') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize autocapitalize="words">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Suffix <span class="optional-field">(Optional)</span></label>
                    <select name="guardian_suffix" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        <option value="">None</option>
                        @foreach ($suffixOptions as $suffixOption)
                            <option value="{{ $suffixOption }}" {{ $value('guardian_suffix') === $suffixOption ? 'selected' : '' }}>{{ $suffixOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Contact No. <span class="optional-field">(Optional)</span></label>
                    <input type="tel" name="guardian_contact_no" placeholder="+639XXXXXXXXX" value="{{ $value('guardian_contact_no') }}" maxlength="13" inputmode="numeric" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>
        </div>

        <div id="educational-background" class="enrollment-section p-8" data-step-section>
            <h3 class="enrollment-section-title">04. Educational Background</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Last School Attended<span id="required_field">*</span></label>
                <input type="text" name="last_school_attended" placeholder="Enter full name of last school attended" value="{{ $value('last_school_attended') }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-capitalize required autocapitalize="words">
            </div>
        </div>

        @if ($isGuidanceEdit)
            </div>
        </div>
        @endif

        <div class="{{ $isGuidanceEdit ? 'guidance-edit-actions' : 'border-t border-gray-200 bg-gray-50 px-7 py-6 md:px-9' }} flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ $cancelUrl }}" class="inline-flex items-center justify-center rounded-md bg-gray-300 px-8 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-400">
                Cancel
            </a>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" id="wizardBack" class="{{ $isGuidanceEdit ? 'hidden' : 'inline-flex' }} items-center justify-center rounded-md bg-gray-300 px-8 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-400">
                    Back
                </button>
                <button type="button" id="wizardNext" class="{{ $isGuidanceEdit ? 'hidden' : 'inline-flex' }} items-center justify-center rounded-md px-9 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
                    Next
                </button>
                <button type="submit" id="wizardSubmit" class="{{ $isGuidanceEdit ? 'inline-flex' : 'hidden' }} items-center justify-center gap-2 rounded-md px-9 py-3 text-sm font-bold uppercase tracking-wider text-white shadow-md transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60" style="background-color: #4bb878;" {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                    {{ $submitLabel ?? 'Submit Enrollment' }}
                </button>
            </div>
        </div>
    </form>
    @endif
</div>

<script>
    (function () {
    const enrollmentForm = document.getElementById('enrollmentForm');
    if (!enrollmentForm) {
        return;
    }

    const gradeLevelSelect = document.getElementById('grade_level');
    const semesterContainer = document.getElementById('semester_container');
    const clusterContainer = document.getElementById('cluster_container');
    const preferredCourseContainer = document.getElementById('preferred_course_container');
    const clusterSelect = document.getElementById('cluster_ID');
    const preferredCourseSelect = document.getElementById('course_ID');
    const selectedPreferredCourse = @json((string) $value('course_ID'));
    const preferredCoursesByCluster = @json($preferredCoursesByCluster);
    const learnerDetails = document.getElementById('learner_details_container');
    const lastGradeCompletedSelect = document.getElementById('last_grade_level_completed');
    const lastSchoolYearCompleted = document.getElementById('last_school_year_completed');
    const contactNumberInput = document.querySelector('input[name="contact_no"]');
    const contactNumberInputs = Array.from(document.querySelectorAll('input[name="contact_no"], input[name="father_contact_no"], input[name="mother_contact_no"], input[name="guardian_contact_no"]'));
    const previousSchoolIdInput = document.querySelector('input[name="school_id_from_previous_school"]');
    const lrnInput = document.getElementById('lrn_input') || document.querySelector('input[name="LRN"]');
    const lrnFeedback = document.getElementById('lrn_feedback');
    const lrnCheckUrl = @json($checkLrnRoute ?? ($isPublicEnrollment ? route('register.check-lrn') : null));
    const ignoreStudentId = @json($ignoreStudentId ?? null);
    const emailInput = document.getElementById('email_input') || document.querySelector('input[name="email"]');
    const emailFeedback = document.getElementById('email_feedback');
    const emailCheckUrl = @json($checkEmailRoute ?? ($isPublicEnrollment ? route('register.check-email') : null));
    const ipDetails = document.getElementById('ip_details_container');
    const fourPsDetails = document.getElementById('four_ps_details_container');
    const pwdDetails = document.getElementById('pwd_details_container');
    const sameAddressCheckbox = document.getElementById('same_address');
    const sectionLinks = Array.from(document.querySelectorAll('.enrollment-step'));
    const formSections = Array.from(document.querySelectorAll('[data-step-section]'));
    const backButton = document.getElementById('wizardBack');
    const nextButton = document.getElementById('wizardNext');
    const submitButton = document.getElementById('wizardSubmit');
    const isSinglePageEdit = @json($isGuidanceEdit);
    const draftKey = `enrollment-form-draft:${enrollmentForm.action}`;
    const addressDataBaseUrl = @json(asset('data/addresspinas'));
    let activeStep = 0;
    let isSubmitting = false;
    let availableLrn = null;
    let availableEmail = null;

    const serverErrorFields = @json($errors->keys());

    function invalidTargets(field) {
        if (!field) {
            return [];
        }

        if (field.type === 'radio' || field.type === 'checkbox') {
            return Array.from(enrollmentForm.querySelectorAll(`[name="${CSS.escape(field.name)}"]`));
        }

        return [field];
    }

    function setFieldInvalid(field, invalid = true) {
        invalidTargets(field).forEach((target) => {
            target.classList.toggle('field-invalid', invalid);
            target.closest('.choice-group')?.classList.toggle('field-invalid', invalid);
        });
    }

    function markServerErrors() {
        serverErrorFields.forEach((name) => {
            const field = enrollmentForm.querySelector(`[name="${CSS.escape(name)}"]`)
                || document.getElementById(`${name}_select`);
            setFieldInvalid(field);
        });
    }

    if (isSinglePageEdit) {
        document.body.classList.add('sidebar-collapsed');
    }
    const currentFields = {
        curr_house_no: document.querySelector('input[name="curr_house_no"]'),
        curr_street_name: document.querySelector('input[name="curr_street_name"]'),
        curr_barangay: document.querySelector('input[name="curr_barangay"]'),
        curr_municipality_city: document.querySelector('input[name="curr_municipality_city"]'),
        curr_province: document.querySelector('input[name="curr_province"]'),
        curr_country: document.querySelector('input[name="curr_country"]'),
        curr_zip_code: document.querySelector('input[name="curr_zip_code"]'),
    };
    const permanentFields = {
        perm_house_no: document.querySelector('input[name="perm_house_no"]'),
        perm_street_name: document.querySelector('input[name="perm_street_name"]'),
        perm_barangay: document.querySelector('input[name="perm_barangay"]'),
        perm_municipality_city: document.querySelector('input[name="perm_municipality_city"]'),
        perm_province: document.querySelector('input[name="perm_province"]'),
        perm_country: document.querySelector('input[name="perm_country"]'),
        perm_zip_code: document.querySelector('input[name="perm_zip_code"]'),
    };
    const addressControls = {
        curr: {
            provinceSelect: document.getElementById('curr_province_select'),
            municipalitySelect: document.getElementById('curr_municipality_city_select'),
            barangaySelect: document.getElementById('curr_barangay_select'),
            provinceInput: currentFields.curr_province,
            municipalityInput: currentFields.curr_municipality_city,
            barangayInput: currentFields.curr_barangay,
            zipInput: currentFields.curr_zip_code,
        },
        perm: {
            provinceSelect: document.getElementById('perm_province_select'),
            municipalitySelect: document.getElementById('perm_municipality_city_select'),
            barangaySelect: document.getElementById('perm_barangay_select'),
            provinceInput: permanentFields.perm_province,
            municipalityInput: permanentFields.perm_municipality_city,
            barangayInput: permanentFields.perm_barangay,
            zipInput: permanentFields.perm_zip_code,
        },
    };
    const addressData = {
        regions: [],
        provinces: [],
        municipalities: [],
        barangays: [],
        zipcodes: {},
        zipEntries: [],
    };

    function updatePreferredCourses() {
        if (!preferredCourseSelect || !clusterSelect) {
            return;
        }

        const currentValue = preferredCourseSelect.value || selectedPreferredCourse;
        const courses = preferredCoursesByCluster[clusterSelect.value] || [];
        preferredCourseSelect.innerHTML = '<option value="">Select Preferred Course</option>';

        courses.forEach((course) => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            option.selected = course.id === currentValue;
            preferredCourseSelect.appendChild(option);
        });
    }

    function toggleSeniorFields() {
        const isSenior = ['11', '12'].includes(gradeLevelSelect?.value);
        semesterContainer.classList.toggle('hidden', !isSenior);
        clusterContainer.classList.toggle('hidden', !isSenior);
        preferredCourseContainer.classList.toggle('hidden', !isSenior);
        const semesterInputs = document.querySelectorAll('input[name="semester"]');

        if (clusterSelect) {
            clusterSelect.required = isSenior;
        }
        if (preferredCourseSelect) {
            preferredCourseSelect.required = isSenior;
        }
        semesterInputs.forEach((input) => {
            input.required = isSenior;
        });
        if (!isSenior) {
            semesterInputs.forEach((input) => {
                input.checked = false;
            });
            if (clusterSelect) {
                clusterSelect.value = '';
            }
            if (preferredCourseSelect) {
                preferredCourseSelect.value = '';
            }
        } else {
            updatePreferredCourses();
        }
    }

    function syncLastGradeOptions() {
        if (!gradeLevelSelect || !lastGradeCompletedSelect) {
            return;
        }

        const targetGrade = Number.parseInt(gradeLevelSelect.value, 10);
        Array.from(lastGradeCompletedSelect.options).forEach((option) => {
            if (!option.value) {
                option.disabled = false;
                option.hidden = false;
                return;
            }

            const optionGrade = Number.parseInt(option.value, 10);
            const isCurrentOrHigher = Number.isFinite(targetGrade) && optionGrade >= targetGrade;
            const showForGrade = option.dataset.showForGrade;
            const isRestrictedToOtherGrade = Boolean(showForGrade) && String(targetGrade) !== showForGrade;
            const shouldHide = isCurrentOrHigher || isRestrictedToOtherGrade;
            option.disabled = shouldHide;
            option.hidden = shouldHide;
        });

        const selectedGrade = Number.parseInt(lastGradeCompletedSelect.value, 10);
        const selectedOption = lastGradeCompletedSelect.selectedOptions[0];
        const selectedShowForGrade = selectedOption?.dataset.showForGrade;
        if (
            lastGradeCompletedSelect.value
            && (
                (Number.isFinite(targetGrade) && selectedGrade >= targetGrade)
                || (selectedShowForGrade && String(targetGrade) !== selectedShowForGrade)
            )
        ) {
            lastGradeCompletedSelect.value = '';
        }
    }

    function capitalizeWords(value) {
        return value.replace(/(\p{L})(\p{L}*)/gu, (_, first, rest) => {
            return first.toLocaleUpperCase('en-US') + rest.toLocaleLowerCase('en-US');
        });
    }

    function capitalizeTextInput(input) {
        if (!input) {
            return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.value = capitalizeWords(input.value);

        if (document.activeElement === input && start !== null && end !== null) {
            input.setSelectionRange(start, end);
        }
    }

    function restrictUnsafeCharacters(input) {
        if (!input || input.readOnly) {
            return;
        }

        const sanitized = String(input.value || '').replace(/[<>]/g, '');
        if (sanitized === input.value) {
            return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.value = sanitized;

        if (document.activeElement === input && start !== null && end !== null) {
            const cursor = Math.max(0, start - 1);
            input.setSelectionRange(cursor, Math.max(0, (end ?? start) - 1));
        }
    }

    function restrictUnsafeFormInputs(form) {
        form.querySelectorAll('input, textarea').forEach((input) => {
            const type = (input.getAttribute('type') || 'text').toLowerCase();
            if (['hidden', 'checkbox', 'radio', 'date', 'file', 'submit', 'button'].includes(type)) {
                return;
            }

            input.addEventListener('keydown', (event) => {
                if (event.key === '<' || event.key === '>') {
                    event.preventDefault();
                }
            });
            input.addEventListener('input', () => restrictUnsafeCharacters(input));

            if (type === 'text' && !input.hasAttribute('pattern')) {
                input.setAttribute('pattern', '[^<>]*');
                input.setAttribute('title', 'Characters < and > are not allowed.');
            }
        });
    }

    function bindDeceasedToggles(form) {
        form.querySelectorAll('[data-deceased-toggle]').forEach((checkbox) => {
            const contact = form.querySelector(`[name="${checkbox.getAttribute('data-deceased-toggle')}"]`);
            if (!contact) {
                return;
            }

            const sync = () => {
                contact.disabled = checkbox.checked;
                if (checkbox.checked) {
                    contact.value = '';
                }
            };

            checkbox.addEventListener('change', sync);
            sync();
        });
    }

    function subscriberDigitsFromContact(value) {
        const trimmed = String(value || '').trim();

        if (
            trimmed === ''
            || trimmed === '+'
            || trimmed === '+6'
            || trimmed === '+63'
            || trimmed === '6'
            || trimmed === '63'
        ) {
            return '';
        }

        let digits = trimmed.replace(/\D/g, '');

        if (digits.startsWith('63')) {
            digits = digits.slice(2);
        }

        if (digits.startsWith('0')) {
            digits = digits.slice(1);
        }

        return digits.slice(0, 10);
    }

    function setContactValidity(input, digits) {
        if (digits === '') {
            input.setCustomValidity(input.required ? 'Must enter 10 digits.' : '');
            return;
        }

        input.setCustomValidity(digits.length === 10 ? '' : 'Must enter 10 digits.');
    }

    function normalizePhilippineContactNumber(input) {
        if (!input) {
            return;
        }

        const digits = subscriberDigitsFromContact(input.value);

        if (digits === '') {
            input.value = '';
            setContactValidity(input, digits);
            return;
        }

        input.value = `+63${digits}`;
        setContactValidity(input, digits);
    }

    function normalizeAddressText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\([^)]*\)/g, '')
            .replace(/^city of\s+/i, '')
            .replace(/\bcity\b/gi, '')
            .replace(/[^a-z0-9]+/gi, ' ')
            .trim()
            .toLowerCase();
    }

    function makeOption(value, label) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;

        return option;
    }

    function populateSelect(select, placeholder, items, valueKey = 'value', labelKey = 'name') {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(makeOption('', placeholder));
        items.forEach((item) => {
            select.appendChild(makeOption(item[valueKey], item[labelKey]));
        });
        select.disabled = items.length === 0;
    }

    function selectedOptionText(select) {
        if (!select?.value) {
            return '';
        }

        return select.selectedOptions?.[0]?.textContent || '';
    }

    function findZipCode(municipalityName, barangayName) {
        const municipality = normalizeAddressText(municipalityName);
        const barangay = normalizeAddressText(barangayName);

        const municipalityMatch = addressData.zipEntries.find((entry) => {
            return entry.places.some((place) => {
                const normalized = normalizeAddressText(place);

                return normalized === municipality || normalized.includes(municipality) || municipality.includes(normalized);
            });
        });

        if (municipalityMatch) {
            return municipalityMatch.zip;
        }

        if (!barangay) {
            return '';
        }

        const barangayMatch = addressData.zipEntries.find((entry) => {
            return entry.places.some((place) => normalizeAddressText(place).includes(barangay));
        });

        return barangayMatch?.zip || '';
    }

    function updateAddressZip(prefix) {
        const controls = addressControls[prefix];
        if (!controls?.zipInput) {
            return;
        }

        controls.zipInput.value = findZipCode(
            controls.municipalityInput?.value,
            controls.barangayInput?.value
        );
    }

    function syncAddressValues(prefix) {
        const controls = addressControls[prefix];
        if (!controls) {
            return;
        }

        if (controls.provinceInput) {
            controls.provinceInput.value = selectedOptionText(controls.provinceSelect);
        }
        if (controls.municipalityInput) {
            controls.municipalityInput.value = selectedOptionText(controls.municipalitySelect);
        }
        if (controls.barangayInput) {
            controls.barangayInput.value = selectedOptionText(controls.barangaySelect);
        }

        updateAddressZip(prefix);
    }

    function fillMunicipalities(prefix, selectedMunicipality = '') {
        const controls = addressControls[prefix];
        const provinceCode = controls?.provinceSelect?.value || '';
        const municipalities = provinceCode
            ? addressData.municipalities
                .filter((municipality) => municipality.prov_code === provinceCode)
                .sort((a, b) => a.name.localeCompare(b.name))
            : [];

        populateSelect(controls?.municipalitySelect, 'Select municipality / city', municipalities, 'mun_code');
        if (selectedMunicipality) {
            const match = municipalities.find((municipality) => municipality.name === selectedMunicipality);
            controls.municipalitySelect.value = match?.mun_code || '';
        }
    }

    function fillBarangays(prefix, selectedBarangay = '') {
        const controls = addressControls[prefix];
        const municipalityCode = controls?.municipalitySelect?.value || '';
        const barangays = municipalityCode
            ? addressData.barangays
                .filter((barangay) => barangay.mun_code === municipalityCode)
                .sort((a, b) => a.name.localeCompare(b.name))
            : [];

        populateSelect(controls?.barangaySelect, 'Select barangay', barangays, 'name');
        if (selectedBarangay) {
            controls.barangaySelect.value = selectedBarangay;
        }
    }

    function syncAddressSelectsFromValues(prefix) {
        const controls = addressControls[prefix];
        if (!controls?.provinceSelect) {
            return;
        }

        const selectedProvince = controls.provinceInput?.value || '';
        const province = addressData.provinces.find((item) => item.name === selectedProvince);
        controls.provinceSelect.value = province?.prov_code || '';
        fillMunicipalities(prefix, controls.municipalityInput?.value || '');
        fillBarangays(prefix, controls.barangayInput?.value || '');
        updateAddressZip(prefix);
    }

    function resetAddressBelow(prefix, role) {
        const controls = addressControls[prefix];

        if (role === 'province') {
            if (controls.municipalityInput) {
                controls.municipalityInput.value = '';
            }
            if (controls.barangayInput) {
                controls.barangayInput.value = '';
            }
            if (controls.zipInput) {
                controls.zipInput.value = '';
            }
            populateSelect(controls.municipalitySelect, 'Select municipality / city', [], 'mun_code');
            populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
        }

        if (role === 'province' || role === 'municipality') {
            if (controls.barangayInput) {
                controls.barangayInput.value = '';
            }
            if (controls.zipInput) {
                controls.zipInput.value = '';
            }
            populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
        }
    }

    async function initializeAddressSelectors() {
        if (!addressControls.curr.provinceSelect || !addressControls.perm.provinceSelect) {
            return;
        }

        try {
            const [provinces, municipalities, barangays, zipcodes] = await Promise.all([
                fetch(`${addressDataBaseUrl}/provinces.json`).then((response) => response.json()),
                fetch(`${addressDataBaseUrl}/city-mun.json`).then((response) => response.json()),
                fetch(`${addressDataBaseUrl}/barangays.json`).then((response) => response.json()),
                fetch(`${addressDataBaseUrl}/zipcodes.json`).then((response) => response.json()),
            ]);

            addressData.provinces = provinces.sort((a, b) => a.name.localeCompare(b.name));
            addressData.municipalities = municipalities;
            addressData.barangays = barangays;
            addressData.zipcodes = zipcodes;
            addressData.zipEntries = Object.entries(zipcodes).map(([zip, places]) => ({
                zip,
                places: Array.isArray(places) ? places : [places],
            }));

            Object.values(addressControls).forEach((controls) => {
                populateSelect(controls.provinceSelect, 'Select province', addressData.provinces, 'prov_code');
                populateSelect(controls.municipalitySelect, 'Select municipality / city', [], 'mun_code');
                populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
            });

            syncAddressSelectsFromValues('curr');
            syncAddressSelectsFromValues('perm');
            copyCurrentToPermanent();
        } catch (error) {
            Object.values(addressControls).forEach((controls) => {
                [controls.provinceSelect, controls.municipalitySelect, controls.barangaySelect].forEach((select) => {
                    if (select) {
                        select.innerHTML = '';
                        select.appendChild(makeOption('', 'Address list unavailable'));
                        select.disabled = true;
                    }
                });
            });
        }
    }

    function validateLastSchoolYear() {
        lastSchoolYearCompleted?.setCustomValidity('');
    }

    function toggleLearnerDetails() {
        const learnerType = document.querySelector('input[name="learner_type"]:checked');
        const showDetails = learnerType && ['transferee', 'balik_aral', 'returnee'].includes(learnerType.value);
        learnerDetails.classList.toggle('hidden', !showDetails);
        learnerDetails.querySelectorAll('input, select').forEach((field) => {
            field.disabled = !showDetails;
            field.required = showDetails && field.type !== 'hidden';
        });
        validateLastSchoolYear();
    }

    function toggleYesNoDetails(name, container) {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        container.classList.toggle('hidden', !selected || selected.value !== 'Yes');
        container.querySelectorAll('input').forEach((input) => {
            input.required = selected && selected.value === 'Yes';
        });
    }

    function copyCurrentToPermanent() {
        const isSameAddress = Boolean(sameAddressCheckbox?.checked);

        if (!isSameAddress) {
            [permanentFields.perm_house_no, permanentFields.perm_street_name].forEach((field) => {
                field?.removeAttribute('readonly');
            });
            syncAddressSelectsFromValues('perm');
            if (addressControls.perm.provinceSelect) {
                addressControls.perm.provinceSelect.disabled = addressData.provinces.length === 0;
            }
            return;
        }

        Object.keys(currentFields).forEach((key) => {
            const currentField = currentFields[key];
            const permKey = key.replace('curr_', 'perm_');
            const permField = permanentFields[permKey];
            if (currentField && permField) {
                permField.value = currentField.value;
            }
        });

        [permanentFields.perm_house_no, permanentFields.perm_street_name].forEach((field) => {
            field?.setAttribute('readonly', 'readonly');
        });
        syncAddressSelectsFromValues('perm');
        ['provinceSelect', 'municipalitySelect', 'barangaySelect'].forEach((key) => {
            if (addressControls.perm[key]) {
                addressControls.perm[key].disabled = true;
            }
        });
    }

    function setLrnFeedback(message = '') {
        if (!lrnFeedback) {
            return;
        }

        lrnFeedback.textContent = message;
        lrnFeedback.classList.toggle('hidden', message === '');
    }

    function normalizeLrnInput() {
        if (!lrnInput || lrnInput.disabled) {
            return;
        }

        lrnInput.value = lrnInput.value.replace(/\D/g, '').slice(0, 12);
    }

    async function ensureLrnAvailable() {
        if (!lrnInput || lrnInput.disabled || !lrnCheckUrl) {
            return true;
        }

        normalizeLrnInput();
        lrnInput.setCustomValidity('');
        setLrnFeedback('');

        if (!lrnInput.checkValidity()) {
            lrnInput.reportValidity();
            return false;
        }

        if (availableLrn === lrnInput.value) {
            return true;
        }

        try {
            const params = { LRN: lrnInput.value };
            if (ignoreStudentId) {
                params.ignore_student = ignoreStudentId;
            }
            const response = await fetch(`${lrnCheckUrl}?${new URLSearchParams(params)}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message
                    || data.errors?.LRN?.[0]
                    || 'Unable to verify LRN availability. Please try again.';
                lrnInput.setCustomValidity(message);
                setLrnFeedback(message);
                lrnInput.reportValidity();
                return false;
            }

            if (!data.available) {
                const message = data.message || 'This LRN is already registered.';
                lrnInput.setCustomValidity(message);
                setLrnFeedback(message);
                lrnInput.reportValidity();
                return false;
            }

            availableLrn = lrnInput.value;
            return true;
        } catch (error) {
            const message = 'Unable to verify LRN availability. Please try again.';
            lrnInput.setCustomValidity(message);
            setLrnFeedback(message);
            lrnInput.reportValidity();
            return false;
        }
    }

    function setEmailFeedback(message = '') {
        if (!emailFeedback) {
            return;
        }

        emailFeedback.textContent = message;
        emailFeedback.classList.toggle('hidden', message === '');
    }

    async function ensureEmailAvailable() {
        if (!emailInput || emailInput.disabled || !emailCheckUrl) {
            return true;
        }

        emailInput.setCustomValidity('');
        setEmailFeedback('');

        if (!emailInput.checkValidity()) {
            emailInput.reportValidity();
            return false;
        }

        const email = emailInput.value.trim();

        if (availableEmail === email) {
            return true;
        }

        try {
            const params = { email };
            if (ignoreStudentId) {
                params.ignore_student = ignoreStudentId;
            }
            const response = await fetch(`${emailCheckUrl}?${new URLSearchParams(params)}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message
                    || data.errors?.email?.[0]
                    || 'Unable to verify email availability. Please try again.';
                emailInput.setCustomValidity(message);
                setEmailFeedback(message);
                emailInput.reportValidity();
                return false;
            }

            if (!data.available) {
                const message = data.message || 'This email is already registered.';
                emailInput.setCustomValidity(message);
                setEmailFeedback(message);
                emailInput.reportValidity();
                return false;
            }

            availableEmail = email;
            return true;
        } catch (error) {
            const message = 'Unable to verify email availability. Please try again.';
            emailInput.setCustomValidity(message);
            setEmailFeedback(message);
            emailInput.reportValidity();
            return false;
        }
    }

    function setActiveStep(stepIndex, { scrollToTop = false } = {}) {
        activeStep = Math.max(0, Math.min(stepIndex, formSections.length - 1));

        if (isSinglePageEdit) {
            return;
        }

        formSections.forEach((section, index) => {
            section.classList.toggle('active', index === activeStep);
        });

        sectionLinks.forEach((link, index) => {
            const isActive = index === activeStep;
            const isDone = index < activeStep;
            const dot = link.querySelector('.enrollment-step-dot');

            link.classList.toggle('active', isActive);
            link.classList.toggle('done', isDone);
            if (dot) {
                dot.innerHTML = isActive || isDone ? '&#10003;' : '';
            }
        });

        if (backButton) {
            backButton.classList.toggle('invisible', activeStep === 0);
        }
        if (nextButton) {
            nextButton.classList.toggle('hidden', activeStep === formSections.length - 1);
        }
        if (submitButton) {
            submitButton.classList.toggle('hidden', activeStep !== formSections.length - 1);
        }

        if (scrollToTop && window.matchMedia('(max-width: 640px)').matches) {
            formSections[activeStep]?.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start',
            });
        }
    }

    async function validateStep(stepIndex) {
        const section = formSections[stepIndex];

        if (!section) {
            return true;
        }

        setActiveStep(stepIndex);
        const fields = Array.from(section.querySelectorAll('input, select, textarea'))
            .filter((field) => !field.disabled);

        const invalidFields = fields.filter((field) => !field.checkValidity());
        invalidFields.forEach((field) => setFieldInvalid(field));

        if (invalidFields.length) {
            invalidFields[0].reportValidity();
            return false;
        }

        if (stepIndex === 0 && !(await ensureLrnAvailable())) {
            return false;
        }

        if (stepIndex === 1 && !(await ensureEmailAvailable())) {
            return false;
        }

        return true;
    }

    async function validateThrough(targetStep) {
        const endStep = Math.max(0, Math.min(targetStep, formSections.length - 1));

        for (let index = 0; index <= endStep; index += 1) {
            if (!(await validateStep(index))) {
                return false;
            }
        }

        return true;
    }

    function clearFormDraft() {
        try {
            sessionStorage.removeItem(draftKey);
        } catch (error) {
            // Ignore storage access errors in private browsing contexts.
        }
    }

    gradeLevelSelect?.addEventListener('change', () => {
        toggleSeniorFields();
        syncLastGradeOptions();
    });
    clusterSelect?.addEventListener('change', () => {
        if (preferredCourseSelect) {
            preferredCourseSelect.value = '';
        }
        updatePreferredCourses();
    });
    document.querySelectorAll('input[name="learner_type"]').forEach((input) => {
        input.addEventListener('change', toggleLearnerDetails);
    });
    document.querySelectorAll('input[name="ip_community"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('ip_community', ipDetails));
    });
    document.querySelectorAll('input[name="four_ps_beneficiary"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('four_ps_beneficiary', fourPsDetails));
    });
    document.querySelectorAll('input[name="pwd"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('pwd', pwdDetails));
    });
    sameAddressCheckbox?.addEventListener('change', copyCurrentToPermanent);
    Object.entries(addressControls).forEach(([prefix, controls]) => {
        controls.provinceSelect?.addEventListener('change', () => {
            resetAddressBelow(prefix, 'province');
            fillMunicipalities(prefix);
            syncAddressValues(prefix);
            copyCurrentToPermanent();
        });
        controls.municipalitySelect?.addEventListener('change', () => {
            resetAddressBelow(prefix, 'municipality');
            fillBarangays(prefix);
            syncAddressValues(prefix);
            copyCurrentToPermanent();
        });
        controls.barangaySelect?.addEventListener('change', () => {
            syncAddressValues(prefix);
            copyCurrentToPermanent();
        });
    });
    lastSchoolYearCompleted?.addEventListener('change', validateLastSchoolYear);
    contactNumberInputs.forEach((input) => {
        input.addEventListener('focus', () => {
            if (input.required && input.value.trim() === '') {
                input.value = '+63';
            }
        });
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Backspace' && event.key !== 'Delete') {
                return;
            }

            const value = input.value.trim();
            if (value === '+63' || value === '+6' || value === '+' || value === '63' || value === '6') {
                event.preventDefault();
                input.value = '';
                setContactValidity(input, '');
            }
        });
        input.addEventListener('input', () => normalizePhilippineContactNumber(input));
        input.addEventListener('blur', () => {
            const digits = subscriberDigitsFromContact(input.value);
            if (digits === '') {
                input.value = '';
                setContactValidity(input, '');
                return;
            }

            normalizePhilippineContactNumber(input);
        });
    });
    previousSchoolIdInput?.addEventListener('input', () => {
        previousSchoolIdInput.value = previousSchoolIdInput.value.replace(/\D/g, '').slice(0, 6);
    });
    lrnInput?.addEventListener('input', () => {
        normalizeLrnInput();
        availableLrn = null;
        lrnInput.setCustomValidity('');
        setLrnFeedback('');
    });
    lrnInput?.addEventListener('keypress', (event) => {
        if (!/\d/.test(event.key) && !event.ctrlKey && !event.metaKey && event.key.length === 1) {
            event.preventDefault();
        }
    });
    lrnInput?.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = (event.clipboardData || window.clipboardData).getData('text') || '';
        lrnInput.value = pasted.replace(/\D/g, '').slice(0, 12);
        availableLrn = null;
        lrnInput.setCustomValidity('');
        setLrnFeedback('');
        lrnInput.dispatchEvent(new Event('input', { bubbles: true }));
    });
    emailInput?.addEventListener('input', () => {
        availableEmail = null;
        emailInput.setCustomValidity('');
        setEmailFeedback('');
    });
    emailInput?.addEventListener('blur', () => {
        if (emailInput.value.trim() === '' || !emailInput.checkValidity()) {
            return;
        }

        ensureEmailAvailable();
    });
    Object.values(currentFields).forEach((field) => {
        field?.addEventListener('input', copyCurrentToPermanent);
    });
    backButton?.addEventListener('click', () => {
        setActiveStep(activeStep - 1, { scrollToTop: true });
    });
    nextButton?.addEventListener('click', async () => {
        if (await validateStep(activeStep)) {
            setActiveStep(activeStep + 1, { scrollToTop: true });
        }
    });
    sectionLinks.forEach((link, index) => {
        link.addEventListener('click', async () => {
            if (index <= activeStep || await validateThrough(index - 1)) {
                setActiveStep(index, { scrollToTop: true });
            }
        });
    });

    if (isSinglePageEdit && 'IntersectionObserver' in window) {
        const editNavigation = Array.from(document.querySelectorAll('[data-guidance-edit-nav]'));
        const navigationBySection = new Map(editNavigation.map((link) => [link.dataset.guidanceEditNav, link]));
        const setActiveEditNavigation = (sectionId) => {
            editNavigation.forEach((link) => {
                link.classList.toggle('is-active', link.dataset.guidanceEditNav === sectionId);
            });
        };

        editNavigation.forEach((link) => {
            link.addEventListener('click', (event) => {
                const section = document.getElementById(link.dataset.guidanceEditNav);
                if (!section) {
                    return;
                }

                event.preventDefault();
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setActiveEditNavigation(section.id);
                window.history.replaceState(null, '', `#${section.id}`);
            });
        });

        const observer = new IntersectionObserver((entries) => {
            const visibleSection = entries
                .filter((entry) => entry.isIntersecting)
                .sort((first, second) => second.intersectionRatio - first.intersectionRatio)[0];

            if (visibleSection?.target?.id && navigationBySection.has(visibleSection.target.id)) {
                setActiveEditNavigation(visibleSection.target.id);
            }
        }, { rootMargin: '-20% 0px -65% 0px', threshold: [0.05, 0.25, 0.5] });

        formSections.forEach((section) => observer.observe(section));
    }
    enrollmentForm.addEventListener('submit', async (event) => {
        if (isSubmitting) {
            return;
        }

        event.preventDefault();
        validateLastSchoolYear();
        if (!(await validateThrough(formSections.length - 1))) {
            return;
        }

        clearFormDraft();
        isSubmitting = true;
        HTMLFormElement.prototype.submit.call(enrollmentForm);
    });

    enrollmentForm.querySelectorAll('input, select, textarea').forEach((field) => {
        const clearInvalidState = () => {
            if (field.checkValidity()) {
                setFieldInvalid(field, false);
            }
        };

        field.addEventListener('input', clearInvalidState);
        field.addEventListener('change', clearInvalidState);
    });

    enrollmentForm.addEventListener('invalid', (event) => {
        setFieldInvalid(event.target);
    }, true);

    markServerErrors();

    function setBirthdateRestriction() {
        const birthdateInput = document.getElementById('birthdateInput');
        if (!birthdateInput) return;

        const today = new Date();
        const tenYearsAgo = new Date(today.getFullYear() - 10, today.getMonth(), today.getDate());

        const year = tenYearsAgo.getFullYear();
        const month = String(tenYearsAgo.getMonth() + 1).padStart(2, '0');
        const day = String(tenYearsAgo.getDate()).padStart(2, '0');
        const latestBirthdate = `${year}-${month}-${day}`;
        const earliestBirthdate = birthdateInput.getAttribute('min') || '1950-01-01';

        birthdateInput.setAttribute('max', latestBirthdate);

        const validateBirthdate = () => {
            birthdateInput.setCustomValidity('');
            if (!birthdateInput.value) {
                return;
            }

            if (birthdateInput.value < earliestBirthdate) {
                birthdateInput.setCustomValidity('Birthdate must be on or after January 1, 1950.');
                return;
            }

            if (birthdateInput.value > latestBirthdate) {
                birthdateInput.setCustomValidity('The learner must be at least 10 years old.');
            }
        };

        birthdateInput.addEventListener('input', () => {
            validateBirthdate();
            updateAge();
        });
        birthdateInput.addEventListener('change', () => {
            validateBirthdate();
            updateAge();
            if (!birthdateInput.checkValidity()) {
                birthdateInput.reportValidity();
            }
        });
        validateBirthdate();
        updateAge();
    }

    function updateAge() {
        const birthdateInput = document.getElementById('birthdateInput');
        const ageInput = document.getElementById('ageDisplay');
        if (!birthdateInput || !ageInput) {
            return;
        }

        if (!birthdateInput.value) {
            ageInput.value = '';
            return;
        }

        const birth = new Date(`${birthdateInput.value}T00:00:00`);
        if (Number.isNaN(birth.getTime())) {
            ageInput.value = '';
            return;
        }

        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age -= 1;
        }

        ageInput.value = age >= 0 ? String(age) : '';
    }

    clearFormDraft();
    initializeAddressSelectors();
    toggleSeniorFields();
    syncLastGradeOptions();
    validateLastSchoolYear();
    contactNumberInputs.forEach((input) => normalizePhilippineContactNumber(input));
    restrictUnsafeFormInputs(enrollmentForm);
    bindDeceasedToggles(enrollmentForm);
    enrollmentForm.querySelectorAll('[data-capitalize]').forEach((input) => {
        capitalizeTextInput(input);
        input.addEventListener('input', () => capitalizeTextInput(input));
        input.addEventListener('blur', () => capitalizeTextInput(input));
    });
    toggleLearnerDetails();
    toggleYesNoDetails('ip_community', ipDetails);
    toggleYesNoDetails('four_ps_beneficiary', fourPsDetails);
    toggleYesNoDetails('pwd', pwdDetails);
    setActiveStep(0);
    setBirthdateRestriction();
    })();
</script>
@endsection
