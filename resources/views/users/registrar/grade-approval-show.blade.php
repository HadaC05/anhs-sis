@extends('users.registrar.layout')

@section('title', 'Review Grade Submission')

@section('content')
@php
    $section = $assignment->section;
    $subject = $assignment->curriculumSubject?->subject;
    $teacher = $assignment->staff;
    $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
    $teacherName = $teacher ? trim($teacher->last_name . ', ' . $teacher->first_name . ' ' . $teacher->middle_name) : 'N/A';
    $latestSubmitted = $assignment->grades->max('submitted_at');
    $isApprovedView = ($status ?? 'submitted') === 'approved';
@endphp

<div class="mb-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('registrar.grade-approvals') }}" class="text-sm font-semibold text-[#296374] hover:underline">Back to grade approvals</a>
            <h1 class="mt-3 text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">{{ $isApprovedView ? 'Approved Grade Record' : 'Review Grade Submission' }}</h1>
            <p class="text-gray-600 text-sm md:text-base">{{ $section?->name ?? 'N/A' }} | {{ $subjectLabel }}</p>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
    </div>
@endif

<div class="grid gap-4 md:grid-cols-4 mb-6">
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Section</p>
        <p class="mt-2 text-sm font-semibold text-gray-800">{{ $section?->name ?? 'N/A' }}</p>
    </div>
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Teacher</p>
        <p class="mt-2 text-sm font-semibold text-gray-800">{{ $teacherName }}</p>
    </div>
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">{{ $isApprovedView ? 'Approved Records' : 'Submitted Records' }}</p>
        <p class="mt-2 text-sm font-semibold text-gray-800">{{ $assignment->grades->count() }}</p>
    </div>
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">{{ $isApprovedView ? 'Last Approved' : 'Last Submitted' }}</p>
        <p class="mt-2 text-sm font-semibold text-gray-800">
            @php($latestDate = $isApprovedView ? $assignment->grades->max('reviewed_at') : $latestSubmitted)
            {{ $latestDate ? $latestDate->format('M d, Y h:i A') : 'N/A' }}
        </p>
    </div>
</div>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-6 py-4 border-b border-white/20 bg-white/40">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">{{ $isApprovedView ? 'Approved Grade Details' : 'Submitted Grade Details' }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">LRN</th>
                    @foreach($periods as $period)
                        <th class="px-6 py-4 text-center">{{ $period['label'] }}</th>
                    @endforeach
                    <th class="px-6 py-4 text-center">Average</th>
                    <th class="px-6 py-4">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @foreach($rows as $row)
                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $row['name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['lrn'] }}</td>
                        @foreach($periods as $period)
                            <td class="px-6 py-4 text-center text-sm text-gray-700">
                                {{ $row['period_values'][$period['key']] ?? '-' }}
                            </td>
                        @endforeach
                        <td class="px-6 py-4 text-center text-sm font-bold text-gray-800">
                            {{ $row['average'] !== null ? number_format($row['average'], 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['remarks'] !== '' ? $row['remarks'] : '-' }}</td>
                    </tr>
                @endforeach

                @if($rows->isEmpty())
                    <tr>
                        <td colspan="{{ 4 + count($periods) }}" class="px-6 py-12 text-center text-gray-500">No submitted grades found for this assignment.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if(! $isApprovedView)
        <div class="px-6 py-5 border-t border-white/20 bg-white/50 flex flex-col sm:flex-row justify-end gap-2">
            <form action="{{ route('registrar.grade-approvals.approve', $assignment) }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Approve Grades</button>
            </form>
            <form action="{{ route('registrar.grade-approvals.reject', $assignment) }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md bg-rose-500 hover:bg-rose-600">Return to Teacher</button>
            </form>
        </div>
    @else
        <div class="px-6 py-5 border-t border-white/20 bg-white/50 text-sm text-gray-600">
            Approved grades are shown here for reference when a teacher requests a change.
        </div>
    @endif
</div>
@endsection
