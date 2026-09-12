@extends('users.principal.layout')

@section('title', 'Student Proficiency Levels')

@section('content')
@php
$proficiencyOptions = collect($levels)->pluck('label')->all();
$selectedYearLabel = $selectedAcademicYear === 'all'
? 'All school years'
: (($academicYears ?? collect())->firstWhere('SY_ID', (int) ($selectedAcademicYear ?: $activeYear?->SY_ID))?->school_year ?? 'Current year');
$sectionList = $sections instanceof \Illuminate\Pagination\LengthAwarePaginator ? $sections->getCollection() : $sections;
$proficiencyChartData = collect($levels)
->map(fn ($level) => [
'label' => $level['label'],
'total' => $summary[$level['label']] ?? 0,
])
->push([
'label' => 'Pending Grades',
'total' => $incompleteCount,
])
->values()
->all();
$proficiencyChartPayload = [
'data' => $proficiencyChartData,
'subjectTitle' => $selectedSubject?->title,
'subjectCode' => $selectedSubject?->code,
];
@endphp

<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Student Proficiency Levels</h1>
            @if (!isset($activeYear) || !$activeYear)
            <p class="mt-1 text-sm font-medium text-amber-600">No active school year set. Choose a school year filter to review subject proficiency.</p>
            @elseif ($selectedSubject)
            <p class="mt-1 text-sm text-gray-500">{{ $selectedSubject->code }} — {{ $selectedSubject->title }} · {{ $selectedYearLabel }}</p>
            @else
            <p class="mt-1 text-sm text-gray-500">Select a subject to view section proficiency by learner.</p>
            @endif
        </div>
    </div>
</div>

<form method="GET" action="{{ route('principal.proficiency-levels') }}" class="mb-6">
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
        <select name="subject_id" class="h-10 min-w-[220px] flex-1 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">Select subject</option>
            @foreach($subjects as $subject)
            <option value="{{ $subject->subject_ID }}" {{ (string) $selectedSubjectId === (string) $subject->subject_ID ? 'selected' : '' }}>
                {{ $subject->code }} — {{ $subject->title }}
            </option>
            @endforeach
        </select>

        <select name="academic_year_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">Current year</option>
            <option value="all" {{ $selectedAcademicYear === 'all' ? 'selected' : '' }}>All years</option>
            @foreach($academicYears ?? [] as $academicYear)
            <option value="{{ $academicYear->SY_ID }}" {{ (string) $selectedAcademicYear === (string) $academicYear->SY_ID ? 'selected' : '' }}>
                {{ $academicYear->school_year }}
            </option>
            @endforeach
        </select>

        <select name="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">All grades</option>
            @foreach($gradeLevels as $gradeLevel)
            <option value="{{ $gradeLevel->grade_ID }}" {{ (string) $selectedGradeLevel === (string) $gradeLevel->grade_ID ? 'selected' : '' }}>
                {{ $gradeLevel->grade_label }}
            </option>
            @endforeach
        </select>

        <select name="proficiency_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition hover:border-[#296374]/40 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">All proficiency levels</option>
            @foreach($proficiencyOptions as $proficiency)
            <option value="{{ $proficiency }}" {{ $selectedProficiencyLevel === $proficiency ? 'selected' : '' }}>{{ $proficiency }}</option>
            @endforeach
            <option value="pending" {{ $selectedProficiencyLevel === 'pending' ? 'selected' : '' }}>Pending Grades</option>
        </select>

        <div class="relative min-w-[180px] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
            </svg>
            <input type="search" name="search" id="search" value="{{ $search }}" placeholder="Search name or LRN"
                class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
        </div>

        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"></path>
            </svg>
            Apply
        </button>
        <a href="{{ route('principal.proficiency-levels') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
    </div>
</form>

@if ($selectedSubject)
<div class="mb-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
    @foreach($levels as $level)
    @php
    $cardClasses = match ($level['label']) {
    'Advanced' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'Proficient' => 'border-[#296374]/20 bg-[#296374]/10 text-[#296374]',
    'Approaching Proficiency' => 'border-blue-200 bg-blue-50 text-blue-700',
    'Developing' => 'border-amber-200 bg-amber-50 text-amber-700',
    'Beginning' => 'border-red-200 bg-red-50 text-red-700',
    default => 'border-gray-200 bg-white text-gray-700',
    };
    $count = $summary[$level['label']] ?? 0;
    @endphp
    <div class="rounded-xl border px-4 py-3 shadow-sm ring-1 ring-white/60 {{ $cardClasses }}">
        <p class="text-[11px] font-bold uppercase tracking-wider opacity-75">{{ $level['range'] }}</p>
        <div class="mt-2 flex items-baseline justify-between gap-3">
            <p class="text-sm font-bold">{{ $level['label'] }}</p>
            <p class="text-2xl font-extrabold">{{ $count }}</p>
        </div>
    </div>
    @endforeach
    <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-700 shadow-sm ring-1 ring-white/60">
        <p class="text-[11px] font-bold uppercase tracking-wider opacity-75">No Average</p>
        <div class="mt-2 flex items-baseline justify-between gap-3">
            <p class="text-sm font-bold">Pending Grades</p>
            <p class="text-2xl font-extrabold">{{ $incompleteCount }}</p>
        </div>
    </div>
</div>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white/90 px-4 py-3 shadow-sm">
    <p class="text-sm font-semibold text-gray-700">{{ $sections->total() }} {{ Str::plural('section', $sections->total()) }} · {{ $totalStudents }} {{ Str::plural('student', $totalStudents) }}</p>
    <p class="text-xs text-gray-500">Subject averages use the DepEd proficiency scale.</p>
</div>

@if ($sectionList->isNotEmpty())
<div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
    <div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            @foreach ($sectionList as $row)
            @php
            $section = $row['section'];
            $students = $row['students'];
            $cap = $row['capacity'];
            $count = $row['student_count'];
            $pct = $row['capacity_percent'];
            $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-[#296374]');
            $badgeColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
            $statusLabel = $pct >= 100 ? 'full' : ($pct >= 80 ? 'near full' : 'open');
            $gradeLabel = $section->gradeLevel?->grade_label ?? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
            $gradeInitial = strtoupper(str_replace(['Grade ', 'grade_'], ['G', 'G'], $section->gradeLevel?->grade_label ?? $section->grade_level));
            $schoolYear = $section->academicYear?->school_year ?? 'N/A';
            @endphp

            <div class="relative flex min-h-[190px] flex-col rounded-lg border border-gray-200/80 bg-white p-5 shadow-md shadow-slate-200/70">
                <span class="absolute right-0 top-4 rounded-l-sm {{ $badgeColor }} px-3 py-1 text-[11px] font-bold lowercase text-white shadow-sm">{{ $statusLabel }}</span>

                <div class="flex items-start gap-4 pr-16">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white shadow-md" style="background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);">
                        {{ $gradeInitial }}
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-gray-800">{{ $section->name }}</h2>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $gradeLabel }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="truncate">{{ $schoolYear }}</span>
                    </div>
                    @if ($section->cluster?->name)
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                        <span class="truncate">{{ $section->cluster->name }}</span>
                    </div>
                    @endif
                    @if ($section->room)
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path>
                        </svg>
                        <span class="truncate font-mono">{{ $section->room }}</span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="truncate">{{ $row['teacher_name'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 11-8 0 4 4 0 018 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="font-semibold text-gray-700">{{ $count }}</span>
                        <span>of {{ $cap ?: 'unlimited' }} students</span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        <span>Capacity</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-200">
                        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>

                <details class="group mt-5 border-t border-gray-100 pt-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2.5 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 [&::-webkit-details-marker]:hidden">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                            Students
                            <span class="ml-1 normal-case tracking-normal text-gray-500">({{ $students->count() }})</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>

                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                        @forelse ($students as $student)
                        @php
                        $badgeClasses = match ($student['proficiency']['label'] ?? null) {
                        'Advanced' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'Proficient' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                        'Approaching Proficiency' => 'bg-blue-50 text-blue-700 ring-blue-200',
                        'Developing' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'Beginning' => 'bg-red-50 text-red-700 ring-red-200',
                        default => 'bg-gray-100 text-gray-700 ring-gray-200',
                        };
                        @endphp
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-100 bg-white px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-gray-800">{{ $student['name'] }}</p>
                                <p class="text-[10px] text-gray-500">
                                    LRN {{ $student['lrn'] }}
                                    ·
                                    {{ $student['subject_average'] !== null ? number_format($student['subject_average'], 0) : '—' }}
                                </p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-1 text-[10px] font-bold ring-1 {{ $badgeClasses }}">
                                {{ $student['proficiency']['label'] ?? 'Pending Grades' }}
                            </span>
                        </div>
                        @empty
                        <p class="text-xs text-gray-500">No students match the current filters.</p>
                        @endforelse
                    </div>
                </details>
            </div>
            @endforeach
        </div>

        @if ($sections instanceof \Illuminate\Pagination\LengthAwarePaginator && $sections->hasPages())
        <div class="mt-6 rounded-lg border border-gray-200/80 bg-white/90 px-6 py-4 shadow-sm">
            {{ $sections->links() }}
        </div>
        @endif
    </div>

    <aside class="rounded-xl border border-gray-200 bg-white p-5 shadow-lg shadow-gray-200/70 h-fit">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-600">Distribution Chart</h2>
                <p class="mt-1 text-xs text-gray-500">Learners in {{ $selectedSubject->title }} by proficiency level.</p>
            </div>
            @if ($totalStudents > 0)
            <button type="button" id="downloadProficiencyChart" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-[11px] font-bold uppercase tracking-wide text-gray-700 shadow-sm transition hover:border-[#296374]/40 hover:bg-[#296374]/5 hover:text-[#296374]">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download
            </button>
            @endif
        </div>
        <div class="relative mx-auto h-80 max-w-xs">
            <canvas id="proficiencyLevelsChart"></canvas>
            @if ($totalStudents === 0)
            <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No chart data yet</div>
            @endif
        </div>
    </aside>
</div>
@else
<div class="rounded-lg border border-gray-200/80 bg-white/95 px-6 py-16 text-center shadow-md">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
    </div>
    <p class="mt-4 font-medium text-gray-600">No sections found for this subject</p>
    <p class="mt-1 text-sm text-gray-500">Try another school year, grade level, or proficiency filter.</p>
</div>
@endif
@else
<div class="rounded-lg border border-gray-200/80 bg-white/95 px-6 py-16 text-center shadow-md">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
    </div>
    <p class="mt-4 font-medium text-gray-600">Choose a subject to begin</p>
    <p class="mt-1 text-sm text-gray-500">Sections teaching that subject will appear here with learner proficiency levels.</p>
</div>
@endif

@if ($selectedSubject && $sectionList->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
    window.proficiencyChartPayload = @json($proficiencyChartPayload);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var chartElement = document.getElementById('proficiencyLevelsChart');
        var payload = window.proficiencyChartPayload || {};
        var proficiencyLevelsData = payload.data || [];
        var subjectTitle = payload.subjectTitle || '';
        var subjectCode = payload.subjectCode || '';

        if (!chartElement || proficiencyLevelsData.length === 0) {
            return;
        }

        Chart.register(ChartDataLabels);

        var chart = new Chart(chartElement, {
            type: 'pie',
            data: {
                labels: proficiencyLevelsData.map(function(item) {
                    return item.label;
                }),
                datasets: [{
                    data: proficiencyLevelsData.map(function(item) {
                        return item.total;
                    }),
                    backgroundColor: ['#10b981', '#296374', '#3b82f6', '#f59e0b', '#ef4444', '#64748b'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        bottom: 8
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            generateLabels: function(chartInstance) {
                                var data = chartInstance.data;
                                var dataset = data.datasets[0] || {};
                                var values = dataset.data || [];

                                return (data.labels || []).map(function(label, index) {
                                    var value = values[index] || 0;
                                    var fill = Array.isArray(dataset.backgroundColor) ?
                                        dataset.backgroundColor[index] :
                                        dataset.backgroundColor;

                                    return {
                                        text: label + ' (' + value + ')',
                                        fillStyle: fill,
                                        strokeStyle: '#ffffff',
                                        lineWidth: 1,
                                        hidden: !chartInstance.getDataVisibility(index),
                                        index: index
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(sum, value) {
                                    return sum + value;
                                }, 0);
                                var value = context.parsed;
                                var percent = total > 0 ? Math.round((value / total) * 100) : 0;
                                return context.label + ': ' + value + ' student(s) (' + percent + '%)';
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: {
                            weight: '700',
                            size: 12
                        },
                        formatter: function(value) {
                            return value > 0 ? value : '';
                        },
                        textStrokeColor: 'rgba(0, 0, 0, 0.25)',
                        textStrokeWidth: 2
                    }
                }
            }
        });

        var downloadButton = document.getElementById('downloadProficiencyChart');
        if (downloadButton) {
            downloadButton.addEventListener('click', function() {
                var sourceCanvas = chart.canvas;
                var pixelRatio = chart.currentDevicePixelRatio || window.devicePixelRatio || 1;
                var padding = 24;
                var headerHeight = 56;
                var cssWidth = chart.width;
                var cssHeight = chart.height;

                var exportCanvas = document.createElement('canvas');
                exportCanvas.width = Math.round((cssWidth + (padding * 2)) * pixelRatio);
                exportCanvas.height = Math.round((cssHeight + headerHeight + (padding * 2)) * pixelRatio);

                var context = exportCanvas.getContext('2d');
                context.scale(pixelRatio, pixelRatio);
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, cssWidth + (padding * 2), cssHeight + headerHeight + (padding * 2));

                context.textAlign = 'center';
                context.fillStyle = '#1f2937';
                context.font = 'bold 16px system-ui, -apple-system, sans-serif';
                context.fillText('Proficiency Distribution', (cssWidth / 2) + padding, padding + 18);

                context.fillStyle = '#6b7280';
                context.font = '12px system-ui, -apple-system, sans-serif';
                context.fillText((subjectCode ? subjectCode + ' — ' : '') + subjectTitle, (cssWidth / 2) + padding, padding + 38);

                context.drawImage(sourceCanvas, padding, padding + headerHeight, cssWidth, cssHeight);

                var link = document.createElement('a');
                var safeSubject = String(subjectCode || 'subject').replace(/[^a-z0-9\-]+/gi, '-').toLowerCase();
                link.download = 'proficiency-chart-' + safeSubject + '.png';
                link.href = exportCanvas.toDataURL('image/png');
                link.click();
            });
        }
    });
</script>
@endif
@endsection