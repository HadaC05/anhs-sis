@extends($layout ?? 'users.guidance.layout')

@section('title', 'Age Alignment Report')

@section('content')
@php
    $alignmentOptions = [
        'all' => 'All alignments',
        'appropriate' => 'Age appropriate',
        'overage' => 'Above range',
        'underage' => 'Below range',
    ];
    $statusClasses = [
        'appropriate' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'overage' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'underage' => 'bg-sky-50 text-sky-700 ring-sky-200',
    ];
    $statusLabels = [
        'appropriate' => 'Appropriate',
        'overage' => 'Above range',
        'underage' => 'Below range',
    ];
    $alignment = $alignment ?? 'overage';
    $reportRoute = $reportRoute ?? 'guidance.reports.age-for-grade';
    $showEnrollmentAction = $showEnrollmentAction ?? true;
    $expectedRanges = \App\Support\PlacementAssessmentAdvisor::expectedRanges();
@endphp

<div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-stretch xl:justify-between">
    <div class="flex min-w-0 flex-col justify-center">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Age Alignment Report</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-600 md:text-base">Review student ages against expected grade-level ranges. Placement tests are suggested for overage students only.</p>
    </div>
    <div class="w-full rounded-xl border border-[#296374]/20 bg-[#296374]/5 p-4 shadow-sm xl:max-w-xl">
        <div class="mb-3 flex items-start gap-2">
            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#296374] text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </span>
            <div>
                <p class="text-sm font-bold text-[#296374]">Expected age by grade</p>
                <p class="text-xs text-gray-500">Measured at the start of the school year</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
            @foreach ($expectedRanges as $range)
                <div class="rounded-lg border border-gray-100 bg-white px-2 py-2 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">{{ $range['label'] }}</p>
                    <p class="mt-0.5 text-sm font-bold text-gray-900">{{ $range['minimum_age'] }}–{{ $range['maximum_age'] }}</p>
                    <p class="text-[10px] text-gray-400">yrs</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

@if (! $activeYear)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        No active school year is set. Choose a school year filter to review enrollments.
    </div>
@endif

<form method="GET" action="{{ route($reportRoute) }}" class="mb-6">
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
        <select name="alignment" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            @foreach ($alignmentOptions as $value => $label)
                <option value="{{ $value }}" {{ $alignment === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="grade_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">All grades</option>
            @foreach ($gradeLevels ?? [] as $level)
                <option value="{{ $level['value'] }}" {{ request('grade_level') == $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
            @endforeach
        </select>

        <select name="academic_year_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            <option value="">Current year</option>
            <option value="all" {{ request('academic_year_id') === 'all' ? 'selected' : '' }}>All years</option>
            @foreach ($academicYears ?? [] as $year)
                <option value="{{ $year->SY_ID }}" {{ request('academic_year_id') == $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
            @endforeach
        </select>

        <div class="relative min-w-[200px] flex-1">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or LRN"
                class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
        </div>

        <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
            @foreach ([10, 15, 25, 50] as $size)
                <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>{{ $size }} / page</option>
            @endforeach
        </select>

        <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Apply</button>
        <a href="{{ route($reportRoute) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
    </div>
</form>

<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Reviewed</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary['all'] ?? 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Average age</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ isset($summary['average_age']) ? number_format($summary['average_age'], 1) : '—' }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Appropriate</p>
        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($summary['appropriate'] ?? 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Above range</p>
        <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($summary['overage'] ?? 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Below range</p>
        <p class="mt-1 text-2xl font-bold text-sky-700">{{ number_format($summary['underage'] ?? 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Marked for test</p>
        <p class="mt-1 text-2xl font-bold text-[#296374]">{{ number_format($summary['marked_for_test'] ?? 0) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="xl:col-span-9">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-bold text-gray-900">Student records</h2>
                <p class="mt-1 text-xs text-gray-500">Pending and active enrollments with recorded birthdates</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Grade</th>
                            <th class="px-5 py-3">Age</th>
                            <th class="px-5 py-3">Alignment</th>
                            @if ($showEnrollmentAction)
                                <th class="px-5 py-3 text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            @php
                                $enrollment = $row['enrollment'];
                                $student = $row['student'];
                                $assessment = $row['assessment'];
                                $recommendation = $row['recommendation'] ?? null;
                                $status = $assessment['status'];
                                $studentName = trim(($student->last_name ?? '').', '.($student->first_name ?? '').' '.($student->middle_name ?? ''));
                            @endphp
                            <tr class="transition hover:bg-gray-50/80 {{ $enrollment->hasPlacementStatusMark() ? 'bg-[#296374]/[0.03]' : '' }}">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-gray-900">{{ $studentName ?: 'Unnamed student' }}</p>
                                    <p class="font-mono text-xs text-gray-500">{{ $student->lrn ?? '-' }}</p>
                                    @if ($enrollment->hasPlacementStatusMark())
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ \App\Models\PlacementStatus::badgeClasses($enrollment->placement_status) }}">{{ $enrollment->placement_status_label }}</span>
                                    @elseif ($recommendation)
                                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">Review suggested</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800">{{ $enrollment->grade_level ? \App\Models\GradeLevel::valueToLabel($enrollment->grade_level) : '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $enrollment->section?->name ?? 'Unassigned' }}</p>
                                </td>
                                <td class="px-5 py-3 font-semibold text-gray-900">{{ $assessment['age'] }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700 ring-gray-200' }}">
                                        {{ $statusLabels[$status] ?? ucfirst($status) }}
                                    </span>
                                    @if ($recommendation)
                                        <p class="mt-1 text-[11px] text-gray-500">{{ $recommendation['summary'] }}</p>
                                    @endif
                                </td>
                                @if ($showEnrollmentAction)
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('guidance.enrollments.show', $enrollment) }}" title="View enrollment" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-[#296374]/30 hover:bg-[#296374]/5 hover:text-[#296374]">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7S3.73 16.06 2.46 12z"></path>
                                            </svg>
                                            <span class="sr-only">View enrollment</span>
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showEnrollmentAction ? 5 : 4 }}" class="px-5 py-14 text-center text-gray-500">
                                    <p class="font-semibold text-gray-700">No students match these filters</p>
                                    <p class="mt-1 text-sm">Try another alignment, grade level, school year, or search term.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->total() > 0)
                <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-5 py-3 text-sm text-gray-600 md:flex-row md:items-center md:justify-between">
                    <span>Showing {{ $rows->firstItem() }} to {{ $rows->lastItem() }} of {{ $rows->total() }} records</span>
                    <div>{{ $rows->withQueryString()->links() }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="xl:col-span-3">
        <div class="space-y-6 xl:sticky xl:top-24">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-gray-900">Alignment ratio</h2>
                <p class="mt-1 text-xs text-gray-500">Share of reviewed students by age status</p>
                <div class="relative mx-auto mt-4 h-52 max-w-[220px]">
                    <canvas id="alignmentRatioChart"></canvas>
                    @if (($ageAlignment['reviewed'] ?? 0) === 0)
                        <div class="absolute inset-0 flex items-center justify-center text-center text-xs text-gray-400">No data for current filters</div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-gray-900">Average age by grade</h2>
                <p class="mt-1 text-xs text-gray-500">Actual vs expected average at school year start</p>
                <div class="relative mt-4 h-64">
                    <canvas id="averageAgeByGradeChart"></canvas>
                    @if (count($ageAlignment['by_grade'] ?? []) === 0)
                        <div class="absolute inset-0 flex items-center justify-center text-center text-xs text-gray-400">No data for current filters</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ageAlignment = @json($ageAlignment);
        var byGrade = ageAlignment.by_grade || [];

        if ((ageAlignment.reviewed || 0) > 0 && document.getElementById('alignmentRatioChart')) {
            new Chart(document.getElementById('alignmentRatioChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Appropriate', 'Above range', 'Below range'],
                    datasets: [{
                        data: [
                            ageAlignment.appropriate || 0,
                            ageAlignment.overage || 0,
                            ageAlignment.underage || 0,
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#0ea5e9'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 11, weight: '600' } },
                        },
                    },
                },
            });
        }

        if (byGrade.length > 0 && document.getElementById('averageAgeByGradeChart')) {
            new Chart(document.getElementById('averageAgeByGradeChart'), {
                type: 'bar',
                data: {
                    labels: byGrade.map(function (row) { return row.label.replace('Grade ', 'G'); }),
                    datasets: [
                        {
                            label: 'Average',
                            data: byGrade.map(function (row) { return row.average_age; }),
                            backgroundColor: '#296374',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Expected',
                            data: byGrade.map(function (row) { return row.expected_average_age; }),
                            backgroundColor: '#cbd5e1',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 10, weight: '600' } },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { precision: 0 },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        }
    });
</script>
@endsection
