@extends('users.teacher.layout')

@section('title', 'Section Grades')

@section('content')
@php
$gradeLabel = $section->gradeLevel?->grade_label
?? ($section->grade_level ? str_replace(['grade_', '_'], ['Grade ', ' '], $section->grade_level) : '—');
$subject = $assignment->curriculumSubject?->subject;
$subjectCode = $subject?->code ?? 'SUBJ';
$subjectTitle = $subject?->title ?? 'Subject';
$subjectLabel = $subject ? ($subjectCode.' - '.$subjectTitle) : 'Subject';
$semester = $assignment->curriculumSubject?->semester;
$lockedPeriodKeys = $lockedPeriodKeys ?? [];
$studentCount = $enrollments->count();
$inputColspan = 3 + count($periods);
$summaryColspan = 4 + count($periods);
@endphp

<div class="space-y-5">
    @if (session('status'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div class="grid grid-cols-1 gap-x-10 gap-y-1 md:grid-cols-2">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">Section</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $section->name }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">Subject</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $subjectLabel }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">School Year</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $section->academicYear?->school_year ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">Grade Level</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $gradeLabel }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">Cluster</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $section->cluster?->name ?? 'N/A' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                <span class="text-sm text-gray-500">Semester</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $semester ? ucfirst($semester).' Semester' : 'Full Year' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 md:border-b-0 md:pb-0">
                <span class="text-sm text-gray-500">Room</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $section->room ?? '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
                <span class="text-sm text-gray-500">Students</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $studentCount }}</span>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">Grade Sheet</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($canEditCurrentTerm)
                        Enter grades for <strong class="text-[#296374]">{{ $editablePeriodLabel }}</strong>. Earlier terms opened by the admin are read-only.
                        @else
                        {{ $editablePeriodLabel ? $editablePeriodLabel.' grades are locked.' : 'No grading term is currently open for input.' }}
                        @endif
                    </p>
                </div>
                @if ($canEditCurrentTerm)
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ $editablePeriodLabel ?? 'Open' }}</span>
                @else
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-gray-600">Locked</span>
                @endif
            </div>
        </div>

        <div class="flex border-b border-gray-200 bg-gray-50/60">
            <button type="button" data-grade-tab="input" class="grade-tab flex flex-1 items-center justify-center border-b-2 border-[#296374] bg-white px-4 py-3.5 text-sm font-semibold text-[#296374] transition sm:flex-none sm:px-6">
                Grade Input
            </button>
            <button type="button" data-grade-tab="summary" class="grade-tab flex flex-1 items-center justify-center border-b-2 border-transparent px-4 py-3.5 text-sm font-semibold text-gray-500 transition hover:text-gray-700 sm:flex-none sm:px-6">
                Grade Summary
            </button>
        </div>

        <div data-grade-panel="input">
            @if (! $canEditCurrentTerm)
            <div class="border-b border-gray-200 bg-slate-50 px-6 py-3 text-sm text-slate-700">
                This grade sheet is locked for the current term. The values below are read-only.
            </div>
            @elseif (count($lockedPeriodKeys) > 0)
            <div class="border-b border-gray-200 bg-amber-50 px-6 py-3 text-sm text-amber-800">
                Earlier terms are locked by the school administrator. Only {{ $editablePeriodLabel }} can be edited.
            </div>
            @endif

            <form id="gradeForm" action="{{ route('teacher.sections.grades.store', $assignment) }}" method="POST">
                @csrf
                <input type="hidden" name="submit" id="submitGradesInput" value="0">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm text-gray-800">
                        <thead>
                            <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                                <th class="border border-gray-200 px-3 py-3 text-center">#</th>
                                <th class="border border-gray-200 px-3 py-3">LRN</th>
                                <th class="border border-gray-200 px-3 py-3">Student</th>
                                @foreach ($inputPeriods as $period)
                                <th class="border border-gray-200 px-3 py-3 text-center" data-period-column="{{ $period['key'] }}">{{ $period['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $currentSexGroup = null;
                            $rowNumber = 0;
                            @endphp
                            @forelse ($enrollments as $enrollment)
                            @php
                            $student = $enrollment->student;
                            $application = $student?->application;
                            $name = $application ? $application->last_name.', '.$application->first_name.($application->middle_name ? ' '.$application->middle_name : '') : ($student?->user?->name ?? 'N/A');
                            $gradeSet = $grades[$enrollment->enrollment_ID] ?? collect();
                            $sexGroup = strtolower((string) $student?->sex) === 'female' ? 'Female' : (strtolower((string) $student?->sex) === 'male' ? 'Male' : 'Unspecified');
                            @endphp
                            @if ($currentSexGroup !== $sexGroup)
                            @php
                            $currentSexGroup = $sexGroup;
                            @endphp
                            <tr class="bg-[#dbeaf1]/60">
                                <td colspan="{{ $inputColspan }}" class="border border-gray-200 px-3 py-2 text-xs font-bold uppercase tracking-wide text-gray-700">
                                    {{ $sexGroup }}
                                </td>
                            </tr>
                            @endif
                            @php
                            $rowNumber++;
                            @endphp
                            <tr class="{{ $rowNumber % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                                <td class="border border-gray-200 px-3 py-2.5 text-center">{{ $rowNumber }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 font-mono text-xs">{{ $student?->lrn ?? 'N/A' }}</td>
                                <td class="border border-gray-200 px-3 py-2.5">{{ $name }}</td>
                                @foreach ($inputPeriods as $period)
                                @php
                                $gradeRecord = $gradeSet->get($period['key']);
                                $isCellLocked = in_array($period['key'], $lockedPeriodKeys, true) || $gradeRecord?->isTeacherLocked() || ! $canEditCurrentTerm;
                                @endphp
                                <td class="border border-gray-200 px-3 py-2 text-center" data-period-column="{{ $period['key'] }}">
                                    <input
                                        type="number"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        data-grade-input
                                        class="mx-auto w-20 rounded-md border px-2 py-1.5 text-center text-sm outline-none transition {{ $isCellLocked ? 'border-gray-200 bg-gray-50 text-gray-500' : 'border-gray-300 bg-white text-gray-800 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15' }}"
                                        name="grades[{{ $enrollment->enrollment_ID }}][{{ $period['key'] }}][grade]"
                                        value="{{ old('grades.'.$enrollment->enrollment_ID.'.'.$period['key'].'.grade', $gradeRecord?->numeric_grade) }}"
                                        placeholder="—"
                                        title="Enter a number from 0 to 100"
                                        @disabled($isCellLocked) />
                                </td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $inputColspan }}" class="border border-gray-200 px-6 py-16 text-center">
                                    <h3 class="text-lg font-semibold text-gray-700">No students found for this subject</h3>
                                    <p class="mt-2 text-sm text-gray-500">Students will appear here once they are enrolled in this section.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if ($enrollments->isNotEmpty() && $canEditCurrentTerm)
            <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <button type="button" id="saveGradesButton" class="inline-flex items-center justify-center rounded-md px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
                    Save Grades
                </button>
                <button type="button" id="submitGradesButton" class="inline-flex items-center justify-center rounded-md bg-amber-500 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-amber-600">
                    Submit
                </button>
            </div>
            @endif
        </div>

        <div data-grade-panel="summary" class="hidden">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500">Average computed from available periods.</p>
                <a href="{{ route('teacher.sections.summary.print', $assignment) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-[#296374]/40 hover:text-[#296374]">
                    Print Summary
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm text-gray-800">
                    <thead>
                        <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                            <th class="border border-gray-200 px-3 py-3 text-center">#</th>
                            <th class="border border-gray-200 px-3 py-3">LRN</th>
                            <th class="border border-gray-200 px-3 py-3">Student</th>
                            @foreach ($periods as $period)
                            <th class="border border-gray-200 px-3 py-3 text-center" data-period-column="{{ $period['key'] }}">{{ $period['label'] }}</th>
                            @endforeach
                            <th class="border border-gray-200 px-3 py-3 text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $currentSummarySexGroup = null;
                        $summaryRowNumber = 0;
                        @endphp
                        @forelse ($enrollments as $enrollment)
                        @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $name = $application ? $application->last_name.', '.$application->first_name : ($student?->user?->name ?? 'N/A');
                        $summary = $summaries[$enrollment->enrollment_ID] ?? null;
                        $gradeSet = $summary['grades'] ?? collect();
                        $average = $summary['average'] ?? null;
                        $averageClass = $average === null ? 'text-gray-400' : ($average >= 75 ? 'font-bold text-emerald-700' : 'font-bold text-red-700');
                        $sexGroup = strtolower((string) $student?->sex) === 'female' ? 'Female' : (strtolower((string) $student?->sex) === 'male' ? 'Male' : 'Unspecified');
                        @endphp
                        @if ($currentSummarySexGroup !== $sexGroup)
                        @php
                        $currentSummarySexGroup = $sexGroup;
                        @endphp
                        <tr class="bg-[#dbeaf1]/60">
                            <td colspan="{{ $summaryColspan }}" class="border border-gray-200 px-3 py-2 text-xs font-bold uppercase tracking-wide text-gray-700">
                                {{ $sexGroup }}
                            </td>
                        </tr>
                        @endif
                        @php
                        $summaryRowNumber++;
                        @endphp
                        <tr class="{{ $summaryRowNumber % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="border border-gray-200 px-3 py-2.5 text-center">{{ $summaryRowNumber }}</td>
                            <td class="border border-gray-200 px-3 py-2.5 font-mono text-xs">{{ $student?->lrn ?? 'N/A' }}</td>
                            <td class="border border-gray-200 px-3 py-2.5">{{ $name }}</td>
                            @foreach ($periods as $period)
                            @php
                            $periodGrade = $gradeSet->get($period['key'])?->numeric_grade;
                            @endphp
                            <td class="border border-gray-200 px-3 py-2.5 text-center" data-period-column="{{ $period['key'] }}">
                                {{ $periodGrade !== null ? $periodGrade : '—' }}
                            </td>
                            @endforeach
                            <td class="border border-gray-200 px-3 py-2.5 text-center {{ $averageClass }}">
                                {{ $average !== null ? $average : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $summaryColspan }}" class="border border-gray-200 px-6 py-12 text-center text-gray-500">No records.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="flex justify-center pt-1">
        <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to My Sections
        </a>
    </div>
</div>

@push('modals')
<div id="gradeConfirmModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="gradeConfirmTitle">
    <div class="mx-auto w-full max-w-md overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div id="gradeConfirmHeader" class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <p id="gradeConfirmEyebrow" class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Grade sheet</p>
            <h3 id="gradeConfirmTitle" class="mt-1 text-lg font-bold tracking-tight text-white">Confirm action</h3>
        </div>
        <div class="space-y-4 px-6 py-5">
            <p id="gradeConfirmMessage" class="text-sm leading-relaxed text-gray-600"></p>
            <div id="gradeConfirmWarning" class="hidden flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-relaxed text-amber-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span id="gradeConfirmWarningText"></span>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <button type="button" id="gradeConfirmCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
            <button type="button" id="gradeConfirmSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Confirm</button>
        </div>
    </div>
</div>

@endpush

<script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            const gradeForm = document.getElementById('gradeForm');
            const submitGradesInput = document.getElementById('submitGradesInput');
            const saveGradesButton = document.getElementById('saveGradesButton');
            const submitGradesButton = document.getElementById('submitGradesButton');
            const confirmModal = document.getElementById('gradeConfirmModal');
            const confirmEyebrow = document.getElementById('gradeConfirmEyebrow');
            const confirmTitle = document.getElementById('gradeConfirmTitle');
            const confirmMessage = document.getElementById('gradeConfirmMessage');
            const confirmWarning = document.getElementById('gradeConfirmWarning');
            const confirmWarningText = document.getElementById('gradeConfirmWarningText');
            const confirmHeader = document.getElementById('gradeConfirmHeader');
            const confirmCancel = document.getElementById('gradeConfirmCancel');
            const confirmSubmit = document.getElementById('gradeConfirmSubmit');
            const sectionName = @json($section -> name);
            const subjectLabel = @json($subjectLabel);
            const gradeInputs = gradeForm ? gradeForm.querySelectorAll('[data-grade-input]') : [];
            let pendingAction = null;

            function constrainGradeInput(input) {
                if (input.disabled) {
                    return;
                }

                let value = String(input.value ?? '').replace(/[^0-9.]/g, '');
                const firstDot = value.indexOf('.');

                if (firstDot !== -1) {
                    value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                }

                if (value !== '') {
                    const numeric = Number(value);

                    if (!Number.isNaN(numeric) && numeric > 100) {
                        value = '100';
                    }
                }

                if (input.value !== value) {
                    input.value = value;
                }

                input.setCustomValidity('');
            }

            function gradeInputsAreValid() {
                for (const input of gradeInputs) {
                    constrainGradeInput(input);

                    if (input.disabled || input.value === '') {
                        continue;
                    }

                    const numeric = Number(input.value);

                    if (!Number.isFinite(numeric) || numeric < 0 || numeric > 100) {
                        input.setCustomValidity('Enter a number from 0 to 100.');
                        input.reportValidity();
                        input.focus();

                        return false;
                    }
                }

                return true;
            }

            gradeInputs.forEach((input) => {
                input.addEventListener('keydown', (event) => {
                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];

                    if (allowedKeys.includes(event.key)) {
                        return;
                    }

                    if (event.key === '.' && !input.value.includes('.')) {
                        return;
                    }

                    if (/^[0-9]$/.test(event.key)) {
                        return;
                    }

                    event.preventDefault();
                });

                input.addEventListener('input', () => constrainGradeInput(input));

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    input.value = (event.clipboardData || window.clipboardData).getData('text');
                    constrainGradeInput(input);
                });
            });

            function openConfirmModal(options) {
                if (!confirmModal || !confirmMessage || !confirmSubmit) {
                    if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                    }
                    return;
                }

                if (confirmEyebrow) {
                    confirmEyebrow.textContent = options.eyebrow || 'Grade sheet';
                }

                if (confirmTitle) {
                    confirmTitle.textContent = options.title || 'Confirm action';
                }

                confirmMessage.innerHTML = options.message || '';
                confirmSubmit.textContent = options.confirmLabel || 'Confirm';
                confirmSubmit.style.backgroundColor = options.confirmColor || '#296374';

                if (confirmHeader) {
                    confirmHeader.className = 'border-b border-gray-300 px-6 py-4 ' + (options.headerClass || 'bg-[#296374]');
                }

                if (confirmWarning && confirmWarningText) {
                    if (options.warning) {
                        confirmWarningText.textContent = options.warning;
                        confirmWarning.classList.remove('hidden');
                        confirmWarning.classList.add('flex');
                    } else {
                        confirmWarning.classList.add('hidden');
                        confirmWarning.classList.remove('flex');
                    }
                }

                pendingAction = options.onConfirm || null;
                confirmModal.classList.remove('hidden');
                confirmModal.classList.add('flex');
            }

            function closeConfirmModal() {
                if (!confirmModal) {
                    return;
                }

                confirmModal.classList.add('hidden');
                confirmModal.classList.remove('flex');
                pendingAction = null;
            }

            if (confirmCancel) {
                confirmCancel.addEventListener('click', closeConfirmModal);
            }

            if (confirmModal) {
                confirmModal.addEventListener('click', (event) => {
                    if (event.target === confirmModal) {
                        closeConfirmModal();
                    }
                });
            }

            if (confirmSubmit) {
                confirmSubmit.addEventListener('click', () => {
                    if (typeof pendingAction === 'function') {
                        pendingAction();
                    }
                    closeConfirmModal();
                });
            }

            if (saveGradesButton && gradeForm) {
                saveGradesButton.addEventListener('click', () => {
                    if (!gradeInputsAreValid()) {
                        return;
                    }

                    openConfirmModal({
                        eyebrow: 'Grade sheet',
                        title: 'Save grades',
                        message: 'Save the grades entered for <strong class="text-gray-900">' + sectionName + '</strong> — <strong class="text-[#296374]">' + subjectLabel + '</strong>?',
                        confirmLabel: 'Save grades',
                        confirmColor: '#296374',
                        headerClass: 'bg-[#296374]',
                        warning: 'Your progress will be saved, but these grades are not submitted yet. You can continue editing afterward.',
                        onConfirm: () => {
                            if (submitGradesInput) {
                                submitGradesInput.value = '0';
                            }

                            if (typeof gradeForm.requestSubmit === 'function') {
                                gradeForm.requestSubmit();
                                return;
                            }

                            gradeForm.submit();
                        },
                    });
                });
            }

            if (submitGradesButton && gradeForm) {
                submitGradesButton.addEventListener('click', () => {
                    if (!gradeInputsAreValid()) {
                        return;
                    }

                    openConfirmModal({
                        eyebrow: 'Grade sheet',
                        title: 'Submit grades',
                        confirmLabel: 'Submit',
                        message: 'Save and submit the current grades for <strong class="text-gray-900">' + sectionName + '</strong> — <strong class="text-[#296374]">' + subjectLabel + '</strong>?',
                        confirmColor: '#f59e0b',
                        headerClass: 'bg-amber-500',
                        warning: 'The current grades will be saved and then submitted. Once submitted, they can no longer be edited.',
                        onConfirm: () => {
                            if (submitGradesInput) {
                                submitGradesInput.value = '1';
                            }

                            if (typeof gradeForm.requestSubmit === 'function') {
                                gradeForm.requestSubmit();
                                return;
                            }

                            gradeForm.submit();
                        },
                    });
                });
            }
        })();
    });

    (function() {
        const tabs = document.querySelectorAll('[data-grade-tab]');
        const panels = document.querySelectorAll('[data-grade-panel]');

        function activateTab(name) {
            tabs.forEach((tab) => {
                const isActive = tab.dataset.gradeTab === name;
                tab.classList.toggle('border-[#296374]', isActive);
                tab.classList.toggle('bg-white', isActive);
                tab.classList.toggle('text-[#296374]', isActive);
                tab.classList.toggle('border-transparent', !isActive);
                tab.classList.toggle('text-gray-500', !isActive);
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.gradePanel !== name);
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activateTab(tab.dataset.gradeTab));
        });
    })();
</script>
@endsection
