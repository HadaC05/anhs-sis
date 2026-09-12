@extends('users.guidance.layout')

@section('title', 'Section Details')

@section('content')
@php
    $enrollments = ($section->enrollments ?? collect())
        ->sortBy(function ($enrollment) {
            $student = $enrollment->student;
            $application = $student?->application;
            $sexRank = match (strtolower((string) $student?->sex)) {
                'male' => 0,
                'female' => 1,
                default => 2,
            };

            return sprintf(
                '%d-%s-%s-%s',
                $sexRank,
                strtolower((string) ($application?->last_name ?? '')),
                strtolower((string) ($application?->first_name ?? '')),
                strtolower((string) ($student?->lrn ?? ''))
            );
        })
        ->values();
    $cap = (int) ($section->capacity ?? 0);
    $activeEnrollmentCount = $enrollments
        ->whereIn('enrollment_status', \App\Models\EnrollmentStatus::activeSlugs())
        ->count();
    $pct = $cap > 0 ? min(100, (int) round(100 * $activeEnrollmentCount / $cap)) : 0;
    $badgeColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
    $statusLabel = $pct >= 100 ? 'full' : ($pct >= 80 ? 'near full' : 'open');
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
    $gradeInitial = strtoupper(str_replace('grade_', 'G', $section->grade_level));
    $isSeniorHigh = in_array($section->grade_level, ['grade_11', 'grade_12'], true);
    $adviserName = $section->adviser
        ? trim($section->adviser->first_name . ' ' . $section->adviser->last_name)
        : 'No adviser';
    $metaParts = array_filter([
        $gradeLabel,
        $isSeniorHigh && $section->cluster?->name ? $section->cluster->name : null,
        $section->room ? 'Room '.$section->room : null,
        'Adviser: '.$adviserName,
        $cap > 0 ? "{$activeEnrollmentCount}/{$cap} students" : "{$activeEnrollmentCount} students",
    ]);
    $transferableStatuses = \App\Models\EnrollmentStatus::activeSlugs();
    $targetSectionOptions = $targetSections->map(function ($targetSection) {
        $targetCapacity = (int) ($targetSection->capacity ?? 0);
        $targetCount = (int) ($targetSection->active_enrollments_count ?? 0);
        $availableSlots = $targetCapacity === 0 ? null : max(0, $targetCapacity - $targetCount);
        $pct = $targetCapacity > 0 ? min(100, (int) round(100 * $targetCount / $targetCapacity)) : 0;
        $isFull = $targetCapacity > 0 && $availableSlots === 0;

        return [
            'id' => $targetSection->section_ID,
            'name' => $targetSection->name,
            'cluster' => $targetSection->cluster?->name,
            'capacity' => $targetCapacity,
            'enrolled' => $targetCount,
            'availableSlots' => $availableSlots,
            'pct' => $pct,
            'isFull' => $isFull,
            'isUnlimited' => $targetCapacity === 0,
            'barColor' => $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]'),
            'badgeClass' => $isFull ? 'border-red-200 bg-red-50 text-red-700' : ($targetCapacity === 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($availableSlots <= 3 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-700')),
            'badgeLabel' => $targetCapacity === 0 ? 'Open capacity' : ($isFull ? 'Full' : $availableSlots.' '.($availableSlots === 1 ? 'slot' : 'slots').' left'),
        ];
    })->values();
@endphp

<div class="mb-6">
    <a href="{{ route('guidance.sections.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#296374] transition hover:underline">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        Back to Sectioning
    </a>
</div>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="flex items-start gap-4">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl text-lg font-bold text-white shadow-lg" style="background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);">
            {{ $gradeInitial }}
        </span>
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">{{ $section->name }}</h1>
                <span class="rounded-full {{ $badgeColor }} px-3 py-1 text-[11px] font-bold lowercase text-white shadow-sm">{{ $statusLabel }}</span>
            </div>
            <p class="mt-2 text-xs text-gray-400">{{ implode(' · ', $metaParts) }}</p>
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-2">
        <button type="button" onclick="openSectionSettingsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:border-[#296374]/40 hover:text-[#296374]" aria-label="Edit section details">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>
        <a href="{{ route('guidance.sections.class-list', $section) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:border-[#296374]/40 hover:text-[#296374]" aria-label="Print class list">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </a>
    </div>
</div>

@if (session('status'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('status') }}
    </div>
@endif

@if (session('success'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('guidance.sections.transfer', $section) }}" id="transferStudentsForm" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    @csrf
    <input type="hidden" name="target_section_id" id="transferTargetSectionId" value="{{ old('target_section_id') }}">

    <div class="border-b border-gray-100 px-4 py-4 lg:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Students in this section</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $enrollments->count() }} enrolled · <span id="selectedTransferCount">0</span> selected</p>
            </div>

            @if ($enrollments->whereIn('enrollment_status', $transferableStatuses)->isNotEmpty() && $targetSections->isNotEmpty())
                <button type="button" id="openTransferModalButton" disabled class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold text-white transition enabled:hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-40" style="background-color: #296374;">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Transfer
                </button>
            @elseif ($enrollments->whereIn('enrollment_status', $transferableStatuses)->isNotEmpty())
                <span class="inline-flex h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-3 text-sm font-semibold text-amber-700">
                    No other same-grade sections available
                </span>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50/80 text-xs font-bold uppercase tracking-wider text-[#296374]">
                    <th class="w-12 px-4 py-3 lg:px-6">
                        <input type="checkbox" id="selectAllTransfers" class="h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]" aria-label="Select all students">
                    </th>
                    <th class="w-12 px-4 py-3 lg:px-6">#</th>
                    <th class="px-4 py-3 lg:px-6">Student name</th>
                    <th class="px-4 py-3 lg:px-6">LRN</th>
                    <th class="px-4 py-3 lg:px-6">Status</th>
                    <th class="px-4 py-3 text-right lg:px-6">Enrollment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $currentSexGroup = null;
                    $rowNumber = 0;
                @endphp
                @forelse ($enrollments as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $sexGroup = strtolower((string) $student?->sex) === 'female' ? 'Female' : (strtolower((string) $student?->sex) === 'male' ? 'Male' : 'Unspecified');
                        $enrollmentStatus = $enrollment->enrollment_status ?? '';
                        $statusClasses = match ($enrollmentStatus) {
                            'enrolled' => 'bg-emerald-100 text-emerald-700',
                            'temporarily_enrolled' => 'bg-amber-100 text-amber-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    @if ($currentSexGroup !== $sexGroup)
                        @php
                            $currentSexGroup = $sexGroup;
                        @endphp
                        <tr class="bg-[#296374]/5">
                            <td colspan="6" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-[#296374] lg:px-6">
                                {{ $sexGroup }}
                            </td>
                        </tr>
                    @endif
                    @php
                        $rowNumber++;
                    @endphp
                    <tr class="bg-white transition hover:bg-gray-50/80">
                        <td class="px-4 py-3 lg:px-6">
                            @if (in_array($enrollmentStatus, $transferableStatuses, true))
                                <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" class="transfer-checkbox h-4 w-4 rounded border-gray-300 text-[#296374] focus:ring-[#296374]" data-student-name="{{ $application ? $application->last_name . ', ' . $application->first_name : 'Student' }}" @checked(in_array($enrollment->enrollment_ID, old('enrollment_ids', []))) aria-label="Select {{ $application ? $application->last_name . ', ' . $application->first_name : 'student' }}">
                            @endif
                        </td>
                        <td class="px-4 py-3 lg:px-6">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-600">{{ $rowNumber }}</span>
                        </td>
                        <td class="px-4 py-3 lg:px-6">
                            <span class="text-sm font-semibold text-gray-800">{{ $application ? $application->last_name . ', ' . $application->first_name . ($application->middle_name ? ' '.$application->middle_name : '') : '-' }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-gray-700 lg:px-6">{{ $student?->lrn ?? '-' }}</td>
                        <td class="px-4 py-3 lg:px-6">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $statusClasses }}">{{ $enrollment->enrollment_status_label ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right lg:px-6">
                            <a href="{{ route('guidance.enrollments.show', ['enrollment' => $enrollment, 'from_section' => $section->section_ID]) }}" title="View enrollment details" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 hover:text-[#296374]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7S3.73 16.06 2.46 12z"></path></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-500">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                                <p class="font-medium text-gray-600">No students assigned yet</p>
                                <p class="text-sm">Students will appear here once they are enrolled and assigned to this section.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

@push('modals')
<div id="transferModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto flex w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div id="transferStepPick" class="flex flex-col">
            <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Section transfer</p>
                        <h3 class="mt-1 text-xl font-bold tracking-tight text-white">Move selected students</h3>
                        <p class="mt-1 text-sm text-white/80"><span id="transferPickCount">0</span> student(s) from <span class="font-semibold text-white">{{ $section->name }}</span></p>
                    </div>
                    <button type="button" onclick="closeTransferModal()" class="inline-flex h-9 w-9 items-center justify-center border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <div class="grid min-h-[420px] grid-cols-1 lg:grid-cols-2">
                <div class="min-w-0 border-b border-gray-200 bg-white lg:border-b-0 lg:border-r">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Selected students</p>
                    </div>
                    <ul id="transferSelectedPreview" class="max-h-[340px] divide-y divide-gray-100 overflow-y-auto bg-white px-5 py-2 text-sm text-gray-700"></ul>
                </div>

                <div class="min-w-0 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Destination section</p>
                    </div>
                    <div id="transferSectionOptions" class="max-h-[340px] space-y-0 overflow-y-auto">
                        @foreach ($targetSectionOptions as $option)
                            <button
                                type="button"
                                class="transfer-section-option block w-full border-b border-gray-100 px-5 py-3.5 text-left transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-45 {{ old('target_section_id') == $option['id'] ? 'is-selected border-l-4 border-l-[#296374] bg-[#296374]/5' : 'border-l-4 border-l-transparent' }}"
                                data-section-id="{{ $option['id'] }}"
                                data-section-name="{{ $option['name'] }}"
                                data-available-slots="{{ $option['isUnlimited'] ? 'unlimited' : $option['availableSlots'] }}"
                                data-is-full="{{ $option['isFull'] ? '1' : '0' }}"
                                @disabled($option['isFull'])
                            >
                                <div class="flex items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-gray-900">{{ $option['name'] }}</p>
                                        @if ($option['cluster'])
                                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $option['cluster'] }}</p>
                                        @endif
                                    </div>

                                    @if (! $option['isUnlimited'])
                                        <div class="w-24 shrink-0">
                                            <p class="mb-1 text-right text-[10px] font-bold uppercase tracking-wide text-gray-400">{{ $option['enrolled'] }}/{{ $option['capacity'] }}</p>
                                            <div class="h-1.5 bg-gray-200">
                                                <div class="h-full {{ $option['barColor'] }}" style="width: {{ $option['pct'] }}%;"></div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="shrink-0 text-xs font-medium text-gray-500">{{ $option['enrolled'] }} enrolled</p>
                                    @endif

                                    <span class="shrink-0 border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $option['badgeClass'] }}">
                                        {{ $option['badgeLabel'] }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-300 bg-gray-50 px-6 py-4">
                <p id="transferPickError" class="mb-3 hidden text-sm font-semibold text-red-600"></p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeTransferModal()" class="border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">Cancel</button>
                    <button type="button" id="transferContinueButton" class="bg-[#296374] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#1e4a57]">Continue</button>
                </div>
            </div>
        </div>

        <div id="transferStepConfirm" class="hidden">
            <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Confirmation required</p>
                <h3 class="mt-1 text-xl font-bold tracking-tight text-white">Review transfer</h3>
                <p class="mt-1 text-sm text-white/80">Verify the destination and student list before proceeding.</p>
            </div>

            <div class="grid min-h-[320px] grid-cols-1 lg:grid-cols-2">
                <div class="min-w-0 border-b border-gray-200 bg-white lg:border-b-0 lg:border-r">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Transfer summary</p>
                    </div>
                    <div class="space-y-4 px-5 py-4 text-sm text-gray-700">
                        <div class="border border-gray-200 bg-white p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Students</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900" id="transferConfirmCount">0</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="border border-gray-200 bg-white p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">From</p>
                                <p class="mt-1 font-semibold text-gray-900">{{ $section->name }}</p>
                            </div>
                            <div class="border border-gray-200 bg-white p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">To</p>
                                <p class="mt-1 font-semibold text-gray-900" id="transferConfirmTarget">—</p>
                            </div>
                        </div>
                        <p class="text-xs leading-relaxed text-gray-500">This updates each student's section assignment immediately and cannot be undone from this screen.</p>
                    </div>
                </div>

                <div class="min-w-0 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Students to transfer</p>
                    </div>
                    <ul id="transferConfirmList" class="max-h-[280px] divide-y divide-gray-100 overflow-y-auto bg-white px-5 py-2 text-sm text-gray-700"></ul>
                </div>
            </div>

            <div class="border-t border-gray-300 bg-gray-50 px-6 py-4">
                <div class="flex justify-end gap-2">
                    <button type="button" id="transferBackButton" class="border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">Back</button>
                    <button type="button" id="transferConfirmButton" class="bg-[#296374] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#1e4a57]">Confirm transfer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="sectionSettingsModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-4 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent p-6">
            <h3 class="text-xl font-bold text-gray-800">Section details</h3>
            <p class="mt-1 text-sm text-gray-500">Update room, capacity, adviser{{ $isSeniorHigh ? ', and cluster' : '' }}.</p>
        </div>
        <form id="sectionSettingsForm" class="space-y-4 p-6" action="{{ route('guidance.sections.update', $section) }}" method="POST">
            @csrf
            @method('PATCH')

            @if ($isSeniorHigh)
                <div>
                    <label for="settings_cluster_id" class="mb-1 block text-sm font-semibold text-gray-700">Cluster</label>
                    <select id="settings_cluster_id" name="cluster_ID" class="w-full rounded-lg border border-gray-300 px-4 py-2" required>
                        <option value="">Select cluster</option>
                        @foreach ($clusters ?? [] as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" {{ (string) old('cluster_ID', $section->cluster_ID) === (string) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="settings_room" class="mb-1 block text-sm font-semibold text-gray-700">Room</label>
                <input id="settings_room" name="room" type="text" value="{{ old('room', $section->room) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2" placeholder="e.g. Room 201">
            </div>

            <div>
                <label for="settings_capacity" class="mb-1 block text-sm font-semibold text-gray-700">Capacity</label>
                <input id="settings_capacity" name="capacity" type="text" inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" autocomplete="off" value="{{ old('capacity', $cap) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 {{ $errors->has('capacity') ? 'border-red-300' : '' }}" required>
                <p class="mt-1 text-xs text-gray-500">Numbers only, up to 100. Minimum {{ max(1, $activeEnrollmentCount) }} based on active enrollments.</p>
                @error('capacity')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="settings_staff_id" class="mb-1 block text-sm font-semibold text-gray-700">Adviser</label>
                <select id="settings_staff_id" name="staff_ID" class="w-full rounded-lg border border-gray-300 px-4 py-2">
                    <option value="">None</option>
                    @foreach ($staffs ?? [] as $staff)
                        <option value="{{ $staff->staff_id }}" {{ (string) old('staff_ID', $section->staff_ID) === (string) $staff->staff_id ? 'selected' : '' }}>{{ $staff->last_name }}, {{ $staff->first_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeSectionSettingsModal()" class="flex-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 rounded-lg bg-[#296374] px-4 py-2 text-white hover:bg-[#1e4a57]">Save changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSectionSettingsModal() {
        document.getElementById('sectionSettingsModal')?.classList.remove('hidden');
        document.getElementById('sectionSettingsModal')?.classList.add('flex');
    }

    function closeSectionSettingsModal() {
        document.getElementById('sectionSettingsModal')?.classList.add('hidden');
        document.getElementById('sectionSettingsModal')?.classList.remove('flex');
    }

    function getTransferCheckboxes() {
        return Array.from(document.querySelectorAll('.transfer-checkbox'));
    }

    function getSelectedTransferCheckboxes() {
        return getTransferCheckboxes().filter((checkbox) => checkbox.checked);
    }

    function getSelectedTransferSectionOption() {
        return document.querySelector('.transfer-section-option.is-selected');
    }

    function selectTransferSectionOption(option) {
        if (! option || option.disabled) {
            return;
        }

        document.querySelectorAll('.transfer-section-option').forEach((item) => {
            item.classList.remove('is-selected', 'border-l-[#296374]', 'bg-[#296374]/5');
            item.classList.add('border-l-transparent');
        });

        option.classList.remove('border-l-transparent');
        option.classList.add('is-selected', 'border-l-[#296374]', 'bg-[#296374]/5');
    }

    function openTransferModal() {
        const selected = getSelectedTransferCheckboxes();

        if (selected.length === 0) {
            return;
        }

        document.getElementById('transferPickCount').textContent = String(selected.length);
        document.getElementById('transferPickError')?.classList.add('hidden');
        document.getElementById('transferStepPick')?.classList.remove('hidden');

        const confirmStep = document.getElementById('transferStepConfirm');
        confirmStep?.classList.add('hidden');
        confirmStep?.classList.remove('flex', 'flex-col');

        const preview = document.getElementById('transferSelectedPreview');
        if (preview) {
            preview.innerHTML = selected
                .map((checkbox) => `<li class="py-2.5 font-medium">${checkbox.dataset.studentName}</li>`)
                .join('');
        }

        document.getElementById('transferModal')?.classList.remove('hidden');
        document.getElementById('transferModal')?.classList.add('flex');
    }

    function closeTransferModal() {
        document.getElementById('transferModal')?.classList.add('hidden');
        document.getElementById('transferModal')?.classList.remove('flex');
    }

    function showTransferPickStep() {
        document.getElementById('transferStepPick')?.classList.remove('hidden');
        const confirmStep = document.getElementById('transferStepConfirm');
        confirmStep?.classList.add('hidden');
        confirmStep?.classList.remove('flex', 'flex-col');
    }

    function showTransferConfirmStep() {
        const option = getSelectedTransferSectionOption();
        const error = document.getElementById('transferPickError');
        const selected = getSelectedTransferCheckboxes();

        error?.classList.add('hidden');

        if (! option) {
            if (error) {
                error.textContent = 'Please choose a destination section.';
                error.classList.remove('hidden');
            }
            return;
        }

        const availableSlots = option.dataset.availableSlots;
        if (availableSlots !== 'unlimited' && selected.length > Number(availableSlots)) {
            if (error) {
                error.textContent = `This section only has ${availableSlots} available slot(s).`;
                error.classList.remove('hidden');
            }
            return;
        }

        document.getElementById('transferConfirmCount').textContent = String(selected.length);
        document.getElementById('transferConfirmTarget').textContent = option.dataset.sectionName;

        const confirmList = document.getElementById('transferConfirmList');
        if (confirmList) {
            confirmList.innerHTML = selected
                .map((checkbox) => `<li class="py-2.5 font-medium">${checkbox.dataset.studentName}</li>`)
                .join('');
        }

        document.getElementById('transferStepPick')?.classList.add('hidden');
        const confirmStep = document.getElementById('transferStepConfirm');
        confirmStep?.classList.remove('hidden');
        confirmStep?.classList.add('flex', 'flex-col');
    }

    function submitTransferForm() {
        const option = getSelectedTransferSectionOption();

        if (! option) {
            return;
        }

        const targetInput = document.getElementById('transferTargetSectionId');
        if (targetInput) {
            targetInput.value = option.dataset.sectionId;
        }

        document.getElementById('transferStudentsForm')?.submit();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('sectionSettingsModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeSectionSettingsModal();
            }
        });

        document.getElementById('transferModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeTransferModal();
            }
        });

        const selectAll = document.getElementById('selectAllTransfers');
        const selectedCount = document.getElementById('selectedTransferCount');
        const transferButton = document.getElementById('openTransferModalButton');

        const syncSelection = () => {
            const checkboxes = getTransferCheckboxes();
            const checkedCount = getSelectedTransferCheckboxes().length;

            if (selectedCount) {
                selectedCount.textContent = String(checkedCount);
            }

            if (transferButton) {
                transferButton.disabled = checkedCount === 0;
            }

            if (! selectAll) {
                return;
            }

            selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                getTransferCheckboxes().forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                syncSelection();
            });
        }

        getTransferCheckboxes().forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelection);
        });

        syncSelection();

        transferButton?.addEventListener('click', openTransferModal);
        document.getElementById('transferContinueButton')?.addEventListener('click', showTransferConfirmStep);
        document.getElementById('transferBackButton')?.addEventListener('click', showTransferPickStep);
        document.getElementById('transferConfirmButton')?.addEventListener('click', submitTransferForm);

        document.querySelectorAll('.transfer-section-option').forEach((option) => {
            option.addEventListener('click', () => selectTransferSectionOption(option));
        });

        @if ($errors->has('capacity') || $errors->has('room') || $errors->has('staff_ID') || $errors->has('cluster_ID'))
            openSectionSettingsModal();
        @endif

        @if ($errors->has('target_section_id') || $errors->has('enrollment_ids'))
            openTransferModal();
            @if (old('target_section_id'))
                showTransferConfirmStep();
            @endif
        @endif

        function limitSectionCapacity(input) {
            if (! input) {
                return;
            }

            const digits = (input.value || '').replace(/\D+/g, '').slice(0, 3);

            if (digits === '') {
                input.value = '';
                return;
            }

            const value = Number(digits);
            input.value = value > 100 ? '100' : String(value);
        }

        const capacityInput = document.getElementById('settings_capacity');
        capacityInput?.addEventListener('input', function () {
            limitSectionCapacity(this);
        });
        capacityInput?.addEventListener('keydown', function (event) {
            if (['e', 'E', '+', '-', '.'].includes(event.key)) {
                event.preventDefault();
            }
        });
        limitSectionCapacity(capacityInput);
    });
</script>
@endpush
@endsection
