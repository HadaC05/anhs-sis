@extends('users.teacher.layout')

@section('title', 'Advisory Sections')

@section('content')
<div class="mb-8 border-b-2 border-[#296374] pb-3"><h1 class="text-xl font-bold tracking-tight text-slate-950 md:text-2xl">Advisory</h1></div>

@if (session('status'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
@endif

@if ($sections->isNotEmpty())
    <div class="space-y-8">
        @foreach ($sections as $section)
            @php
                $capacity = (int) ($section->capacity ?? 0);
                $learnerCount = (int) ($section->active_enrollments_count ?? 0);
                $occupancy = $capacity > 0 ? min(100, (int) round($learnerCount * 100 / $capacity)) : 0;
                $status = $occupancy >= 100 ? 'Full' : ($occupancy >= 80 ? 'Near full' : 'Open');
                $barClass = $occupancy >= 100 ? 'bg-red-500' : ($occupancy >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
                $details = [
                    ['label' => 'Grade Level', 'value' => $gradeLabel],
                    ['label' => 'School Year', 'value' => $section->academicYear?->school_year ?? 'N/A'],
                    ['label' => 'Room', 'value' => $section->room ?: 'Not set'],
                ];
                $tools = [
                    ['label' => 'Class List', 'description' => 'View and import learners', 'route' => 'teacher.advisory.class-list.index', 'color' => 'bg-[#296374]/10 text-[#296374]', 'icon' => 'users'],
                    ['label' => 'Student Grades', 'description' => 'Review grades and forms', 'route' => 'teacher.advisory.show', 'color' => 'bg-emerald-100 text-emerald-700', 'icon' => 'chart'],
                    ['label' => 'Observed Values', 'description' => 'Record learner behavior', 'route' => 'teacher.advisory.observed-values', 'color' => 'bg-violet-100 text-violet-700', 'icon' => 'star'],
                    ['label' => 'Attendance', 'description' => 'Manage SF9 and SF2 records', 'route' => 'teacher.advisory.attendance', 'color' => 'bg-sky-100 text-sky-700', 'icon' => 'calendar'],
                    ['label' => 'Promotion', 'description' => 'Evaluate learner eligibility', 'route' => 'teacher.advisory.promotions.index', 'color' => 'bg-amber-100 text-amber-700', 'icon' => 'trend'],
                ];
            @endphp
            <section class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <div class="lg:col-span-7 xl:col-span-8">
                    <div class="overflow-hidden rounded-md border-2 border-slate-300 bg-white shadow-md shadow-slate-200/70">
                        <div class="flex items-center justify-between gap-4 border-b-2 border-[#1f4e5c] bg-[#214e5c] px-5 py-3"><h2 class="text-sm font-bold uppercase tracking-[0.16em] text-white">Advisory Class Snapshot</h2><span class="rounded-full border border-white/40 bg-white/20 px-2.5 py-1 text-xs font-bold text-white">{{ $status }}</span></div>
                        <div class="border-b-2 border-slate-200 px-5 py-5"><div class="flex items-start gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center border-2 border-[#296374] bg-[#296374]/10 text-sm font-extrabold text-[#173f4b]">{{ strtoupper(str_replace('grade_', 'G', $section->grade_level)) }}</div><div class="min-w-0"><h3 class="text-lg font-bold tracking-tight text-slate-950">{{ $section->name }}</h3><p class="mt-1 text-sm font-medium text-slate-700">{{ $learnerCount }} active {{ Str::plural('learner', $learnerCount) }}</p></div></div></div>
                        <div class="px-5 py-5"><dl class="divide-y-2 divide-slate-200 border-y-2 border-slate-200">
                            @foreach ($details as $detail)<div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-xs font-bold uppercase tracking-wide text-slate-600">{{ $detail['label'] }}</dt><dd class="text-right text-sm font-semibold text-slate-950">{{ $detail['value'] }}</dd></div>@endforeach
                            <div class="py-3"><div class="mb-2 flex justify-between text-xs font-bold uppercase tracking-wide text-slate-600"><span>Class capacity</span><span class="text-slate-900">{{ $capacity ? $learnerCount.' of '.$capacity : $learnerCount.' learners' }}</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-300"><div class="h-full rounded-full {{ $barClass }}" style="width: {{ $capacity ? $occupancy : 0 }}%"></div></div></div>
                        </dl></div>
                    </div>
                </div>
                <aside class="lg:col-span-5 xl:col-span-4"><h2 class="mb-3 text-sm font-bold uppercase tracking-[0.16em] text-slate-700">Advisory Tools</h2><div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                    @foreach ($tools as $tool)
                        <a href="{{ route($tool['route'], $section) }}" class="group flex items-center justify-between gap-3 rounded-md border-2 border-slate-300 bg-white px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:border-[#296374] hover:bg-[#296374]/5 hover:shadow-md"><div class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-current/20 {{ $tool['color'] }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">@if($tool['icon'] === 'users')<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0z"/>@elseif($tool['icon'] === 'chart')<path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-5m4 5V8m4 8V6"/>@elseif($tool['icon'] === 'star')<path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.75 5.57L21 9.48l-4.5 4.38 1.06 6.19L12 17.17l-5.56 2.88 1.06-6.19L3 9.48l6.25-.91L12 3z"/>@elseif($tool['icon'] === 'calendar')<path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14v15H5z"/>@else<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h6m0 0v6m0-6-8 8-4-4-4 4"/>@endif</svg></span><div class="min-w-0"><p class="text-sm font-bold text-slate-950">{{ $tool['label'] }}</p><p class="truncate text-xs font-medium text-slate-600">{{ $tool['description'] }}</p></div></div><svg class="h-4 w-4 shrink-0 text-slate-500 transition group-hover:translate-x-0.5 group-hover:text-[#214e5c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg></a>
                    @endforeach
                </div></aside>
            </section>
        @endforeach
    </div>
@else
    <div class="rounded-md border border-slate-300 bg-white px-6 py-16 text-center shadow-sm"><p class="font-medium text-gray-600">No advisory section assigned yet</p><p class="mt-1 text-sm text-gray-500">Advisory classes will appear here once you are assigned as a class adviser.</p></div>
@endif
@endsection
