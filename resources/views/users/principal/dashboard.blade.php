@extends('users.principal.layout')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <div class="bg-white/95 backdrop-blur-sm rounded-lg shadow-xl border border-white/20 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Principal Dashboard</h1>
                <p class="text-gray-600 text-sm md:text-base">School-wide enrollment, staffing, and academic operations overview.</p>
            </div>
            <div class="rounded-lg px-5 py-3 text-white shadow-md" style="background-color: #296374;">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">Active School Year</p>
                <p class="text-lg font-bold">{{ $activeYear ? $activeYear->school_year : 'Not Set' }}</p>
            </div>
        </div>
    </div>

    <div id="enrollment-summary" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="shadow-xl rounded-lg p-6 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Active Students</p>
            <p class="text-3xl font-bold text-white">{{ $totalStudents }}</p>
            <p class="text-xs text-white/70 mt-2">{{ $enrolledCount }} enrolled, {{ $temporaryCount }} temporary</p>
        </div>
        <div class="shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #fbbf24;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Pending Enrollments</p>
            <p class="text-3xl font-bold text-white">{{ $pendingCount }}</p>
            <p class="text-xs text-white/70 mt-2">Awaiting review by guidance</p>
        </div>
        <div class="shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #10b981;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Sections</p>
            <p class="text-3xl font-bold text-white">{{ $sectionCount }}</p>
            <p class="text-xs text-white/70 mt-2">Capacity {{ $totalEnrolled }} / {{ $totalCapacity }}</p>
        </div>
        <div class="shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #3b82f6;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Capacity Use</p>
            <p class="text-3xl font-bold text-white">{{ $capacityRate }}%</p>
            <p class="text-xs text-white/70 mt-2">Across active sections</p>
        </div>
    </div>

    <div id="school-operations" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
            <div class="px-8 py-5 border-b border-white/20 bg-white/50">
                <h3 class="font-bold text-[#296374] text-lg">Recent Enrollment Activity</h3>
                <p class="text-xs text-gray-600 mt-1">Latest student status changes for the active school year.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                            <th class="px-8 py-4">Student</th>
                            <th class="px-8 py-4">Grade</th>
                            <th class="px-8 py-4">Section</th>
                            <th class="px-8 py-4">Status</th>
                            <th class="px-8 py-4">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/20">
                        @forelse($recentEnrollments as $enrollment)
                            @php
                                $student = $enrollment->student;
                                $application = $student?->application;
                                $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                            @endphp
                            <tr class="hover:bg-white/30 transition-all bg-white/10">
                                <td class="px-8 py-4">
                                    <p class="text-sm font-bold text-gray-800">{{ $name }}</p>
                                    <p class="text-xs text-gray-600">{{ $student?->lrn ?? 'N/A' }}</p>
                                </td>
                                <td class="px-8 py-4 text-sm font-semibold text-gray-700">{{ $gradeLabel }}</td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-semibold text-gray-700">{{ $enrollment->section?->name ?? 'Unassigned' }}</span>
                                    <span class="text-[10px] text-gray-500 block">{{ $enrollment->cluster?->name ?? 'N/A' }}</span>
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold uppercase tracking-wider {{ $enrollment->enrollment_status === 'pending' ? 'text-amber-600' : ($enrollment->enrollment_status === 'temporarily_enrolled' ? 'text-blue-600' : 'text-[#296374]') }}">
                                        {{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_status ?? 'N/A')) }}
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-xs text-gray-600">{{ $enrollment->updated_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-8 text-center text-gray-500">No recent enrollment activity found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-6">
                <h3 class="font-bold text-[#296374] text-lg mb-5">Academic Operations</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Active Teachers</p>
                            <p class="text-sm text-gray-600">Available instructional staff</p>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">{{ $teacherCount }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-4">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Subject Loads</p>
                            <p class="text-sm text-gray-600">Current assignments</p>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">{{ $subjectAssignmentCount }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Submitted Grades</p>
                            <p class="text-sm text-gray-600">For registrar review</p>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">{{ $submittedGradesCount }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-6">
                <h3 class="font-bold text-[#296374] text-lg mb-5">Enrollment by Grade</h3>
                <div class="space-y-3">
                    @foreach(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'] as $gradeLevel)
                        @php
                            $count = (int) ($gradeLevelStats[$gradeLevel] ?? 0);
                            $width = $totalStudents > 0 ? max(6, round(($count / $totalStudents) * 100)) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-gray-600 mb-1">
                                <span>{{ strtoupper(str_replace('grade_', 'Grade ', $gradeLevel)) }}</span>
                                <span>{{ $count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $width }}%; background-color: #296374;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
