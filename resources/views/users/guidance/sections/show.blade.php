@extends('users.guidance.layout')

@section('title', 'Section Details')

@section('content')
<nav class="mb-6 flex items-center gap-2 text-sm">
    <a href="{{ route('guidance.sections.index') }}" class="text-gray-500 hover:text-[#296374] font-medium transition-colors">Sectioning</a>
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
    <span class="text-gray-700 font-semibold">{{ $section->name }}</span>
</nav>

<div class="mb-8">
    <div class="flex items-center gap-4 flex-wrap">
        <div class="h-14 w-14 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">{{ $section->name }}</h1>
            <p class="text-gray-500 text-sm mt-0.5">Section details and enrolled students</p>
        </div>
    </div>
</div>

@php
    $cap = (int) ($section->capacity ?? 0);
    $count = $section->enrollments->count();
    $pct = $cap > 0 ? min(100, (int) round(100 * $count / $cap)) : 0;
    $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
@endphp

<div class="rounded-xl bg-white/95 backdrop-blur-sm shadow-xl border border-gray-200/80 overflow-hidden mb-6">
    <div class="px-6 py-5 border-b border-gray-200" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);">
        <h2 class="text-sm font-bold uppercase tracking-wider flex items-center gap-2" style="color: #296374;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            Room & section info
        </h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: #296374;">
                    <span class="text-white text-sm font-bold">{{ strtoupper(str_replace('grade_', 'G', $section->grade_level)) }}</span>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade level</p>
                    <p class="text-base font-bold text-gray-800 mt-0.5">{{ $gradeLabel }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Cluster</p>
                    <p class="text-base font-bold text-gray-800 mt-0.5">{{ $section->cluster?->name ?? '—' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Room</p>
                    <p class="text-base font-bold text-gray-800 mt-0.5 font-mono">{{ $section->room ?? '—' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: #296374;">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Capacity</p>
                    <p class="text-base font-bold text-gray-800 mt-0.5">{{ $count }} / {{ $cap ?: '—' }}</p>
                    @if ($cap > 0)
                    <div class="mt-2 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%;"></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="rounded-xl bg-white/95 backdrop-blur-sm shadow-xl border border-gray-200/80 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);">
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider flex items-center gap-2" style="color: #296374;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Students in this section
            </h2>
            <p class="text-sm text-gray-500 mt-1">{{ $section->enrollments->count() }} student(s) enrolled</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('guidance.sections.class-list', $section) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print class list
            </a>
            <a href="{{ route('guidance.sections.class-list.pdf', $section) }}" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white border border-emerald-600 hover:bg-emerald-700 transition-colors" style="background-color: #059669;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold uppercase tracking-wider border-b-2 border-gray-200" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); color: #296374;">
                    <th class="px-6 py-4 w-12">#</th>
                    <th class="px-6 py-4">Student name</th>
                    <th class="px-6 py-4">LRN</th>
                    <th class="px-6 py-4">Learner type</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($section->enrollments as $index => $enrollment)
                    @php $student = $enrollment->student; $application = $student?->application; @endphp
                    <tr class="hover:bg-gray-50/80 transition-colors bg-white">
                        <td class="px-6 py-4">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-600">{{ $index + 1 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-gray-800">{{ $application ? $application->last_name . ', ' . $application->first_name . ($application->middle_name ? ' ' . $application->middle_name : '') : '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-gray-700">{{ $student?->lrn ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $enrollment->learner_type ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-500">
                                <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                                <p class="font-medium text-gray-600">No students assigned yet</p>
                                <p class="text-sm">Students will appear here once they are enrolled and assigned to this section.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="flex justify-end mt-8">
    <a href="{{ route('guidance.sections.index') }}" class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-bold text-white shadow-md hover:shadow-lg hover:opacity-95 transition-all" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        Back to Sectioning
    </a>
</div>
@endsection
