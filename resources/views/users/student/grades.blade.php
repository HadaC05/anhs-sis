@extends('users.student.layout')

@section('title', 'My Grades')

@section('content')
@php
    $studentName = optional($application)->last_name ? optional($application)->last_name . ', ' . optional($application)->first_name : Auth::user()->name;
@endphp
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">My Grades</h1>
        <p class="text-gray-600 text-sm md:text-base">View your academic performance and enrolled subjects.</p>
    </div>

    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-[#296374] uppercase tracking-wider">Student Information</h3>
            <div class="text-sm text-gray-600">
                <span class="font-semibold">Name:</span> {{ $studentName }} | 
                <span class="font-semibold">LRN:</span> {{ optional($student)->lrn ?? 'Not Set' }}
            </div>
        </div>
    </div>

    @if($enrollments->isEmpty())
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Enrollments Found</h3>
            <p class="text-gray-600">You don't have any enrollments yet. Please complete your enrollment first.</p>
        </div>
    @else
        @foreach($enrollments as $schoolYear => $yearEnrollments)
            @foreach($yearEnrollments as $enrollment)
                <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-[#296374] uppercase tracking-wider">
                            {{ $schoolYear }}
                        </h3>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span class="font-semibold">Grade Level:</span> 
                            {{ strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level)) }}
                            @if($enrollment->semester)
                                <span class="font-semibold">| Semester:</span> {{ ucfirst($enrollment->semester) }}
                            @endif
                            <span class="font-semibold">| Section:</span> 
                            {{ $enrollment->section->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                    
                    @if($enrollment->subjectAssignments->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p>No subjects assigned for this enrollment.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b-2 border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Teacher</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Grading Period</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-700">Grade</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Remarks</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Posted By</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Date Posted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enrollment->subjectAssignments as $assignment)
                                        @php
                                            $grades = $assignment->grades->keyBy('grading_period');
                                            $gradingPeriods = ['q1', 'q2', 'q3', 'q4'];
                                        @endphp
                                        
                                        @foreach($gradingPeriods as $period)
                                            @php
                                                $grade = $grades->get($period);
                                                $periodName = match($period) {
                                                    'q1' => 'First',
                                                    'q2' => 'Second', 
                                                    'q3' => 'Third',
                                                    'q4' => 'Fourth',
                                                    default => ucfirst($period)
                                                };
                                            @endphp
                                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                                <td class="py-3 px-4">
                                                    <div class="font-medium text-gray-900">
                                                        {{ $assignment->curriculumSubject->subject->subject_name ?? 'N/A' }}
                                                        @if($loop->first)
                                                            <div class="text-xs text-gray-500 mt-1">
                                                                {{ $assignment->section->name ?? 'No Section' }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($loop->first)
                                                        {{ $assignment->staff->first_name . ' ' . $assignment->staff->last_name ?? 'Not Assigned' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $periodName }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    @if($grade)
                                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold
                                                            {{ $grade->numeric_grade >= 90 ? 'bg-green-100 text-green-800' : 
                                                               ($grade->numeric_grade >= 85 ? 'bg-blue-100 text-blue-800' : 
                                                               ($grade->numeric_grade >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                                            {{ number_format($grade->numeric_grade, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                                            Not Posted
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($grade)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            {{ $grade->remarks === 'Passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ $grade->remarks }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($grade)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            {{ $grade->status === 'released' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                            {{ ucfirst($grade->status) }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                            Pending
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 text-gray-600">
                                                    {{ $grade->postedBy->name ?? '-' }}
                                                </td>
                                                <td class="py-3 px-4 text-gray-600">
                                                    {{ $grade->reviewed_at ? $grade->reviewed_at->format('M d, Y') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    @endif

    <div class="flex justify-center">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-bold text-white uppercase tracking-wide shadow-lg transition-all hover:-translate-y-1" style="background-color: #296374;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>
@endsection
