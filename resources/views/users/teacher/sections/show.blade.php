@extends('users.teacher.layout')

@section('title', 'Section Grades')

@section('content')
@php
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
    $subject = $assignment->curriculumSubject?->subject;
    $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
@endphp

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">{{ $section->name }} - {{ $subjectLabel }}</h1>
            <p class="text-gray-600 text-sm md:text-base">Input grades for {{ $gradeLabel }}.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('teacher.sections.summary.print', $assignment) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md bg-slate-600 hover:bg-slate-700">
                Print Summary
            </a>
            <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">
                Back to Assignments
            </a>
        </div>
    </div>
</div>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-8 py-5 border-b border-white/20 bg-white/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="font-bold text-[#296374] text-lg">Grade Input</h3>
            <p class="text-xs text-gray-600 mt-1">Periods: {{ collect($periods)->pluck('label')->implode(', ') }}</p>
        </div>
    </div>

    @if(session('status'))
        <div class="px-8 py-4 bg-emerald-50 text-emerald-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="px-8 py-4 bg-rose-50 text-rose-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('teacher.sections.grades.store', $assignment) }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                        <th class="px-8 py-4">Student</th>
                        <th class="px-8 py-4">Status</th>
                        @foreach($periods as $period)
                            <th class="px-8 py-4 text-center">{{ $period['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/20">
                    @forelse($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $application = $student?->application;
                            $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                            $initials = $application ? substr($application->first_name ?? '', 0, 1) . substr($application->last_name ?? '', 0, 1) : '--';
                            $gradeSet = $grades[$enrollment->enrollment_ID] ?? collect();
                        @endphp
                        <tr class="hover:bg-white/30 transition-all bg-white/10">
                            <td class="px-8 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-md" style="background-color: #296374;">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ $name }}</p>
                                        <p class="text-xs text-gray-600">{{ $student?->lrn ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-4">
                                <span class="text-xs font-semibold text-gray-600 uppercase">
                                    {{ str_replace('_', ' ', $enrollment->enrollment_status ?? 'N/A') }}
                                </span>
                            </td>
                            @foreach($periods as $period)
                                @php
                                    $gradeRecord = $gradeSet->get($period['key']);
                                @endphp
                                <td class="px-8 py-4 text-center">
                                    <div class="space-y-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            class="w-24 border border-gray-300 rounded-md px-2 py-1 text-sm text-gray-700 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none"
                                            name="grades[{{ $enrollment->enrollment_ID }}][{{ $period['key'] }}][grade]"
                                            value="{{ old('grades.' . $enrollment->enrollment_ID . '.' . $period['key'] . '.grade', $gradeRecord?->numeric_grade) }}"
                                            placeholder="Grade"
                                        />
                                        <input
                                            type="text"
                                            class="w-24 border border-gray-200 rounded-md px-2 py-1 text-xs text-gray-600 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none"
                                            name="grades[{{ $enrollment->enrollment_ID }}][{{ $period['key'] }}][remarks]"
                                            value="{{ old('grades.' . $enrollment->enrollment_ID . '.' . $period['key'] . '.remarks', $gradeRecord?->remarks) }}"
                                            placeholder="Remarks"
                                        />
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($periods) }}" class="px-8 py-8 text-center text-gray-500">
                                No students found for this subject.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enrollments->isNotEmpty())
            <div class="px-8 py-6 border-t border-white/20 bg-white/50 flex flex-col sm:flex-row items-end sm:items-center justify-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-bold text-white uppercase tracking-wide shadow-lg transition-all hover:-translate-y-1" style="background-color: #296374;">
                    Save Grades
                </button>
            </div>
        @endif
    </form>

    @if($enrollments->isNotEmpty())
        <form action="{{ route('teacher.sections.grades.submit', $assignment) }}" method="POST" class="px-8 py-6 border-t border-white/20 bg-white/50 text-right">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-bold uppercase tracking-wide text-white shadow-md bg-amber-500 hover:bg-amber-600">
                Submit for Registrar Review
            </button>
            <p class="text-xs text-gray-500 mt-2">Submitting will lock grades for review. Update grades again to return to draft.</p>
        </form>
    @endif
</div>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden mt-8">
    <div class="px-8 py-5 border-b border-white/20 bg-white/50">
        <h3 class="font-bold text-[#296374] text-lg">Grade Summary</h3>
        <p class="text-xs text-gray-600 mt-1">Average computed from available periods.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-8 py-4">Student</th>
                    @foreach($periods as $period)
                        <th class="px-8 py-4 text-center">{{ $period['label'] }}</th>
                    @endforeach
                    <th class="px-8 py-4 text-center">Average</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @forelse($enrollments as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                        $summary = $summaries[$enrollment->enrollment_ID] ?? null;
                        $gradeSet = $summary['grades'] ?? collect();
                    @endphp
                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                        <td class="px-8 py-4 text-sm font-semibold text-gray-800">{{ $name }}</td>
                        @foreach($periods as $period)
                            <td class="px-8 py-4 text-center text-sm text-gray-700">
                                {{ $gradeSet->get($period['key'])?->numeric_grade ?? '—' }}
                            </td>
                        @endforeach
                        <td class="px-8 py-4 text-center text-sm font-bold text-gray-800">
                            {{ $summary && $summary['average'] !== null ? $summary['average'] : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($periods) }}" class="px-8 py-8 text-center text-gray-500">No records.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
