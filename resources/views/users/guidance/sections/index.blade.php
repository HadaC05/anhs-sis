@extends('users.guidance.layout')

@section('title', 'Sectioning')

@section('content')
<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Sectioning</h1>
            <p class="text-gray-500 text-sm">View and manage class sections for the current school year</p>
        </div>
    </div>
    @if (!isset($activeYear) || !$activeYear)
        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <p class="text-amber-800 text-sm font-medium">No active school year set. Sections will appear here once an admin sets the current school year.</p>
        </div>
    @endif
</div>

<form method="GET" action="{{ route('guidance.sections.index') }}" class="mb-6 rounded-xl bg-white/90 backdrop-blur-sm border border-gray-200/80 shadow-sm p-4 flex flex-wrap items-end gap-4 max-w-2xl">
    <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
    <div class="min-w-[200px] flex-1">
        <label for="grade_level" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Grade level</label>
        <select name="grade_level" id="grade_level" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/30 focus:border-[#296374] outline-none bg-white shadow-sm transition-shadow">
            <option value="">All levels</option>
            @foreach ($gradeLevels ?? [] as $level)
                <option value="{{ $level['value'] }}" {{ request('grade_level') == $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
            @endforeach
        </select>
    </div>
    @if ($showClusterFilter ?? false)
    <div class="min-w-[200px] flex-1">
        <label for="cluster_id" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Cluster</label>
        <select name="cluster_id" id="cluster_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#296374]/30 focus:border-[#296374] outline-none bg-white shadow-sm transition-shadow">
            <option value="">All clusters</option>
            @foreach ($clusters ?? [] as $cluster)
                <option value="{{ $cluster->cluster_ID }}" {{ request('cluster_id') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <button type="submit" class="rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-md hover:shadow-lg hover:opacity-95 transition-all inline-flex items-center gap-2" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        Filter
    </button>
</form>

@php
    $sectionsList = $sections ?? collect();
    $totalCapacity = $totalCapacity ?? 0;
    $totalEnrolled = $totalEnrolled ?? 0;
@endphp

@if ($sectionsList->isNotEmpty())
<div class="mb-6 flex flex-wrap items-center gap-3">
    <a href="{{ route('guidance.sections.master-list') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Print master list
    </a>
    <a href="{{ route('guidance.sections.master-list.pdf') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white border border-emerald-600 hover:bg-emerald-700 transition-colors" style="background-color: #059669;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        Download PDF
    </a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl bg-white/90 backdrop-blur-sm border border-gray-200/80 shadow-sm p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sections</p>
        <p class="text-2xl font-bold text-gray-800">{{ $sections->total() }}</p>
    </div>
    <div class="rounded-xl bg-white/90 backdrop-blur-sm border border-gray-200/80 shadow-sm p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total capacity</p>
        <p class="text-2xl font-bold text-gray-800">{{ $totalCapacity ?: '-' }}</p>
    </div>
    <div class="rounded-xl bg-white/90 backdrop-blur-sm border border-gray-200/80 shadow-sm p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Enrolled</p>
        <p class="text-2xl font-bold" style="color: #296374;">{{ $totalEnrolled }}</p>
    </div>
</div>
@endif

<div class="rounded-xl bg-white/95 backdrop-blur-sm shadow-xl border border-gray-200/80 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold uppercase tracking-wider border-b-2 border-gray-200" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); color: #296374;">
                    <th class="px-6 py-4">Grade level</th>
                    <th class="px-6 py-4">Section</th>
                    @if ($showClusterColumn ?? false)
                    <th class="px-6 py-4">Cluster</th>
                    @endif
                    <th class="px-6 py-4">Room</th>
                    <th class="px-6 py-4 w-40">Capacity</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sectionsList as $section)
                    @php
                        $cap = (int) ($section->capacity ?? 0);
                        $count = (int) ($section->enrollments_count ?? 0);
                        $pct = $cap > 0 ? min(100, (int) round(100 * $count / $cap)) : 0;
                        $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition-colors bg-white">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <span class="h-8 w-8 rounded-lg flex items-center justify-center text-xs font-bold text-white" style="background-color: #296374;">{{ strtoupper(str_replace('grade_', 'G', $section->grade_level)) }}</span>
                                {{ $gradeLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-gray-800">{{ $section->name }}</span>
                        </td>
                        @if ($showClusterColumn ?? false)
                        <td class="px-6 py-4">
                            @if($section->cluster?->name)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $section->cluster->name }}</span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700 font-mono">{{ $section->room ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 w-40">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 min-w-0 h-2 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">{{ $count }}/{{ $cap ?: '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('guidance.sections.show', $section) }}" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold text-white shadow-md hover:shadow-lg hover:opacity-95 transition-all" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
                                View
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($showClusterColumn ?? false) ? 6 : 5 }}" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-500">
                                <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <p class="font-medium text-gray-600">No sections found</p>
                                <p class="text-sm">Try changing the grade level filter or check back once sections are created for this school year.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($sections)
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
            {{ $sections->links() }}
        </div>
    @endisset
</div>
@endsection
