@extends('users.registrar.layout')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Registrar Dashboard</h1>
        <p class="text-gray-600 text-sm md:text-base">Overview of enrolled students and records.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="shadow-xl rounded-2xl p-6" style="background-color: #296374;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Total Students</p>
            <p class="text-3xl font-bold text-white">{{ $totalStudents }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #10b981;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Enrolled</p>
            <p class="text-3xl font-bold text-white">{{ $enrolledCount }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #3b82f6;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Temporarily Enrolled</p>
            <p class="text-3xl font-bold text-white">{{ $temporaryCount }}</p>
        </div>
    </div>

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-8 py-5 border-b border-gray-200 bg-white/50">
            <h3 class="font-bold text-[#296374] text-lg">Recent Enrollments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                        <th class="px-8 py-4">Student</th>
                        <th class="px-8 py-4">Grade</th>
                        <th class="px-8 py-4">Status</th>
                        <th class="px-8 py-4">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/20">
                    @forelse($recentStudents as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $application = $student?->application;
                            $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                            $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                        @endphp
                        <tr class="hover:bg-white/30 transition-all bg-white/10">
                            <td class="px-8 py-4 text-sm font-semibold text-gray-800">{{ $name }}</td>
                            <td class="px-8 py-4 text-sm text-gray-700">{{ $gradeLabel }}</td>
                            <td class="px-8 py-4 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_status)) }}</td>
                            <td class="px-8 py-4 text-xs text-gray-500">{{ $enrollment->updated_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-8 text-center text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
