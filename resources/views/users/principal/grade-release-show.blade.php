@extends('users.principal.layout')

@section('title', 'Review Grade Release')

@section('content')
@php
    $section = $assignment->section;
    $subject = $assignment->curriculumSubject?->subject;
    $teacher = $assignment->staff;
    $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
    $teacherName = $teacher ? trim($teacher->last_name . ', ' . $teacher->first_name . ' ' . $teacher->middle_name) : 'N/A';
    $isReleasedView = ($status ?? 'approved') === 'released';
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">{{ $isReleasedView ? 'Released Grade Record' : 'Review Grade Release' }}</h1>
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
    </div>
@endif

<section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="grid xl:grid-cols-2 xl:divide-x xl:divide-slate-200">
        <dl class="divide-y divide-slate-100 px-6">
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">School Year</dt><dd class="text-sm font-semibold text-[#296374]">{{ $assignment->academicYear?->school_year ?? 'N/A' }}</dd></div>
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">Section</dt><dd class="text-sm font-semibold text-[#296374]">{{ $section?->name ?? 'N/A' }}</dd></div>
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">Teacher</dt><dd class="min-w-0 sm:text-right text-sm font-semibold text-[#296374]">{{ $teacherName }}</dd></div>
        </dl>
        <dl class="divide-y divide-slate-100 px-6">
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">Subject</dt><dd class="min-w-0 sm:text-right text-sm font-semibold text-[#296374]">{{ $subjectLabel }}</dd></div>
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">Grade Level</dt><dd class="text-sm font-semibold text-[#296374]">{{ $section?->getRelation('gradeLevel')?->grade_label ?? 'N/A' }}</dd></div>
            <div class="flex flex-col items-start gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"><dt class="shrink-0 text-sm text-slate-500">{{ $isReleasedView ? 'Released Records' : 'Approved Records' }}</dt><dd class="text-sm font-semibold text-[#296374]">{{ $assignment->grades->count() }}</dd></div>
        </dl>
    </div>
</section>

<div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Student grades">
        <table class="min-w-[800px] w-full text-left">
            <thead>
                <tr class="border-b border-[#296374] bg-[#296374] text-xs font-semibold uppercase tracking-wide text-white">
                    <th scope="col" class="px-4 py-2">Student</th>
                    <th scope="col" class="px-4 py-2">LRN</th>
                    @foreach($periods as $period)
                        <th scope="col" class="px-4 py-2 text-center">{{ $period['label'] }}</th>
                    @endforeach
                    <th scope="col" class="px-4 py-2 text-center">Average</th>
                    <th scope="col" class="px-4 py-2">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @if($rows->isEmpty())
                    <tr>
                        <td colspan="{{ 4 + count($periods) }}" class="px-6 py-12 text-center text-gray-500">No grades found for this assignment.</td>
                    </tr>
                @else
                    @foreach($rows as $row)
                        <tr class="transition-colors odd:bg-white even:bg-slate-50 hover:bg-[#edf5f7]">
                            <td class="px-4 py-2 text-sm font-semibold text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $row['lrn'] }}</td>
                            @foreach($periods as $period)
                                <td class="px-4 py-2 text-center text-sm tabular-nums text-slate-700">{{ $row['period_values'][$period['key']] ?? '-' }}</td>
                            @endforeach
                            <td class="border-x border-slate-200 bg-slate-100/60 px-4 py-2 text-center text-sm font-bold tabular-nums text-slate-800">
                                {{ $row['average'] !== null ? number_format($row['average'], 2) : '-' }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                @if($row['remarks'] !== '')
                                    <span class="inline-flex rounded-md border px-2.5 py-1 text-xs font-semibold {{ $row['remarks'] === 'Passed' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">{{ $row['remarks'] }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    @if(! $isReleasedView)
        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-5">
            <form action="{{ route('principal.grade-releases.release', $assignment) }}" method="POST" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Release to Students</button>
            </form>
        </div>
    @endif
</div>
@endsection
