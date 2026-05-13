@extends('users.teacher.layout')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Teacher Dashboard</h1>
        <p class="text-gray-600 text-sm md:text-base">Welcome to your faculty portal.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="shadow-xl rounded-2xl p-6" style="background-color: #296374;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Teacher Name</p>
            <p class="text-xl font-bold text-white">
                {{ $staff ? trim($staff->last_name . ', ' . $staff->first_name . ' ' . ($staff->middle_name ?? '') . ' ' . ($staff->suffix ?? '')) : Auth::user()->name }}
            </p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #10b981;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Assigned Sections</p>
            <p class="text-2xl font-bold text-white">{{ $sectionsCount }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #f59e0b;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Assigned Subjects</p>
            <p class="text-2xl font-bold text-white">{{ $subjectsCount }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #8b5cf6;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Total Students</p>
            <p class="text-2xl font-bold text-white">{{ $studentsCount }}</p>
        </div>
    </div>

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 p-6">
        <h3 class="text-base font-bold text-[#296374] uppercase tracking-wider mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-bold text-white uppercase tracking-wide shadow-lg transition-all hover:-translate-y-1" style="background-color: #296374;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                View Assigned Subjects
            </a>
        </div>
    </div>
</div>
@endsection
