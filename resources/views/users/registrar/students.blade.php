@extends('users.registrar.layout')

@section('title', 'Student Masterlist')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Student Masterlist</h1>
    <p class="text-gray-600 text-sm md:text-base">View enrolled and temporarily enrolled students for the active school year.</p>
    @if (!isset($activeYear) || !$activeYear)
        <p class="mt-2 text-amber-600 text-sm font-medium">No active school year set. The list will appear once an admin sets the current school year.</p>
    @endif
</div>

<form method="GET" action="{{ route('registrar.students') }}" class="mb-6 rounded-xl border border-gray-200 bg-white/80 p-8 shadow-sm">
    <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">

    <div class="mb-6">
        <label for="search" class="block text-xs font-semibold text-gray-500 mb-1.5">Search</label>
        <input type="text" name="search" id="search" value="{{ request('search') }}"
               placeholder="Student name or LRN..."
               class="w-full max-w-md border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-6">
        <div class="px-2 sm:px-3">
            <label for="status" class="block text-xs font-semibold text-gray-500 mb-1.5">Status</label>
            <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                <option value="">All</option>
                <option value="enrolled" {{ request('status') === 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                <option value="temporarily_enrolled" {{ request('status') === 'temporarily_enrolled' ? 'selected' : '' }}>Temporarily Enrolled</option>
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="grade_level" class="block text-xs font-semibold text-gray-500 mb-1.5">Grade Level</label>
            <select name="grade_level" id="grade_level" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                <option value="">All</option>
                @foreach ($gradeLevels ?? [] as $level)
                    <option value="{{ $level['value'] }}" {{ request('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="px-2 sm:px-3">
            <label for="per_page" class="block text-xs font-semibold text-gray-500 mb-1.5">Rows</label>
            <select name="per_page" id="per_page" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                @foreach ([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" {{ (int) request('per_page', 20) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <button type="submit" class="rounded-lg px-6 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">Apply filters</button>
    </div>
</form>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-6 py-4 border-b border-white/20 bg-white/40">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Students</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">LRN</th>
                    <th class="px-6 py-4">Grade</th>
                    <th class="px-6 py-4">Section</th>
                    <th class="px-6 py-4">Cluster</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">School Year</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @forelse($students as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $application = $student?->application;
                        $name = $application
                            ? $application->last_name . ', ' . $application->first_name . ($application->middle_name ? ' ' . $application->middle_name : '')
                            : ($student?->user?->name ?? '—');
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                    @endphp
                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-gray-800">{{ $name }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700 font-mono">{{ $student?->lrn ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-gray-700">{{ $gradeLabel }}</span>
                            @if(!empty($enrollment->semester))
                                <span class="text-xs text-gray-500 block">Sem {{ ucfirst($enrollment->semester) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700">{{ $enrollment->section?->section_name ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700">{{ $enrollment->cluster?->cluster_name ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($enrollment->enrollment_status ?? '') === 'enrolled')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-white" style="background-color: #296374;">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white/80"></span> Enrolled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-blue-700 bg-blue-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Temporarily Enrolled
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $enrollment->academicYear?->school_year ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('registrar.students.show', $student) }}" class="inline-block text-white text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-lg shadow-md hover:opacity-90 transition-opacity" style="background-color: #296374;">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No students found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-white/20 bg-white/20">
        {{ $students->links() }}
    </div>
</div>
@endsection