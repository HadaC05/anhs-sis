@extends('users.teacher.layout')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Teacher Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600 md:text-base">Overview of learners in your advisory and teaching sections</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">School year</p>
            <p class="text-sm font-bold text-[#296374]">{{ $activeYear?->school_year ?? 'Not set' }}</p>
        </div>
        @if ($advisoryCount > 0)
            <a href="{{ route('teacher.advisory.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#296374]/20 bg-[#296374]/5 px-4 py-3 text-sm font-semibold text-[#296374] shadow-sm transition hover:bg-[#296374]/10">
                {{ $advisoryCount }} advisory {{ Str::plural('section', $advisoryCount) }}
            </a>
        @endif
        @if ($subjectsCount > 0)
            <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-semibold text-violet-700 shadow-sm transition hover:bg-violet-100">
                {{ $subjectsCount }} subject {{ Str::plural('assignment', $subjectsCount) }}
            </a>
        @endif
    </div>
</div>

@if (! $activeYear)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-800">
        No active school year is set. Your student statistics will appear once an admin activates the current school year.
    </div>
@endif

@if ($sectionsCount === 0)
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm font-medium text-gray-600">
        You have no advisory or teaching section assignments for the active school year yet.
    </div>
@endif

<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <a href="{{ route('teacher.sections.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#296374]/30 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Students</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalStudents) }}</p>
                <p class="mt-1 text-xs text-gray-500">In your sections</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#296374]/10 text-[#296374]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
        </div>
    </a>

    <a href="{{ route('teacher.sections.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Officially Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($enrolledCount) }}</p>
                <p class="mt-1 text-xs text-emerald-600">{{ $totalStudents > 0 ? round(($enrolledCount / $totalStudents) * 100) : 0 }}% of your students</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
            </div>
        </div>
    </a>

    <a href="{{ route('teacher.sections.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Temporarily Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($temporaryCount) }}</p>
                <p class="mt-1 text-xs text-blue-600">{{ $totalStudents > 0 ? round(($temporaryCount / $totalStudents) * 100) : 0 }}% of your students</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </a>

    <a href="{{ route('teacher.sections.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">My Sections</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($sectionsCount) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $subjectsCount }} subject {{ Str::plural('assignment', $subjectsCount) }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>
    </a>

    <a href="{{ route('teacher.advisory.index') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Learner Types</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($transfereeCount + $balikAralCount) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $transfereeCount }} transferees · {{ $balikAralCount }} balik aral</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0-3-3m3 3-3 3M16 17H4m0 0 3 3m-3-3 3-3"></path></svg>
            </div>
        </div>
    </a>
</div>

<div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Gender Ratio</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Distribution among your students
                    @if ($genderSectionId !== '')
                        · {{ $teacherSections->firstWhere('section_ID', (int) $genderSectionId)?->name }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('teacher.dashboard') }}" class="flex items-center gap-2">
                    @if ($enrollmentGradeLevel !== '')
                        <input type="hidden" name="enrollment_grade_level" value="{{ $enrollmentGradeLevel }}">
                    @endif
                    <select name="gender_section_id" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                        <option value="">All sections</option>
                        @foreach ($teacherSections as $section)
                            <option value="{{ $section->section_ID }}" {{ $genderSectionId == $section->section_ID ? 'selected' : '' }}>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </form>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">{{ number_format(collect($genderDistribution)->sum('total')) }} total</span>
            </div>
        </div>
        <div class="relative mx-auto h-72 max-w-sm">
            <canvas id="genderChart"></canvas>
            @if (collect($genderDistribution)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No student data yet</div>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Students by Section</h2>
                <p class="mt-1 text-sm text-gray-500">Enrollment count per assigned section</p>
            </div>
        </div>
        <div class="relative h-72">
            <canvas id="sectionChart"></canvas>
            @if (collect($sectionDistribution)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No student data yet</div>
            @endif
        </div>
    </div>
</div>

<div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @php
        $enrollmentFilterLabel = $enrollmentGradeLevel !== ''
            ? (collect($gradeLevels)->firstWhere('value', $enrollmentGradeLevel)['label'] ?? str_replace('_', ' ', $enrollmentGradeLevel))
            : 'All grade levels';
        $summaryTotal = $enrollmentGradeSummary['total'] ?? 0;
        $summaryEnrolledPct = $summaryTotal > 0 ? round(($enrollmentGradeSummary['enrolled'] / $summaryTotal) * 100) : 0;
        $summaryTemporaryPct = $summaryTotal > 0 ? round(($enrollmentGradeSummary['temporary'] / $summaryTotal) * 100) : 0;
    @endphp
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Students by Grade Level</h2>
            <p class="mt-1 text-sm text-gray-500">Official vs temporary enrollees{{ $enrollmentGradeLevel !== '' ? ' · '.$enrollmentFilterLabel : '' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('teacher.dashboard') }}" class="flex items-center gap-2">
                @if ($genderSectionId !== '')
                    <input type="hidden" name="gender_section_id" value="{{ $genderSectionId }}">
                @endif
                <select name="enrollment_grade_level" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" {{ $enrollmentGradeLevel === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
            </form>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">{{ number_format($summaryTotal) }} students</span>
        </div>
    </div>
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Officially enrolled</p>
            <p class="mt-1 text-xl font-bold text-[#296374]">{{ number_format($enrollmentGradeSummary['enrolled'] ?? 0) }} <span class="text-sm font-semibold text-gray-500">({{ $summaryEnrolledPct }}%)</span></p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Temporarily enrolled</p>
            <p class="mt-1 text-xl font-bold text-blue-600">{{ number_format($enrollmentGradeSummary['temporary'] ?? 0) }} <span class="text-sm font-semibold text-gray-500">({{ $summaryTemporaryPct }}%)</span></p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Scope</p>
            <p class="mt-1 text-sm font-bold text-gray-900">{{ $enrollmentFilterLabel }}</p>
        </div>
    </div>
    <div class="relative h-72 max-w-5xl">
        <canvas id="enrollmentByGradeChart"></canvas>
        @if ($summaryTotal === 0)
            <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No student data for this filter</div>
        @endif
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Recent Students</h3>
            <p class="mt-1 text-sm text-gray-500">Latest learners across your sections</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('teacher.sections.index') }}" class="text-sm font-semibold text-[#296374] transition hover:underline">My sections</a>
            @if ($advisoryCount > 0)
                <a href="{{ route('teacher.advisory.index') }}" class="text-sm font-semibold text-[#296374] transition hover:underline">My advisory</a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">Grade</th>
                    <th class="px-6 py-4">Section</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Learner type</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse ($recentStudents as $enrollment)
                    @php
                        $student = $enrollment->student ?? null;
                        $application = $student?->application;
                        $name = $application ? $application->last_name.', '.$application->first_name : ($student?->user?->name ?? 'N/A');
                        $initials = $application ? substr($application->first_name ?? '', 0, 1).substr($application->last_name ?? '', 0, 1) : '--';
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                        $status = $enrollment->enrollment_status ?? '';
                        $statusClasses = match ($status) {
                            'enrolled' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                            'temporarily_enrolled' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            default => 'bg-gray-100 text-gray-700 ring-gray-200',
                        };
                        $isAdvisory = $advisorySectionIds->contains($enrollment->section_ID);
                        $actionUrl = $isAdvisory
                            ? route('teacher.advisory.show', $enrollment->section)
                            : route('teacher.sections.index');
                        $actionLabel = $isAdvisory ? 'View advisory' : 'View sections';
                    @endphp
                    <tr class="transition hover:bg-gray-50/80">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full text-xs font-bold text-white" style="background-color: #296374;">{{ $initials }}</div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student?->lrn ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-700">{{ $gradeLabel }}</td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $enrollment->section?->name ?? 'Unassigned' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $enrollment->cluster?->name ?? 'N/A' }}
                                @if ($isAdvisory)
                                    · <span class="font-semibold text-[#296374]">Advisory</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses }}">
                                {{ $enrollment->enrollment_status_label ?: '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 capitalize text-gray-600">{{ $enrollment->learner_type_label ?: 'Regular' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ $actionUrl }}" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">{{ $actionLabel }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No students found in your sections yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var palette = ['#296374', '#14b8a6', '#3b82f6', '#f59e0b', '#8b5cf6', '#f97316', '#64748b'];

        var genderData = @json($genderDistribution);
        var sectionData = @json($sectionDistribution);
        var enrollmentByGrade = @json($enrollmentByGrade);

        function doughnutOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                            font: { size: 12, weight: '600' },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var total = context.dataset.data.reduce(function (sum, value) { return sum + value; }, 0);
                                var value = context.parsed;
                                var percent = total > 0 ? Math.round((value / total) * 100) : 0;
                                return context.label + ': ' + value + ' (' + percent + '%)';
                            },
                        },
                    },
                },
            };
        }

        if (genderData.length > 0 && document.getElementById('genderChart')) {
            new Chart(document.getElementById('genderChart'), {
                type: 'doughnut',
                data: {
                    labels: genderData.map(function (item) { return item.label; }),
                    datasets: [{
                        data: genderData.map(function (item) { return item.total; }),
                        backgroundColor: ['#296374', '#14b8a6', '#cbd5e1'],
                        borderWidth: 0,
                    }],
                },
                options: doughnutOptions(),
            });
        }

        if (sectionData.length > 0 && document.getElementById('sectionChart')) {
            new Chart(document.getElementById('sectionChart'), {
                type: 'bar',
                data: {
                    labels: sectionData.map(function (item) { return item.label; }),
                    datasets: [{
                        label: 'Students',
                        data: sectionData.map(function (item) { return item.total; }),
                        backgroundColor: sectionData.map(function (item, index) {
                            return item.is_advisory ? '#296374' : palette[index % palette.length];
                        }),
                        borderRadius: 8,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' },
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: '600' } },
                        },
                    },
                },
            });
        }

        if (enrollmentByGrade.length > 0 && document.getElementById('enrollmentByGradeChart')) {
            new Chart(document.getElementById('enrollmentByGradeChart'), {
                type: 'bar',
                data: {
                    labels: enrollmentByGrade.map(function (row) { return row.label.replace('Grade ', 'G'); }),
                    datasets: [
                        {
                            label: 'Officially enrolled',
                            data: enrollmentByGrade.map(function (row) { return row.enrolled; }),
                            backgroundColor: '#296374',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Temporarily enrolled',
                            data: enrollmentByGrade.map(function (row) { return row.temporary; }),
                            backgroundColor: '#3b82f6',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: enrollmentByGrade.length > 3 ? 'y' : 'x',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11, weight: '600' } },
                        },
                    },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' },
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' },
                        },
                    },
                },
            });
        }
    });
</script>
@endsection
