@php
    $isRegistrar = ($dashboardContext ?? 'guidance') === 'registrar';
    $isAdmin = ($dashboardContext ?? 'guidance') === 'admin';
    $isPrincipal = ($dashboardContext ?? 'guidance') === 'principal';
    $dashboardRoute = match ($dashboardContext ?? 'guidance') {
        'registrar' => 'registrar.dashboard',
        'admin' => 'admin.dashboard',
        'principal' => 'principal.dashboard',
        default => 'guidance.dashboard',
    };
    $pageTitle = match ($dashboardContext ?? 'guidance') {
        'registrar' => 'Registrar Dashboard',
        'admin' => 'Admin Dashboard',
        'principal' => 'Principal Dashboard',
        default => 'Guidance Dashboard',
    };
    $pageSubtitle = $isAdmin
        ? 'System and enrollment overview for the active school year'
        : ($isPrincipal
            ? 'School-wide enrollment overview for the active school year'
            : 'Enrollment overview for the active school year');
    $totalEnrolleesUrl = $isRegistrar ? route('registrar.students') : ($isAdmin ? route('admin.users') : route('guidance.enrollments.index').'?status=all');
    $enrolledUrl = $isRegistrar ? route('registrar.students', ['status' => 'enrolled']) : ($isAdmin ? route('admin.users') : route('guidance.enrollments.index').'?status=enrolled');
    $temporaryUrl = $isRegistrar ? route('registrar.students', ['status' => 'temporarily_enrolled']) : ($isAdmin ? route('admin.users') : route('guidance.enrollments.index').'?status=temporarily_enrolled');
    $transfereeUrl = $isRegistrar ? route('registrar.students') : ($isAdmin ? route('admin.users') : route('guidance.enrollments.index').'?status=all&learner_type=transferee');
    $balikAralUrl = $isRegistrar ? route('registrar.students') : ($isAdmin ? route('admin.users') : route('guidance.enrollments.index').'?status=all&learner_type=balik_aral');
    $useStatLinks = ! $isAdmin && ! $isPrincipal;
    $showEnrollmentNotifications = ! $isRegistrar && ! $isAdmin && ! $isPrincipal;
    $showRecentApplications = ! $isRegistrar && ! $isAdmin && ! $isPrincipal;
    $showAgeAlignmentReportLink = ! $isRegistrar && ! $isAdmin;
    $ageAlignmentReportRoute = $isPrincipal ? 'principal.reports.age-for-grade' : 'guidance.reports.age-for-grade';
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-600 md:text-base">{{ $pageSubtitle }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">School year</p>
            <p class="text-sm font-bold text-[#296374]">{{ $activeYear?->school_year ?? 'Not set' }}</p>
        </div>
        @if ($isAdmin && isset($totalUsers))
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">System users</p>
                <p class="text-sm font-bold text-[#296374]">{{ number_format($totalUsers) }}</p>
            </div>
        @endif
        @if ($showEnrollmentNotifications && $pendingCount > 0)
            <a href="{{ route('guidance.enrollments.index') }}?status=pending" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-sm transition hover:bg-amber-100">
                <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                {{ $pendingCount }} pending review
            </a>
        @endif
        @if ($showEnrollmentNotifications && $placementTestMarkedCount > 0)
            <a href="{{ route('guidance.reports.age-for-grade') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#296374]/20 bg-[#296374]/5 px-4 py-3 text-sm font-semibold text-[#296374] shadow-sm transition hover:bg-[#296374]/10">
                {{ $placementTestMarkedCount }} marked for placement test
            </a>
        @endif
    </div>
</div>

@if (! $activeYear)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-800">
        No active school year is set. Enrollment statistics will appear once an admin activates the current school year.
    </div>
@endif

@php $hideEnrollmentStats = $hideEnrollmentStats ?? false; @endphp
@if (! $hideEnrollmentStats)
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @if ($useStatLinks)
    <a href="{{ $totalEnrolleesUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#296374]/30 hover:shadow-md">
    @else
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @endif
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Enrollees</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalEnrollees) }}</p>
                <p class="mt-1 text-xs text-gray-500">Official + temporary</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#296374]/10 text-[#296374]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path></svg>
            </div>
        </div>
    @if ($useStatLinks)</a>@else</div>@endif

    @if ($useStatLinks)
    <a href="{{ $enrolledUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
    @else
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @endif
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Officially Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($enrolledCount) }}</p>
                <p class="mt-1 text-xs text-emerald-600">{{ $totalEnrollees > 0 ? round(($enrolledCount / $totalEnrollees) * 100) : 0 }}% of enrollees</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
            </div>
        </div>
    @if ($useStatLinks)</a>@else</div>@endif

    @if ($useStatLinks)
    <a href="{{ $temporaryUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
    @else
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @endif
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Temporarily Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($temporaryCount) }}</p>
                <p class="mt-1 text-xs text-blue-600">{{ $totalEnrollees > 0 ? round(($temporaryCount / $totalEnrollees) * 100) : 0 }}% of enrollees</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
            </div>
        </div>
    @if ($useStatLinks)</a>@else</div>@endif

    @if ($useStatLinks)
    <a href="{{ $transfereeUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
    @else
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @endif
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Transferees</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($transfereeCount) }}</p>
                <p class="mt-1 text-xs text-gray-500">Among active enrollees</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0-3-3m3 3-3 3M16 17H4m0 0 3 3m-3-3 3-3"></path></svg>
            </div>
        </div>
    @if ($useStatLinks)</a>@else</div>@endif

    @if ($useStatLinks)
    <a href="{{ $balikAralUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-md">
    @else
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    @endif
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Balik Aral</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($balikAralCount) }}</p>
                <p class="mt-1 text-xs text-gray-500">Returning learners</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"></path></svg>
            </div>
        </div>
    @if ($useStatLinks)</a>@else</div>@endif
</div>
@endif

<div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Gender Ratio</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Distribution among active enrollees
                    @if ($genderGradeLevel !== '')
                        · {{ collect($gradeLevels)->firstWhere('value', $genderGradeLevel)['label'] ?? str_replace('_', ' ', $genderGradeLevel) }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route($dashboardRoute) }}" class="flex items-center gap-2">
                    @if ($ageGradeLevel !== '')
                        <input type="hidden" name="age_grade_level" value="{{ $ageGradeLevel }}">
                    @endif
                    @if ($enrollmentGradeLevel !== '')
                        <input type="hidden" name="enrollment_grade_level" value="{{ $enrollmentGradeLevel }}">
                    @endif
                    @if ($isPrincipal)
                        @if (! empty($proficiencySelectedAcademicYear))
                            <input type="hidden" name="proficiency_academic_year_id" value="{{ $proficiencySelectedAcademicYear }}">
                        @endif
                        @if (! empty($proficiencyComparisonAcademicYear))
                            <input type="hidden" name="proficiency_compare_year_id" value="{{ $proficiencyComparisonAcademicYear }}">
                        @endif
                        @if (($proficiencyGradeLevel ?? '') !== '')
                            <input type="hidden" name="proficiency_grade_level" value="{{ $proficiencyGradeLevel }}">
                        @endif
                    @endif
                    <select name="gender_grade_level" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                        <option value="">All grades</option>
                        @foreach ($gradeLevels as $level)
                            <option value="{{ $level['value'] }}" {{ $genderGradeLevel === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </form>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">{{ number_format(collect($genderDistribution)->sum('total')) }} total</span>
            </div>
        </div>
        <div class="relative mx-auto h-72 max-w-sm">
            <canvas id="genderChart"></canvas>
            @if (collect($genderDistribution)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No enrollee data yet</div>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Cluster Distribution</h2>
                <p class="mt-1 text-sm text-gray-500">SHS clusters and junior high enrollees</p>
            </div>
        </div>
        <div class="relative h-72">
            <canvas id="clusterChart"></canvas>
            @if (collect($clusterDistribution)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No enrollee data yet</div>
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
            <h2 class="text-lg font-bold text-gray-900">Enrollment by Grade Level</h2>
            <p class="mt-1 text-sm text-gray-500">Official vs temporary enrollees{{ $enrollmentGradeLevel !== '' ? ' · '.$enrollmentFilterLabel : '' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route($dashboardRoute) }}" class="flex items-center gap-2">
                @if ($genderGradeLevel !== '')
                    <input type="hidden" name="gender_grade_level" value="{{ $genderGradeLevel }}">
                @endif
                @if ($ageGradeLevel !== '')
                    <input type="hidden" name="age_grade_level" value="{{ $ageGradeLevel }}">
                @endif
                @if ($isPrincipal)
                    @if (! empty($proficiencySelectedAcademicYear))
                        <input type="hidden" name="proficiency_academic_year_id" value="{{ $proficiencySelectedAcademicYear }}">
                    @endif
                    @if (! empty($proficiencyComparisonAcademicYear))
                        <input type="hidden" name="proficiency_compare_year_id" value="{{ $proficiencyComparisonAcademicYear }}">
                    @endif
                    @if (($proficiencyGradeLevel ?? '') !== '')
                        <input type="hidden" name="proficiency_grade_level" value="{{ $proficiencyGradeLevel }}">
                    @endif
                @endif
                <select name="enrollment_grade_level" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" {{ $enrollmentGradeLevel === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
            </form>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">{{ number_format($summaryTotal) }} enrollees</span>
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
            <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No enrollee data for this filter</div>
        @endif
    </div>
</div>

@if ($isPrincipal)
@php
    $proficiencyTotal = collect($proficiencyDistribution ?? [])->sum('total');
    $proficiencyComparisonTotal = collect($proficiencyComparisonDistribution ?? [])->sum('total');
    $proficiencyFilterLabel = ($proficiencyGradeLevel ?? '') !== ''
        ? (collect($gradeLevels)->firstWhere('value', $proficiencyGradeLevel)['label'] ?? str_replace('_', ' ', $proficiencyGradeLevel))
        : 'All grade levels';
    $proficiencyPalette = [
        'Advanced' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200'],
        'Proficient' => ['bg' => 'bg-[#296374]/10', 'text' => 'text-[#296374]', 'ring' => 'ring-[#296374]/20'],
        'Approaching Proficiency' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-200'],
        'Developing' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200'],
        'Beginning' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-200'],
    ];
@endphp
<div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Student Proficiency Distribution</h2>
            <p class="mt-1 text-sm text-gray-500">
                General average levels for {{ $proficiencyFilterLabel }} in {{ $proficiencySelectedAcademicYearLabel ?? 'the selected school year' }}
                @if (! empty($proficiencyComparisonAcademicYearLabel))
                    compared with {{ $proficiencyComparisonAcademicYearLabel }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route($dashboardRoute) }}" class="flex flex-wrap items-center gap-2">
                @if ($genderGradeLevel !== '')
                    <input type="hidden" name="gender_grade_level" value="{{ $genderGradeLevel }}">
                @endif
                @if ($enrollmentGradeLevel !== '')
                    <input type="hidden" name="enrollment_grade_level" value="{{ $enrollmentGradeLevel }}">
                @endif
                @if ($ageGradeLevel !== '')
                    <input type="hidden" name="age_grade_level" value="{{ $ageGradeLevel }}">
                @endif
                <select name="proficiency_academic_year_id" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach (($academicYears ?? collect()) as $year)
                        <option value="{{ $year->SY_ID }}" {{ (int) ($proficiencySelectedAcademicYear ?? 0) === (int) $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                    @endforeach
                </select>
                <select name="proficiency_grade_level" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" {{ ($proficiencyGradeLevel ?? '') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
                <select name="proficiency_compare_year_id" onchange="this.form.submit()" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">No comparison</option>
                    @foreach (($academicYears ?? collect()) as $year)
                        @if ((int) ($proficiencySelectedAcademicYear ?? 0) !== (int) $year->SY_ID)
                            <option value="{{ $year->SY_ID }}" {{ (int) ($proficiencyComparisonAcademicYear ?? 0) === (int) $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                        @endif
                    @endforeach
                </select>
            </form>
            <a href="{{ route('principal.proficiency-levels') }}" class="inline-flex h-9 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Open report</a>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Reviewed students</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($proficiencyTotal) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Comparison</p>
            <p class="mt-1 text-xl font-bold text-[#296374]">{{ ! empty($proficiencyComparisonAcademicYearLabel) ? number_format($proficiencyComparisonTotal).' students' : 'None' }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Scope</p>
            <p class="mt-1 text-sm font-bold text-gray-900">{{ $proficiencyFilterLabel }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(280px,420px)_1fr]">
        <div class="relative mx-auto h-80 w-full max-w-md">
            <canvas id="principalProficiencyChart"></canvas>
            @if ($proficiencyTotal === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No proficiency data yet</div>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach(($proficiencyDistribution ?? []) as $item)
                @php
                    $colors = $proficiencyPalette[$item['label']] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'ring' => 'ring-gray-200'];
                    $percent = $proficiencyTotal > 0 ? round(($item['total'] / $proficiencyTotal) * 100) : 0;
                    $comparisonTotal = collect($proficiencyComparisonDistribution ?? [])->firstWhere('label', $item['label'])['total'] ?? 0;
                    $difference = $item['total'] - $comparisonTotal;
                @endphp
                <div class="rounded-lg px-4 py-3 ring-1 {{ $colors['bg'] }} {{ $colors['text'] }} {{ $colors['ring'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold">{{ $item['label'] }}</p>
                            <p class="text-[11px] font-semibold uppercase tracking-wider opacity-70">{{ $item['range'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-extrabold">{{ number_format($item['total']) }}</p>
                            <p class="text-[11px] font-bold opacity-70">{{ $percent }}%</p>
                        </div>
                    </div>
                    @if (! empty($proficiencyComparisonAcademicYearLabel))
                        <p class="mt-3 text-xs font-semibold opacity-80">
                            {{ number_format($comparisonTotal) }} last year
                            <span class="{{ $difference >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                ({{ $difference >= 0 ? '+' : '' }}{{ number_format($difference) }})
                            </span>
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@php
    $suggestedPlacementCount = $ageAlignment['overage'] ?? 0;
@endphp

<div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Age Alignment</h2>
            <p class="mt-1 text-sm text-gray-500">Quick snapshot of student ages vs expected grade-level ranges</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route($dashboardRoute) }}" class="flex items-center gap-2">
                @if ($genderGradeLevel !== '')
                    <input type="hidden" name="gender_grade_level" value="{{ $genderGradeLevel }}">
                @endif
                @if ($enrollmentGradeLevel !== '')
                    <input type="hidden" name="enrollment_grade_level" value="{{ $enrollmentGradeLevel }}">
                @endif
                @if ($isPrincipal)
                    @if (! empty($proficiencySelectedAcademicYear))
                        <input type="hidden" name="proficiency_academic_year_id" value="{{ $proficiencySelectedAcademicYear }}">
                    @endif
                    @if (! empty($proficiencyComparisonAcademicYear))
                        <input type="hidden" name="proficiency_compare_year_id" value="{{ $proficiencyComparisonAcademicYear }}">
                    @endif
                    @if (($proficiencyGradeLevel ?? '') !== '')
                        <input type="hidden" name="proficiency_grade_level" value="{{ $proficiencyGradeLevel }}">
                    @endif
                @endif
                <select name="age_grade_level" onchange="this.form.submit()" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" {{ $ageGradeLevel === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
            </form>
            @if ($showAgeAlignmentReportLink)
                <a href="{{ route($ageAlignmentReportRoute, $ageGradeLevel !== '' ? ['grade_level' => $ageGradeLevel] : []) }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Open full report</a>
            @endif
        </div>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Reviewed</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($ageAlignment['reviewed'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Average age</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ isset($ageAlignment['average_age']) ? number_format($ageAlignment['average_age'], 1) : '—' }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Needs review</p>
            <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format($suggestedPlacementCount) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Marked for test</p>
            <p class="mt-1 text-xl font-bold text-[#296374]">{{ number_format($placementTestMarkedCount) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Appropriate</p>
            <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($ageAlignment['appropriate'] ?? 0) }}</p>
        </div>
    </div>
</div>

@if ($showRecentApplications)
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Recent Applications</h3>
            <p class="mt-1 text-sm text-gray-500">Latest enrollment submissions</p>
        </div>
        <a href="{{ route('guidance.enrollments.index') }}" class="text-sm font-semibold text-[#296374] transition hover:underline">View all enrollments</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">Grade</th>
                    <th class="px-6 py-4">Section</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Submitted</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentEnrollments as $enrollment)
                    @php
                        $student = $enrollment->student ?? null;
                        $application = $student?->application;
                        $name = $application ? $application->last_name . ', ' . $application->first_name : ($student?->user?->name ?? 'N/A');
                        $initials = $application ? substr($application->first_name ?? '', 0, 1) . substr($application->last_name ?? '', 0, 1) : '--';
                        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                        $status = $enrollment->enrollment_status ?? '';
                        $statusClasses = match ($status) {
                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'enrolled' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                            'temporarily_enrolled' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            default => 'bg-gray-100 text-gray-700 ring-gray-200',
                        };
                        $recentPlacementRecommended = $enrollment->placementAssessmentRecommendation();
                    @endphp
                    <tr class="transition hover:bg-gray-50/80 {{ $enrollment->hasPlacementStatusMark() ? 'bg-[#296374]/[0.03]' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full text-xs font-bold text-white" style="background-color: #296374;">{{ $initials }}</div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student?->lrn ?? 'N/A' }}</p>
                                    @if ($enrollment->hasPlacementStatusMark())
                                        <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ \App\Models\PlacementStatus::badgeClasses($enrollment->placement_status) }}">{{ $enrollment->placement_status_label }}</span>
                                    @elseif ($recentPlacementRecommended)
                                        <span class="mt-1 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">Age review</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-700">{{ $gradeLabel }}</td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $enrollment->section?->name ?? 'Unassigned' }}</p>
                            <p class="text-xs text-gray-500">{{ $enrollment->cluster?->name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses }}">
                                {{ $enrollment->enrollment_status_label ?: '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $enrollment->created_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('guidance.enrollments.show', $enrollment) }}" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No recent applications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var palette = ['#296374', '#14b8a6', '#3b82f6', '#f59e0b', '#8b5cf6', '#f97316', '#64748b'];
        var genderData = @json($genderDistribution);
        var clusterData = @json($clusterDistribution);
        var enrollmentByGrade = @json($enrollmentByGrade);
        var proficiencyData = @json($proficiencyDistribution ?? []);

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

        if (clusterData.length > 0 && document.getElementById('clusterChart')) {
            new Chart(document.getElementById('clusterChart'), {
                type: 'bar',
                data: {
                    labels: clusterData.map(function (item) { return item.label; }),
                    datasets: [{
                        label: 'Enrollees',
                        data: clusterData.map(function (item) { return item.total; }),
                        backgroundColor: palette,
                        borderRadius: 8,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' },
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: '600' },
                            },
                        },
                    },
                },
            });
        }

        if (proficiencyData.length > 0 && document.getElementById('principalProficiencyChart')) {
            new Chart(document.getElementById('principalProficiencyChart'), {
                type: 'pie',
                data: {
                    labels: proficiencyData.map(function (item) { return item.label; }),
                    datasets: [{
                        label: 'Students',
                        data: proficiencyData.map(function (item) { return item.total; }),
                        backgroundColor: ['#10b981', '#296374', '#3b82f6', '#f59e0b', '#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                                    return context.label + ': ' + value + ' of ' + total + ' students (' + percent + '%)';
                                },
                            },
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
                        tooltip: {
                            callbacks: {
                                footer: function (items) {
                                    if (!items.length) {
                                        return '';
                                    }
                                    var index = items[0].dataIndex;
                                    var row = enrollmentByGrade[index];
                                    var total = row.total || 0;
                                    if (total === 0) {
                                        return '';
                                    }
                                    var officialPct = Math.round((row.enrolled / total) * 100);
                                    var tempPct = Math.round((row.temporary / total) * 100);
                                    return 'Ratio: ' + officialPct + '% official · ' + tempPct + '% temporary';
                                },
                            },
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
