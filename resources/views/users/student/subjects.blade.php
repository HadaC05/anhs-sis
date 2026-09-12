@extends('users.student.layout')

@section('title', 'My Subjects')

@section('content')
@php
    $enrollment = $selectedEnrollment;
    $periodLabel = $isSeniorHigh
        ? collect([$selectedSemester?->label, $selectedTerm?->label])->filter()->implode(' · ')
        : ($selectedTerm?->label);
    $semesterLabels = $semesters->pluck('label', 'key');
    $selectClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="space-y-5">
    @include('users.student.partials.enrollment-summary', [
        'student' => $student,
        'application' => $application,
        'enrollment' => $enrollment,
        'activeYear' => $enrollment?->academicYear ?? $selectedYear,
    ])

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Subjects</h1>
            <p class="mt-1 text-sm text-gray-500">View the subjects assigned to your section for the selected school year and grading period.</p>

            <form method="GET" action="{{ route('student.subjects') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:items-end">
                <div class="min-w-0">
                    <label for="SY_ID" class="mb-1.5 block text-sm text-gray-500">
                        <span class="text-red-500">*</span> School Year
                    </label>
                    <select
                        id="SY_ID"
                        name="SY_ID"
                        onchange="this.form.submit()"
                        class="{{ $selectClass }}"
                        @disabled($academicYears->isEmpty())
                    >
                        @forelse ($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" @selected((int) $selectedYear?->SY_ID === (int) $year->SY_ID)>
                                {{ $year->school_year }}
                            </option>
                        @empty
                            <option value="">No school years available</option>
                        @endforelse
                    </select>
                </div>

                @if ($isSeniorHigh)
                    <div class="min-w-0">
                        <label for="semester_ID" class="mb-1.5 block text-sm text-gray-500">
                            <span class="text-red-500">*</span> Semester
                        </label>
                        <select
                            id="semester_ID"
                            name="semester_ID"
                            onchange="this.form.submit()"
                            class="{{ $selectClass }}"
                            @disabled($semesters->isEmpty())
                        >
                            @forelse ($semesters as $semester)
                                <option value="{{ $semester->semester_ID }}" @selected((int) $selectedSemester?->semester_ID === (int) $semester->semester_ID)>
                                    {{ $semester->label }}
                                </option>
                            @empty
                                <option value="">No semesters available</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="min-w-0">
                        <label for="term_ID" class="mb-1.5 block text-sm text-gray-500">
                            <span class="text-red-500">*</span> Term
                        </label>
                        <select
                            id="term_ID"
                            name="term_ID"
                            onchange="this.form.submit()"
                            class="{{ $selectClass }}"
                            @disabled($terms->isEmpty())
                        >
                            @forelse ($terms as $term)
                                <option value="{{ $term->term_ID }}" @selected((int) $selectedTerm?->term_ID === (int) $term->term_ID)>
                                    {{ $term->label }}
                                </option>
                            @empty
                                <option value="">No terms available</option>
                            @endforelse
                        </select>
                    </div>
                @elseif ($enrollment)
                    <div class="min-w-0">
                        <label for="term_ID" class="mb-1.5 block text-sm text-gray-500">
                            <span class="text-red-500">*</span> Term
                        </label>
                        <select
                            id="term_ID"
                            name="term_ID"
                            onchange="this.form.submit()"
                            class="{{ $selectClass }}"
                            @disabled($terms->isEmpty())
                        >
                            @forelse ($terms as $term)
                                <option value="{{ $term->term_ID }}" @selected((int) $selectedTerm?->term_ID === (int) $term->term_ID)>
                                    {{ $term->label }}
                                </option>
                            @empty
                                <option value="">No terms available</option>
                            @endforelse
                        </select>
                    </div>
                @endif
            </form>
        </div>

        @if (! $enrollment)
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-700">No Enrollments Found</h3>
                <p class="mt-2 text-sm text-gray-500">Check Student Profile for your enrollment status, or contact the guidance office.</p>
            </div>
        @elseif ($assignments->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="text-lg font-semibold text-gray-700">No Subjects Assigned</h3>
                <p class="mt-2 text-sm text-gray-500">Subjects assigned to your section for this filter will appear here.</p>
            </div>
        @else
            @if ($periodLabel)
                <div class="border-b border-gray-100 bg-[#f8fbfd] px-6 py-3">
                    <p class="text-sm font-semibold text-[#296374]">{{ $periodLabel }}</p>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm text-gray-800">
                    <thead>
                        <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                            <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Code</th>
                            <th class="border border-gray-200 px-3 py-3">Subject Name</th>
                            <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Type</th>
                            @if ($isSeniorHigh)
                                <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Semester</th>
                            @endif
                            <th class="border border-gray-200 px-3 py-3">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $index => $assignment)
                            @php
                                $subject = $assignment->curriculumSubject?->subject;
                                $subjectCode = $subject?->code ?? '—';
                                $subjectTitle = $subject?->title ?? 'N/A';
                                $subjectType = $subject?->type ? ucwords(str_replace('_', ' ', $subject->type)) : '—';
                                $subjectSemester = $assignment->curriculumSubject?->semester;
                                $teacher = $assignment->staff;
                                $teacherName = $teacher
                                    ? trim($teacher->last_name.', '.$teacher->first_name)
                                    : 'Unassigned';
                            @endphp
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="border border-gray-200 px-3 py-2.5 font-medium whitespace-nowrap">{{ $subjectCode }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 font-semibold uppercase">{{ $subjectTitle }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 whitespace-nowrap">{{ $subjectType }}</td>
                                @if ($isSeniorHigh)
                                    <td class="border border-gray-200 px-3 py-2.5 whitespace-nowrap">
                                        {{ $subjectSemester ? ($semesterLabels[$subjectSemester] ?? ucfirst($subjectSemester).' Semester') : 'Full Year' }}
                                    </td>
                                @endif
                                <td class="border border-gray-200 px-3 py-2.5">{{ $teacherName }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
@endsection
