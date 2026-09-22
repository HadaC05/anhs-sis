@extends('users.student.layout')

@section('title', 'My Grades')

@section('content')
@php
    $enrollment = $selectedEnrollment;
    $gradingPeriods = collect($gradingTerms ?? [])->pluck('label', 'key')->all();
    $periodKeys = array_keys($gradingPeriods);
    $formatGrade = fn ($value) => $value === null || $value === '' ? '—' : number_format((float) $value, 0);
    $gradeRemarks = fn ($value) => $value === null || $value === '' ? '—' : ((float) $value >= 75 ? 'PASSED' : 'FAILED');
    $sessionLabel = function ($item): string {
        $schoolYear = $item?->academicYear?->school_year ?? 'N/A';
        $semester = $item?->isSeniorHigh() && $item?->semester
            ? strtoupper(ucfirst($item->semester)).' SEM'
            : null;
        $grade = $item?->gradeLevel?->grade_label
            ?? ($item?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $item->grade_level)) : null);

        return collect(["SY {$schoolYear}", $semester, $grade])->filter()->implode(' · ');
    };
@endphp

<div class="space-y-5">
    @include('users.student.partials.enrollment-summary', [
        'student' => $student,
        'application' => $application,
        'enrollment' => $enrollment,
        'activeYear' => $enrollment?->academicYear,
    ])

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Grade</h1>

            <form method="GET" action="{{ route('student.grades') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1 sm:max-w-sm">
                    <label for="session" class="mb-1.5 block text-sm text-gray-500">
                        <span class="text-red-500">*</span> Academic Session
                    </label>
                    <select
                        id="session"
                        name="session"
                        onchange="this.form.submit()"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15"
                        @disabled($enrollments->isEmpty())
                    >
                        @forelse ($enrollments as $item)
                            <option value="{{ $item->enrollment_ID }}" @selected($enrollment?->enrollment_ID === $item->enrollment_ID)>
                                {{ $sessionLabel($item) }}
                            </option>
                        @empty
                            <option value="">No academic sessions available</option>
                        @endforelse
                    </select>
                </div>
                <button
                    type="button"
                    id="toggleGradeReport"
                    class="inline-flex w-full items-center justify-center rounded-md border border-[#296374] bg-white px-4 py-2.5 text-sm font-semibold text-[#296374] transition hover:bg-[#296374]/5 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    @disabled(! $enrollment)
                >
                    Grade Report
                </button>
            </form>
        </div>

        @if (! $enrollment)
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-700">No Enrollments Found</h3>
                <p class="mt-2 text-sm text-gray-500">Check Student Profile for your enrollment status, or contact the guidance office.</p>
            </div>
        @elseif ($enrollment->subjectAssignments->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="text-lg font-semibold text-gray-700">No Subjects Found</h3>
                <p class="mt-2 text-sm text-gray-500">Assigned subjects for this academic session will appear here once they are available.</p>
            </div>
        @else
            @php
                $finalRatings = [];
                $semesterName = $enrollment->semester
                    ? strtoupper(substr($enrollment->semester, 0, 1)).'S'
                    : ($enrollment->gradeLevel?->grade_label ?? strtoupper(str_replace('grade_', 'G', (string) $enrollment->grade_level)));
            @endphp
            <div class="space-y-3 p-4 md:hidden">
                @php $mobileFinalRatings = []; @endphp
                @foreach ($enrollment->subjectAssignments as $assignment)
                    @php
                        $grades = $assignment->grades->keyBy('grading_period');
                        $subject = $assignment->curriculumSubject?->subject;
                        $periodValues = collect($periodKeys)
                            ->mapWithKeys(fn ($periodKey) => [$periodKey => $grades->get($periodKey)?->numeric_grade]);
                        $availableGrades = $periodValues->filter(fn ($grade) => $grade !== null && $grade !== '');
                        $finalRating = $availableGrades->isNotEmpty() ? round($availableGrades->avg()) : null;
                        $mobileFinalRatings[] = $finalRating;
                        $mobileRowId = 'mobile-grade-detail-'.$assignment->assignment_ID;
                    @endphp
                    <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#296374]">{{ $subject?->code ?? 'â€”' }}</p>
                                <h2 class="mt-1 text-base font-bold leading-snug text-gray-800">{{ $subject?->title ?? 'N/A' }}</h2>
                                <p class="mt-1 text-xs text-gray-500">{{ $semesterName }} · {{ $subject?->type ? ucwords(str_replace('_', ' ', $subject->type)) : 'â€”' }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Final Grade</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $formatGrade($finalRating) }}</p>
                                <p class="text-xs font-bold {{ $finalRating !== null && (float) $finalRating < 75 ? 'text-red-600' : 'text-emerald-700' }}">{{ $gradeRemarks($finalRating) }}</p>
                            </div>
                        </div>
                        <button type="button" class="grade-expand mt-4 inline-flex w-full items-center justify-center rounded-md border border-[#296374] px-3 py-2 text-sm font-semibold text-[#296374] transition hover:bg-[#296374] hover:text-white" data-target="{{ $mobileRowId }}" data-expanded-label="Hide term grades" data-collapsed-label="View term grades" aria-expanded="false">
                            View term grades
                        </button>
                        <div id="{{ $mobileRowId }}" class="hidden">
                        @if ($availableGrades->isNotEmpty())
                            <dl class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 text-sm">
                                @foreach ($availableGrades as $periodKey => $periodValue)
                                    <div class="rounded bg-slate-50 px-3 py-2">
                                        <dt class="text-xs text-gray-500">{{ $gradingPeriods[$periodKey] ?? $periodKey }}</dt>
                                        <dd class="mt-1 font-bold text-gray-800">{{ $formatGrade($periodValue) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-3 border-t border-gray-100 pt-3 text-center text-sm text-gray-500">There are no grades yet.</p>
                        @endif
                        </div>
                    </article>
                @endforeach
                @php
                    $mobileAverageGrades = collect($mobileFinalRatings)->filter(fn ($grade) => $grade !== null);
                    $mobileGeneralAverage = $mobileAverageGrades->isNotEmpty() ? round($mobileAverageGrades->avg()) : null;
                @endphp
                <div class="flex items-center justify-between rounded-lg bg-[#eef5f8] px-4 py-3">
                    <span class="text-sm font-bold uppercase tracking-wide text-gray-700">General Average</span>
                    <span class="text-lg font-bold {{ $mobileGeneralAverage !== null && (float) $mobileGeneralAverage < 75 ? 'text-red-600' : 'text-emerald-700' }}">{{ $formatGrade($mobileGeneralAverage) }} · {{ $gradeRemarks($mobileGeneralAverage) }}</span>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full border-collapse text-sm text-gray-800">
                    <thead>
                        <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                            <th class="border border-gray-200 px-3 py-3 text-center w-16">Action</th>
                            <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Semester</th>
                            <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Code</th>
                            <th class="border border-gray-200 px-3 py-3">Subject Name</th>
                            <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Type</th>
                            <th class="border border-gray-200 px-3 py-3 text-center whitespace-nowrap">Final Grade</th>
                            <th class="border border-gray-200 px-3 py-3 text-center whitespace-nowrap">Grade Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollment->subjectAssignments as $index => $assignment)
                            @php
                                $grades = $assignment->grades->keyBy('grading_period');
                                $subject = $assignment->curriculumSubject?->subject;
                                $subjectCode = $subject?->code ?? '—';
                                $subjectTitle = $subject?->title ?? 'N/A';
                                $subjectType = $subject?->type ? ucwords(str_replace('_', ' ', $subject->type)) : '—';
                                $periodValues = collect($periodKeys)
                                    ->mapWithKeys(fn ($periodKey) => [$periodKey => $grades->get($periodKey)?->numeric_grade]);
                                $availableGrades = $periodValues->filter(fn ($grade) => $grade !== null && $grade !== '');
                                $finalRating = $availableGrades->isNotEmpty() ? round($availableGrades->avg()) : null;
                                $finalRatings[] = $finalRating;
                                $rowId = 'grade-detail-'.$assignment->assignment_ID;
                            @endphp
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="border border-gray-200 px-3 py-2.5 text-center">
                                    <button
                                        type="button"
                                        class="grade-expand inline-flex h-7 w-7 items-center justify-center rounded border border-[#296374] bg-white text-base font-bold leading-none text-[#296374] transition hover:bg-[#296374] hover:text-white"
                                        data-target="{{ $rowId }}"
                                        aria-expanded="false"
                                        aria-label="Show term grades"
                                    >+</button>
                                </td>
                                <td class="border border-gray-200 px-3 py-2.5 whitespace-nowrap">{{ $semesterName }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 font-medium whitespace-nowrap">{{ $subjectCode }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 font-semibold uppercase">{{ $subjectTitle }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 whitespace-nowrap">{{ $subjectType }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 text-center font-bold">{{ $formatGrade($finalRating) }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 text-center font-semibold {{ $finalRating !== null && (float) $finalRating < 75 ? 'text-red-600' : 'text-emerald-700' }}">
                                    {{ $gradeRemarks($finalRating) }}
                                </td>
                            </tr>
                            <tr id="{{ $rowId }}" class="hidden bg-[#e4f0f4]">
                                <td colspan="7" class="border border-gray-200 px-4 py-3">
                                    @if ($availableGrades->isEmpty())
                                        <p class="text-center text-sm text-gray-500">There are no grades yet.</p>
                                    @else
                                        <dl class="mx-auto max-w-lg divide-y divide-gray-200 overflow-hidden rounded-md border border-gray-200 bg-white text-sm">
                                            @foreach ($availableGrades as $periodKey => $periodValue)
                                                <div class="flex items-center justify-between gap-4 px-3 py-2.5">
                                                    <dt class="font-medium text-gray-600">{{ $gradingPeriods[$periodKey] ?? $periodKey }}</dt>
                                                    <dd class="font-bold text-gray-800">{{ $formatGrade($periodValue) }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @php
                            $generalAverageGrades = collect($finalRatings)->filter(fn ($grade) => $grade !== null);
                            $generalAverage = $generalAverageGrades->isNotEmpty() ? round($generalAverageGrades->avg()) : null;
                        @endphp
                        <tr class="bg-[#eef5f8]">
                            <td colspan="5" class="border border-gray-200 px-3 py-3 text-right text-sm font-bold uppercase tracking-wide text-gray-700">
                                General Average
                            </td>
                            <td class="border border-gray-200 px-3 py-3 text-center text-sm font-bold text-gray-900">
                                {{ $formatGrade($generalAverage) }}
                            </td>
                            <td class="border border-gray-200 px-3 py-3 text-center text-sm font-bold {{ $generalAverage !== null && (float) $generalAverage < 75 ? 'text-red-600' : 'text-emerald-700' }}">
                                {{ $gradeRemarks($generalAverage) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="gradeReportLegend" class="hidden border-t border-gray-200 px-6 py-5">
                <div class="grid gap-6 text-sm text-gray-700 md:grid-cols-3">
                    <div>
                        <h5 class="mb-2 font-bold text-gray-800">Descriptors</h5>
                        <div class="space-y-1">
                            <p>Outstanding</p>
                            <p>Very Satisfactory</p>
                            <p>Satisfactory</p>
                            <p>Fairly Satisfactory</p>
                            <p>Did Not Meet Expectations</p>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-2 font-bold text-gray-800">Grading Scale</h5>
                        <div class="space-y-1">
                            <p>90-100</p>
                            <p>85-89</p>
                            <p>80-84</p>
                            <p>75-79</p>
                            <p>Below 75</p>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-2 font-bold text-gray-800">Remarks</h5>
                        <div class="space-y-1">
                            <p>Passed</p>
                            <p>Passed</p>
                            <p>Passed</p>
                            <p>Passed</p>
                            <p>Failed</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="flex justify-center pt-1">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('.grade-expand').forEach((button) => {
            button.addEventListener('click', () => {
                const target = document.getElementById(button.dataset.target);
                if (!target) {
                    return;
                }

                const isHidden = target.classList.contains('hidden');
                target.classList.toggle('hidden', !isHidden);
                button.textContent = isHidden ? '−' : '+';
                if (button.dataset.expandedLabel) {
                    button.textContent = isHidden ? button.dataset.expandedLabel : button.dataset.collapsedLabel;
                }
                button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            });
        });

        const reportButton = document.getElementById('toggleGradeReport');
        const legend = document.getElementById('gradeReportLegend');
        reportButton?.addEventListener('click', () => {
            if (!legend) {
                return;
            }
            legend.classList.toggle('hidden');
        });
    })();
</script>
@endsection
