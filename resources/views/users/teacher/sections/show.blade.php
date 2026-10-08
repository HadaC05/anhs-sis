@extends('users.teacher.layout')

@section('title', 'Section Grades')

@section('content')
@php
$gradeLabel = $section->loadMissing('gradeLevel')->getRelation('gradeLevel')?->grade_label
?? ($section->grade_level ? str_replace(['grade_', '_'], ['Grade ', ' '], $section->grade_level) : '—');
$subject = $assignment->subject;
$subjectCode = $subject?->code ?? 'SUBJ';
$subjectTitle = $subject?->title ?? 'Subject';
$subjectLabel = $subject ? ($subjectCode.' - '.$subjectTitle) : 'Subject';
$mapehComponent = \App\Models\MapehConfiguration::forSection($section)?->components
    ->first(fn ($component) => (int) $component->curriculumSubject->subject_ID === (int) $assignment->subject_ID);
$mapehSheetSuffix = match ($mapehComponent?->key) {
    'music_arts' => 'M and A',
    'pe_health' => 'PE and H',
    default => null,
};
$lockedPeriodKeys = $lockedPeriodKeys ?? [];
$studentCount = $enrollments->count();
$inputColspan = 3 + 2 * count($inputPeriods);
$summaryColspan = 5 + count($periods);
$descriptorBands = \App\Support\Sf9PerformanceScale::forSection($section);
$gradeReturnReasons = $gradeReturnReasons ?? collect();
$currentTermLabel = \App\Models\GradingTerm::currentEditablePeriodLabelForSection($section, $section->curriculum?->gradingSemester?->key);
$termIsOpen = $editablePeriodKey !== null && (\App\Models\GradingTerm::isSeniorHighSection($section)
    ? \App\Models\GradingTerm::isCurrentSeniorHighPeriodOpen()
    : \App\Models\GradingTerm::isCurrentJuniorHighPeriodOpen());
@endphp

<div class="space-y-5">
    <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 transition hover:text-[#296374]">&larr; My Sections</a>
    @if (session('status'))
    <div id="teacherGradeToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span>{{ session('status') }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 hover:bg-emerald-50" data-dismiss-teacher-grade-toast aria-label="Close notification">&times;</button>
    </div>
    @endif

    @if ($errors->any())
    <div id="teacherGradeErrorToast" role="alert" aria-live="assertive" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <span>{{ $errors->first() }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-rose-700/70 hover:bg-rose-50" data-dismiss-teacher-grade-toast aria-label="Close notification">&times;</button>
    </div>
    @endif

    @if ($gradeReturnReasons->isNotEmpty())
    <section class="overflow-hidden rounded-xl border-2 border-amber-400 bg-amber-50 shadow-lg" aria-labelledby="grade-returned-title">
        <div class="flex items-start gap-4 bg-amber-400 px-5 py-4 text-amber-950">
            <svg class="mt-0.5 h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
            <div>
                <h1 id="grade-returned-title" class="text-base font-extrabold uppercase tracking-wide">Submitted grades returned for revision</h1>
                <p class="mt-1 text-sm font-semibold">Please correct the item below and resubmit the grades.</p>
            </div>
        </div>
        <div class="space-y-2 px-5 py-4 text-sm text-amber-950">
            @foreach ($gradeReturnReasons as $reason)
                <p><span class="font-bold">Reason:</span> {{ $reason->name }}@if ($reason->description) — {{ $reason->description }}@endif</p>
            @endforeach
        </div>
    </section>
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
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold {{ $termIsOpen ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $currentTermLabel ?? 'Grade editing' }} <span>{{ $termIsOpen ? 'Open' : 'Closed' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 border-y border-slate-300 bg-slate-200 p-2" role="group" aria-label="Grade sheet views">
            <button type="button" data-grade-tab="input" aria-pressed="true" class="grade-tab flex min-w-0 items-center justify-center rounded-lg border border-[#296374] bg-[#296374] px-2 py-3.5 text-sm font-bold text-white shadow-md transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374] sm:px-6">
                Grade Input
            </button>
            <button type="button" data-grade-tab="summary" aria-pressed="false" class="grade-tab flex min-w-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374] sm:px-6">
                Grade Summary
            </button>
            <button type="button" data-grade-tab="statistics" aria-pressed="false" class="grade-tab flex min-w-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374] sm:px-6">Class Statistics</button>
        </div>

        <div data-grade-panel="input">
            @if (\App\Support\MapehGrades::isComputed($assignment))
            <div class="border-b border-gray-200 bg-cyan-50 px-6 py-3 text-sm text-[#296374]">This subject is calculated from its configured components. Enter or import grades through each component's grade sheet. Existing standalone records below are retained for reference; the combined results appear in the advisory grade summary and reports.</div>
            @endif
            @if (! $canEditCurrentTerm)
            <div class="border-b border-gray-200 bg-slate-50 px-6 py-3 text-sm text-slate-700">
                This grade sheet is locked for the current term. The values below are read-only.
            </div>
            @endif

            @if ($canEditCurrentTerm && $enrollments->isNotEmpty())
            <div class="border-b border-gray-200 px-6 py-4">
                <button type="button" id="classRecordImportTrigger" class="rounded-md bg-[#296374] px-4 py-2 text-sm font-semibold text-white" aria-haspopup="dialog" aria-controls="classRecordImportModal">Import Class Record</button>
                <div id="classRecordImportResult" class="hidden space-y-2 text-sm text-gray-700" aria-live="polite">
                    <p id="classRecordImportSummary"></p>
                    <details id="classRecordImportDetails" class="hidden">
                        <summary class="cursor-pointer font-semibold">Records that were not imported</summary>
                        <ul id="classRecordImportIssues" class="mt-2 max-h-52 list-disc space-y-1 overflow-y-auto pl-5 text-xs"></ul>
                    </details>
                </div>
            </div>
            <div id="classRecordImportToast" role="status" aria-live="polite" class="hidden fixed right-5 top-5 z-[120] w-[calc(100%-2.5rem)] max-w-sm rounded-xl border border-gray-200 bg-white p-4 text-sm font-semibold text-gray-800 shadow-xl">
                <button type="button" class="float-right ml-3" aria-label="Close import notification" onclick="this.parentElement.classList.add('hidden')">&times;</button>
                <span></span>
            </div>
            @endif

            <form id="gradeForm" action="{{ route('teacher.sections.grades.store', $assignment) }}" method="POST">
                @csrf
                <input type="hidden" name="submit" id="submitGradesInput" value="0">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] table-fixed border-collapse text-sm text-gray-800" aria-label="Learner grade input">
                        <colgroup>
                            <col class="w-14">
                            <col class="w-44">
                            <col>
                            @foreach ($inputPeriods as $period)
                                <col class="w-36">
                                <col class="w-28">
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr class="bg-slate-100 text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                                <th scope="col" class="border-b border-gray-200 px-3 py-4 text-center">#</th>
                                <th scope="col" class="border-b border-gray-200 px-4 py-4">LRN</th>
                                <th scope="col" class="border-b border-gray-200 px-4 py-4">Student</th>
                                @foreach ($inputPeriods as $period)
                                <th scope="col" class="border-b border-l border-gray-200 bg-[#dbeaf1]/60 px-4 py-4 text-center" data-period-column="{{ $period['key'] }}">{{ $period['label'] }}</th>
                                <th scope="col" class="border-b border-gray-200 px-4 py-4 text-center">Remarks</th>
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
                            <tr class="bg-slate-50">
                                <td colspan="{{ $inputColspan }}" class="border-y border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-[#296374]">
                                    {{ $sexGroup }}
                                </td>
                            </tr>
                            @endif
                            @php
                            $rowNumber++;
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50 focus-within:bg-cyan-50/40 {{ $rowNumber % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }}">
                                <td class="border-b border-gray-100 px-3 py-3 text-center text-slate-400">{{ $rowNumber }}</td>
                                <td class="border-b border-gray-100 px-4 py-3 font-mono text-xs text-slate-500">{{ $student?->lrn ?? 'N/A' }}</td>
                                <td class="break-words border-b border-gray-100 px-4 py-3 font-medium text-slate-800">{{ $name }}</td>
                                @foreach ($inputPeriods as $period)
                                @php
                                $gradeRecord = $gradeSet->get($period['key']);
                                $inputGrade = old('grades.'.$enrollment->enrollment_ID.'.'.$period['key'].'.grade', $gradeRecord?->numeric_grade);
                                $validInputGrade = is_numeric($inputGrade) && $inputGrade >= 60 && $inputGrade <= 100;
                                $isCellLocked = in_array($period['key'], $lockedPeriodKeys, true) || $gradeRecord?->isTeacherLocked() || ! $canEditCurrentTerm;
                                @endphp
                                <td class="border-b border-l border-gray-100 px-4 py-2 text-center" data-period-column="{{ $period['key'] }}">
                                    <input
                                        type="number"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="60"
                                        max="100"
                                        aria-label="{{ $period['label'] }} grade for {{ $name }}"
                                        data-grade-input
                                        class="mx-auto block h-10 w-24 rounded-lg border px-3 text-center text-sm font-semibold tabular-nums outline-none transition [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none {{ $isCellLocked ? 'border-gray-200 bg-gray-50 text-gray-500' : 'border-gray-300 bg-white text-gray-800 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15' }}"
                                        name="grades[{{ $enrollment->enrollment_ID }}][{{ $period['key'] }}][grade]"
                                        value="{{ old('grades.'.$enrollment->enrollment_ID.'.'.$period['key'].'.grade', $gradeRecord?->numeric_grade) }}"
                                        placeholder="—"
                                        title="Enter a number from 60 to 100"
                                        @disabled($isCellLocked) />
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3 text-center text-xs font-semibold {{ ! $validInputGrade ? 'text-gray-400' : ($inputGrade >= 75 ? 'text-emerald-700' : 'text-red-700') }}" data-input-remarks aria-live="polite">{{ $validInputGrade ? ($inputGrade >= 75 ? 'Passed' : 'Failed') : '—' }}</td>
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
            <div class="teacher-actions flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <button type="button" id="saveGradesButton" class="inline-flex items-center justify-center rounded-md px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
                    Save Grades
                </button>
            </div>
            @endif
        </div>

        <div data-grade-panel="summary" class="hidden">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="gradeSummaryHeading" class="text-sm font-bold text-gray-900">Class grade summary</h2>
                    <p id="gradeSummaryDescription" class="mt-1 text-xs leading-relaxed text-gray-500">{{ $studentCount }} {{ Str::plural('student', $studentCount) }} &middot; Averages use available periods. Unrecorded grades appear as &mdash;.</p>
                </div>
                <a href="{{ route('teacher.sections.summary.print', $assignment) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-[#296374]/40 hover:text-[#296374]">
                    Print Full Summary
                </a>
            </div>

            <aside aria-labelledby="descriptor-guide-title" class="border-b border-gray-200 bg-slate-50 px-6 py-4">
                <h3 id="descriptor-guide-title" class="text-sm font-bold text-gray-800">Descriptor guide</h3>
                <p class="mt-1 text-xs text-gray-500">Based on the selected {{ $descriptorBands[0]['description'] === 'Advancing' ? 'SF9 Performance Report' : 'original SF9 Progress Report' }}. Descriptors use the average shown below; missing grades have no descriptor.</p>
                <dl class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach($descriptorBands as $band)
                        <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><dt class="text-xs font-semibold text-gray-800">{{ $band['description'] }}</dt><dd class="mt-1 text-xs text-gray-500">{{ $band['scale'] }} &middot; {{ $band['remarks'] }}</dd></div>
                    @endforeach
                </dl>
            </aside>

            <div class="overflow-x-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#296374]" role="region" aria-labelledby="gradeSummaryHeading" tabindex="0">
                <table class="w-full border-collapse text-sm text-gray-800" aria-labelledby="gradeSummaryHeading" aria-describedby="gradeSummaryDescription">
                    <thead>
                        <tr class="bg-slate-100 text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                            <th scope="col" class="w-14 border-b border-gray-200 px-3 py-4 text-center">#</th>
                            <th scope="col" class="min-w-64 border-b border-gray-200 px-4 py-4">Student <span class="ml-1 font-medium normal-case text-slate-500">/ LRN</span></th>
                            @foreach ($periods as $period)
                            <th scope="col" class="min-w-28 border-b border-gray-200 px-4 py-4 text-center" data-period-column="{{ $period['key'] }}">{{ $period['label'] }}</th>
                            @endforeach
                            <th scope="col" class="w-32 min-w-28 border-b border-l border-[#296374]/15 bg-[#296374]/10 px-4 py-4 text-center text-[#296374]">Average</th>
                            <th scope="col" class="min-w-28 border-b border-gray-200 px-4 py-4 text-center">Remarks</th>
                            <th scope="col" class="min-w-40 border-b border-gray-200 px-4 py-4 text-center">Descriptor</th>
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
                        <tr class="bg-slate-50">
                            <td colspan="{{ $summaryColspan }}" class="border-b border-gray-200 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {{ $sexGroup }}
                            </td>
                        </tr>
                        @endif
                        @php
                        $summaryRowNumber++;
                        @endphp
                        <tr class="bg-white transition-colors hover:bg-slate-50">
                            <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs tabular-nums text-slate-400">{{ $summaryRowNumber }}</td>
                            <th scope="row" class="border-b border-gray-100 px-4 py-3.5 text-left font-medium">
                                <span class="block break-words text-gray-900">{{ $name }}</span>
                                <span class="mt-1 block font-mono text-xs font-normal tracking-wide text-slate-500">{{ $student?->lrn ?? 'N/A' }}</span>
                            </th>
                            @foreach ($periods as $period)
                            @php
                            $periodGrade = $gradeSet->get($period['key'])?->numeric_grade;
                            @endphp
                            <td class="border-b border-gray-100 px-4 py-3.5 text-center font-medium tabular-nums text-slate-600" data-summary-enrollment="{{ $enrollment->enrollment_ID }}" data-period-column="{{ $period['key'] }}">
                                {{ $periodGrade !== null ? $periodGrade : '—' }}
                            </td>
                            @endforeach
                            <td class="border-b border-l border-gray-100 bg-[#296374]/5 px-4 py-3.5 text-center text-base tabular-nums {{ $averageClass }}" data-summary-average>
                                {{ $average !== null ? $average : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-center text-xs font-semibold {{ $averageClass }}" data-summary-remarks>{{ $average === null ? '—' : ($average >= 75 ? 'Passed' : 'Failed') }}</td>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-center text-xs font-semibold text-gray-700" data-summary-descriptor>{{ \App\Support\Sf9PerformanceScale::descriptor($average, $descriptorBands) ?? '?' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $summaryColspan }}" class="px-6 py-16 text-center">
                                <p class="font-semibold text-gray-700">No students to display</p>
                                <p class="mt-1 text-sm text-gray-500">Grades will appear here once students are enrolled in this section.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($enrollments->isNotEmpty() && $canEditCurrentTerm)
            <div class="teacher-actions flex justify-end border-t border-gray-200 px-6 py-4">
                <button type="button" id="submitGradesButton" class="inline-flex items-center justify-center rounded-md bg-amber-500 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-amber-600">
                    Submit
                </button>
            </div>
            @endif
        </div>
        @include('users.teacher.sections.statistics')
    </div>

</div>

<script>
    document.querySelectorAll('[data-dismiss-teacher-grade-toast]').forEach((button) => button.addEventListener('click', () => button.closest('[role]')?.remove()));
    window.setTimeout(() => document.getElementById('teacherGradeToast')?.remove(), 4000);
    window.setTimeout(() => document.getElementById('teacherGradeErrorToast')?.remove(), 6000);
</script>

@push('modals')
@if ($canEditCurrentTerm && $enrollments->isNotEmpty())
<div id="classRecordImportModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true" aria-labelledby="classRecordImportTitle">
    <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-5 shadow-2xl">
        <form id="classRecordImportForm" action="{{ route('teacher.sections.grades.import', $assignment) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="classRecordImportTitle" class="text-base font-bold text-gray-800">Import Class Record</h2>
                <button type="button" id="classRecordImportClose" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Close</button>
            </div>
            <p class="mb-3 text-xs font-semibold text-gray-700">Importing into {{ $section->name }} · {{ $subjectTitle }} · {{ $section->academicYear?->school_year }}</p>
            <label for="classRecordPeriod" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Term</label>
            <select id="classRecordPeriod" name="period" required class="mb-4 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                @foreach ($inputPeriods as $period)
                    <option value="{{ $period['key'] }}" @selected($period['key'] === $editablePeriodKey)>{{ $period['label'] }}</option>
                @endforeach
            </select>
            <p id="classRecordImportHelp" class="mb-3 text-xs text-gray-500">Upload a completed Excel class record. An LRN column is not required: grades are matched to enrolled learners by name. @if ($mapehSheetSuffix) This component uses the selected TERM number's {{ $mapehSheetSuffix }} worksheet when present; a plain TERM worksheet is also accepted. @else Grades come from the selected TERM worksheet. @endif Recalculate and save the workbook in Excel before uploading, then review and save the imported grades.</p>
            <input id="classRecordFile" name="class_record" type="file" accept=".xlsx" required class="sr-only" aria-label="Excel class record" aria-describedby="classRecordImportHelp">
            <div id="classRecordDropzone" class="cursor-pointer rounded-xl border-2 border-dashed border-[#4bb878]/45 bg-[#f8fcfb] px-6 py-10 text-center transition hover:border-[#4bb878] hover:bg-[#f1faf6]">
                <svg class="mx-auto h-11 w-11 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 13h16v4H4z"></path></svg>
                <p class="mt-4 text-sm font-medium text-[#4bb878]">Drag and drop the class record here</p>
                <p class="mt-1 text-xs text-gray-400">&mdash; OR &mdash;</p>
                <button type="button" id="classRecordBrowse" class="mt-4 inline-flex h-9 items-center rounded-md bg-[#4bb878] px-5 text-xs font-bold text-white">Browse Files</button>
                <p id="classRecordFileName" class="mt-4 text-xs font-medium text-gray-600">XLSX only &middot; Max 10 MB</p>
            </div>
            <p id="classRecordFileError" class="mt-2 text-xs text-red-600" role="alert"></p>
            <div id="classRecordSubmitRow" class="mt-4 hidden justify-end">
                <button type="submit" id="classRecordImportButton" class="h-9 rounded-lg bg-[#296374] px-4 text-xs font-bold text-white disabled:opacity-50">Import Class Record</button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="gradeConfirmModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true" aria-labelledby="gradeConfirmTitle">
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
    function updateGradeRemarks(cell, value) {
        if (!cell) return;
        const grade = value === null || String(value).trim() === '' ? NaN : Number(value);
        const valid = Number.isFinite(grade) && grade >= 60 && grade <= 100;
        cell.textContent = valid ? (grade >= 75 ? 'Passed' : 'Failed') : '—';
        cell.classList.toggle('text-gray-400', !valid);
        cell.classList.toggle('text-emerald-700', valid && grade >= 75);
        cell.classList.toggle('text-red-700', valid && grade < 75);
    }

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

            const importForm = document.getElementById('classRecordImportForm');
            const importModal = document.getElementById('classRecordImportModal');
            const importTrigger = document.getElementById('classRecordImportTrigger');
            const importFile = document.getElementById('classRecordFile');
            const importZone = document.getElementById('classRecordDropzone');
            let importBusy = false;
            let previousBodyOverflow;
            function closeImportModal() {
                if (importBusy || !importModal) return;
                importModal.classList.add('hidden');
                importModal.classList.remove('flex');
                document.body.style.overflow = previousBodyOverflow ?? '';
                importTrigger?.focus();
            }
            importTrigger?.addEventListener('click', () => {
                previousBodyOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                importModal.classList.remove('hidden');
                importModal.classList.add('flex');
                document.getElementById('classRecordPeriod').focus();
            });
            document.getElementById('classRecordImportClose')?.addEventListener('click', closeImportModal);
            importModal?.addEventListener('click', event => {
                if (event.target === importModal) closeImportModal();
            });
            importModal?.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeImportModal();
                if (event.key !== 'Tab') return;
                const focusable = [...importModal.querySelectorAll('button, select')].filter(el => !el.disabled && el.getClientRects().length);
                const first = focusable[0], last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault(); last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault(); first?.focus();
                }
            });
            function selectImportFile() {
                const file = importFile.files[0];
                const valid = file && /\.xlsx$/i.test(file.name) && file.size <= 10 * 1024 * 1024;
                document.getElementById('classRecordFileName').textContent = file?.name ?? 'XLSX only · Max 10 MB';
                document.getElementById('classRecordFileError').textContent = file && !valid ? 'Choose an XLSX file no larger than 10 MB.' : '';
                document.getElementById('classRecordSubmitRow').classList.toggle('hidden', !valid);
                document.getElementById('classRecordSubmitRow').classList.toggle('flex', !!valid);
                importFile.setCustomValidity(file && !valid ? 'Choose an XLSX file no larger than 10 MB.' : '');
            }
            document.getElementById('classRecordBrowse')?.addEventListener('click', event => {
                event.stopPropagation();
                if (!importBusy) importFile.click();
            });
            importZone?.addEventListener('click', () => { if (!importBusy) importFile.click(); });
            importFile?.addEventListener('change', selectImportFile);
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(type => importZone?.addEventListener(type, event => {
                event.preventDefault();
                importZone.classList.toggle('border-[#4bb878]', type === 'dragenter' || type === 'dragover');
                if (type === 'drop' && !importBusy && event.dataTransfer.files.length) {
                    importFile.files = event.dataTransfer.files;
                    selectImportFile();
                }
            }));
            let importToastTimer;
            importForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = document.getElementById('classRecordImportButton');
                if (button.disabled) return;
                importBusy = true;
                const formData = new FormData(importForm);
                const controls = [button, saveGradesButton, submitGradesButton, ...importForm.querySelectorAll('input[type="file"], select')].filter(Boolean);
                const controlStates = controls.map(control => control.disabled);
                const inputStates = [...gradeInputs].map(input => input.readOnly);
                controls.forEach(control => control.disabled = true);
                gradeInputs.forEach(input => input.readOnly = true);
                button.textContent = 'Reading class record…';
                const result = document.getElementById('classRecordImportResult');
                const summary = document.getElementById('classRecordImportSummary');
                const details = document.getElementById('classRecordImportDetails');
                const issues = document.getElementById('classRecordImportIssues');
                let message;
                issues.replaceChildren();
                details.classList.add('hidden');
                details.open = false;
                try {
                    const response = await fetch(importForm.action, {
                        method: 'POST', body: formData,
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? 'The class record could not be imported. Refresh the page and try again.');
                    }
                    let imported = 0;
                    data.grades.forEach(record => {
                        const input = gradeForm.elements.namedItem(`grades[${record.enrollment_id}][${data.period}][grade]`);
                        if (input && !input.disabled) {
                            input.value = record.grade;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            imported++;
                        }
                    });
                    const periodLabel = document.getElementById('classRecordPeriod').selectedOptions[0].text;
                    message = imported
                        ? `${imported} grades filled for ${periodLabel}. Review and save, or submit from Grade Summary.`
                        : `No grades were imported for ${periodLabel}. Existing inputs were kept.`;
                    const allNamesUnmatched = imported === 0 && data.issues.length > 0
                        && data.issues.every(issue => issue.includes('no matching learner in this class.'));
                    summary.textContent = `${message} ${data.unchanged} learner inputs unchanged.`
                        + (allNamesUnmatched ? ' Check that the workbook names match students enrolled in this section and school year.' : '');
                    data.issues.forEach(issue => {
                        const item = document.createElement('li');
                        item.textContent = issue;
                        issues.appendChild(item);
                    });
                    details.classList.toggle('hidden', data.issues.length === 0);
                    details.open = imported === 0 && data.issues.length > 0;
                    importBusy = false;
                    closeImportModal();
                } catch (error) {
                    message = error.message || 'The upload failed. Please try again.';
                    summary.textContent = message;
                    document.getElementById('classRecordFileError').textContent = message;
                } finally {
                    importBusy = false;
                    result.classList.remove('hidden');
                    controls.forEach((control, index) => control.disabled = controlStates[index]);
                    gradeInputs.forEach((input, index) => input.readOnly = inputStates[index]);
                    button.textContent = 'Import Class Record';
                    const toast = document.getElementById('classRecordImportToast');
                    toast.querySelector('span').textContent = message;
                    toast.classList.remove('hidden');
                    clearTimeout(importToastTimer);
                    importToastTimer = setTimeout(() => toast.classList.add('hidden'), 7000);
                }
            });

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
                updateGradeRemarks(input.closest('td').nextElementSibling, input.value);
            }

            function gradeInputsAreValid() {
                for (const input of gradeInputs) {
                    constrainGradeInput(input);

                    if (input.disabled || input.value === '') {
                        continue;
                    }

                    const numeric = Number(input.value);

                    if (!Number.isFinite(numeric) || numeric < 60 || numeric > 100) {
                        document.querySelector('[data-grade-tab="input"]')?.click();
                        input.setCustomValidity('Enter a number from 60 to 100.');
                        input.reportValidity();
                        input.focus();

                        return false;
                    }
                }

                return true;
            }

            gradeInputs.forEach((input) => {
                updateGradeRemarks(input.closest('td').nextElementSibling, input.value);
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
            if (name === 'summary' || name === 'statistics') {
                document.querySelectorAll('[data-summary-enrollment]').forEach(cell => {
                    const input = document.getElementById('gradeForm')?.elements.namedItem(`grades[${cell.dataset.summaryEnrollment}][${cell.dataset.periodColumn}][grade]`);
                    if (input && !input.disabled) cell.textContent = input.value === '' ? '—' : input.value;
                });
                document.querySelectorAll('[data-summary-average]').forEach(cell => {
                    const values = [...cell.parentElement.querySelectorAll('[data-summary-enrollment]')]
                        .map(item => item.textContent.trim()).filter(value => value !== '' && Number.isFinite(Number(value)) && Number(value) >= 0 && Number(value) <= 100).map(Number);
                    const average = values.length ? Math.round(values.reduce((total, value) => total + value, 0) / values.length * 100) / 100 : null;
                    cell.textContent = average === null ? '—' : average;
                    cell.classList.toggle('text-gray-400', average === null);
                    cell.classList.toggle('font-bold', average !== null);
                    cell.classList.toggle('text-emerald-700', average !== null && average >= 75);
                    cell.classList.toggle('text-red-700', average !== null && average < 75);
                    updateGradeRemarks(cell.parentElement.querySelector('[data-summary-remarks]'), average);
                });
            }
            tabs.forEach((tab) => {
                const isActive = tab.dataset.gradeTab === name;
                tab.setAttribute('aria-pressed', String(isActive));
                ['border-[#296374]', 'bg-[#296374]', 'text-white', 'shadow-md'].forEach(className => tab.classList.toggle(className, isActive));
                ['border-slate-300', 'bg-white', 'text-slate-700', 'hover:bg-slate-100'].forEach(className => tab.classList.toggle(className, !isActive));
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.gradePanel !== name);
            });
            document.dispatchEvent(new CustomEvent('grade-sheet-updated'));
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activateTab(tab.dataset.gradeTab));
        });
    })();
</script>
@endsection
