@extends('users.student.layout')

@section('title', 'My Subjects')

@section('content')
@php
    $enrollment = $selectedEnrollment;
    $selectClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="space-y-5">
    @include('users.student.partials.enrollment-summary', ['student' => $student, 'application' => $application, 'enrollment' => $enrollment, 'activeYear' => $enrollment?->academicYear])

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Subjects</h1>
            <p class="mt-1 text-sm text-gray-500">View the subjects in your enrollment record.</p>

            <form method="GET" action="{{ route('student.subjects') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:items-end">
                <div class="min-w-0">
                    <label for="grade_ID" class="mb-1.5 block text-sm text-gray-500"><span class="text-red-500">*</span> Grade Level</label>
                    <select id="grade_ID" name="grade_ID" onchange="this.form.submit()" class="{{ $selectClass }}" @disabled($gradeLevels->isEmpty())>
                        @forelse ($gradeLevels as $grade)
                            <option value="{{ $grade->grade_ID }}" @selected((int) $selectedGrade?->grade_ID === (int) $grade->grade_ID)>{{ $grade->grade_label }}</option>
                        @empty
                            <option value="">No grade levels available</option>
                        @endforelse
                    </select>
                </div>

                @if ($isSeniorHigh)
                    <div class="min-w-0">
                        <label for="semester_ID" class="mb-1.5 block text-sm text-gray-500"><span class="text-red-500">*</span> Semester</label>
                        <select id="semester_ID" name="semester_ID" onchange="this.form.submit()" class="{{ $selectClass }}" @disabled($semesters->isEmpty())>
                            @forelse ($semesters as $semester)
                                <option value="{{ $semester->semester_ID }}" @selected((int) $selectedSemester?->semester_ID === (int) $semester->semester_ID)>{{ $semester->label }}</option>
                            @empty
                                <option value="">No semesters available</option>
                            @endforelse
                        </select>
                    </div>
                @endif
            </form>
        </div>

        @if (! $enrollment)
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <h3 class="text-lg font-semibold text-gray-700">No Enrollments Found</h3>
                <p class="mt-2 text-sm text-gray-500">Check Student Profile for your enrollment status, or contact the guidance office.</p>
            </div>
        @elseif ($studentSubjects->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="text-lg font-semibold text-gray-700">No Subjects Assigned</h3>
                <p class="mt-2 text-sm text-gray-500">Subjects from this enrollment record will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm text-gray-800">
                    <thead><tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                        <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Code</th>
                        <th class="border border-gray-200 px-3 py-3">Subject Name</th>
                        <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Subject Type</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($studentSubjects as $index => $studentSubject)
                            @php
                                $subject = $studentSubject->curriculumSubject?->subject;
                                $subjectCode = $subject?->code ?? '—';
                                $subjectTitle = $subject?->title ?? 'N/A';
                                $subjectType = $subject?->type ? ucwords(str_replace('_', ' ', $subject->type)) : '—';
                            @endphp
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="border border-gray-200 px-3 py-2.5 font-medium whitespace-nowrap">{{ $subjectCode }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 font-semibold uppercase">{{ $subjectTitle }}</td>
                                <td class="border border-gray-200 px-3 py-2.5 whitespace-nowrap">{{ $subjectType }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="flex justify-center pt-1">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
