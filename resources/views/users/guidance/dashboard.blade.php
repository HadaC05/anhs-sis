@extends('users.guidance.layout')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Dashboard</h1>
    <p class="text-gray-600 text-sm md:text-base">Overview of enrollment statistics and activities</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-6 mb-10">
    <a href="{{ route('guidance.enrollments.index', ['status' => 'all']) }}" class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20 cursor-pointer" style="background-color: #296374;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Total Applications</p>
        <p class="text-3xl font-bold text-white">{{ $totalCount }}</p>
        <p class="text-xs text-white/70 mt-2 flex items-center gap-1">
            <span>View all</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </p>
    </a>
    <a href="{{ route('guidance.enrollments.index') }}?status=pending" class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20 cursor-pointer" style="background-color: #296374; border-left-color: #fbbf24;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Pending Review</p>
        <p class="text-3xl font-bold text-white">{{ $pendingCount }}</p>
        <p class="text-xs text-white/70 mt-2 flex items-center gap-1">
            <span>Review now</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </p>
    </a>
    <a href="{{ route('guidance.enrollments.index') }}?status=enrolled" class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20 cursor-pointer" style="background-color: #296374; border-left-color: #10b981;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Enrolled</p>
        <p class="text-3xl font-bold text-white">{{ $enrolledCount }}</p>
        <p class="text-xs text-white/70 mt-2 flex items-center gap-1">
            <span>View enrolled</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </p>
    </a>
    <a href="{{ route('guidance.enrollments.index') }}?status=temporarily_enrolled" class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20 cursor-pointer" style="background-color: #296374; border-left-color: #3b82f6;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Temporary</p>
        <p class="text-3xl font-bold text-white">{{ $temporaryCount }}</p>
        <p class="text-xs text-white/70 mt-2 flex items-center gap-1">
            <span>Monitor list</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </p>
    </a>
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20" style="background-color: #296374;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Active School Year</p>
        <p class="text-2xl font-bold text-white">{{ $activeYear ? $activeYear->school_year : 'Not Set' }}</p>
        <p class="text-xs text-white/70 mt-2">Approved: {{ $approvedCount }}</p>
    </div>
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-6 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl border border-white/20" style="background-color: #296374;">
        <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Sections</p>
        <p class="text-3xl font-bold text-white">{{ $sectionCount }}</p>
        <p class="text-xs text-white/70 mt-2">Capacity {{ $totalEnrolled }} / {{ $totalCapacity }}</p>
    </div>
</div>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-8 py-5 border-b border-white/20 bg-white/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="font-bold text-[#296374] text-lg">Recent Applications</h3>
            <p class="text-xs text-gray-600 mt-1">Latest enrollment submissions</p>
        </div>
        <a href="{{ route('guidance.enrollments.index') }}" class="text-xs font-bold uppercase tracking-wider text-gray-600 hover:text-[#296374]">View all</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-8 py-4">Student Info</th>
                    <th class="px-8 py-4">Level</th>
                    <th class="px-8 py-4">Section</th>
                    <th class="px-8 py-4">Status</th>
                    <th class="px-8 py-4">Submitted</th>
                    <th class="px-8 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @forelse($recentEnrollments as $enrollment)
                @php
                    $student = $enrollment->student ?? null;
                    $application = $student?->application;
                    $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                    $initials = $application ? substr($application->first_name ?? '', 0, 1) . substr($application->last_name ?? '', 0, 1) : '--';
                    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
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
                        <span class="text-sm font-semibold text-gray-700">{{ $gradeLabel }}</span>
                        @if(!empty($enrollment->semester))
                        <span class="text-xs text-gray-500 block">Sem {{ ucfirst($enrollment->semester) }}</span>
                        @endif
                    </td>
                    <td class="px-8 py-4">
                        <span class="text-xs font-semibold text-gray-700">{{ $enrollment->section?->name ?? 'Unassigned' }}</span>
                        <span class="text-[10px] text-gray-500 block">{{ $enrollment->cluster?->name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-8 py-4">
                        @if(($enrollment->enrollment_status ?? '') == 'pending')
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                            <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">For Review</span>
                        </div>
                        @elseif(($enrollment->enrollment_status ?? '') == 'enrolled')
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background-color: #296374;"></span>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color: #296374;">Enrolled</span>
                        </div>
                        @elseif(($enrollment->enrollment_status ?? '') == 'temporarily_enrolled')
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Temporary</span>
                        </div>
                        @else
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_status ?? 'N/A')) }}</span>
                        </div>
                        @endif
                    </td>
                    <td class="px-8 py-4">
                        <span class="text-xs text-gray-600">{{ $enrollment->created_at->format('M d, Y') }}</span>
                    </td>
                    <td class="px-8 py-4 text-right">
                        <a href="{{ route('guidance.enrollments.show', $enrollment) }}" class="inline-block text-white text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-lg shadow-md transition-all transform hover:-translate-y-1 hover:shadow-lg active:scale-95" style="background-color: #296374;">
                            Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-8 py-8 text-center text-gray-500">
                        No recent applications found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
