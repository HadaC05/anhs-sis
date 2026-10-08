@extends('users.teacher.layout')

@section('title', 'Promotion')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'promotions'])
<x-password-reset-toasts test-prefix="promotion" />

<nav class="mb-5 flex gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm" aria-label="Promotion records">
    <a href="{{ route('teacher.advisory.promotions.index', [$section, 'tab' => 'promotions']) }}" class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-bold transition {{ $tab === 'promotions' ? 'bg-[#296374] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">Promotions</a>
    <a href="{{ route('teacher.advisory.promotions.index', [$section, 'tab' => 'remediation']) }}" class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-bold transition {{ $tab === 'remediation' ? 'bg-[#296374] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">Remediation</a>
</nav>

<form method="GET" action="{{ route('teacher.advisory.promotions.index', $section) }}" class="mb-5 grid grid-cols-1 gap-4 rounded-xl border border-[#296374]/25 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label for="promotion-search" class="block lg:col-span-2"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Learner / LRN</span>
        <input id="promotion-search" type="search" name="search" value="{{ request('search') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20" placeholder="Search by name or LRN">
    </label>
    <label for="promotion-status" class="block"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Status</span>
        <select id="promotion-status" name="eligibility" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20">
            <option value="all">All statuses</option>
            @foreach (\App\Models\PromotionStatus::definitions() as $status)
                <option value="{{ $status['slug'] }}" @selected(request('eligibility') === $status['slug'])>{{ $status['name'] }}</option>
            @endforeach
        </select>
    </label>
    <div class="flex items-center gap-2">
        <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-[#296374] px-4 text-sm font-semibold text-white transition hover:bg-[#1f4e5c]">Apply filters</button>
        <a href="{{ route('teacher.advisory.promotions.index', [$section, 'tab' => $tab]) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
    </div>
</form>

@if ($tab === 'promotions')
<form method="POST" action="{{ route('teacher.advisory.promotions.sf5', $section) }}" class="mb-5">
    @csrf
    <input type="hidden" name="search" value="{{ request('search') }}">
    <input type="hidden" name="eligibility" value="{{ request('eligibility', 'all') }}">
    <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1f4e5c]">Generate SF 5 (.xlsx)</button>
</form>

<form id="bulkPromotionForm" method="POST" action="{{ route('teacher.advisory.promotions.bulk', $section) }}"
    data-confirm-action="Process" data-confirm-title="Process selected learners?" data-confirm-message="Eligible Grade 7–9 learners will be promoted. Eligible Grade 10 learners will be marked as Junior High School completers without a Grade 11 enrollment.">
    @csrf
</form>
@endif

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-bold text-gray-800">{{ $tab === 'remediation' ? 'Students Under Remediation' : 'Promotion' }}</h2>
            @if ($tab === 'promotions')
                @include('partials.promotion-criteria')
            @else
                <p class="mt-1 text-xs text-gray-500">Learners in this advisory section whose remedial class record has been started.</p>
            @endif
        </div>
        @if ($tab === 'promotions')
        <button form="bulkPromotionForm" type="submit" class="shrink-0 rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1f4e5c]">Bulk promote selected</button>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                <tr>
                    @if ($tab === 'promotions')
                        <th class="w-12 px-5 py-3 text-center"><input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-[#296374]"></th>
                    @endif
                    <th class="px-5 py-3">Learner</th>
                    <th class="px-5 py-3">LRN</th>
                    <th class="px-5 py-3">General Average</th>
                    <th class="px-5 py-3">Eligibility</th>
                    <th class="px-5 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($enrollments as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $name = $student?->application ? trim($student->application->last_name.', '.$student->application->first_name.' '.$student->application->middle_name) : ($student?->name ?? 'Learner');
                        $evaluation = $evaluations[$enrollment->enrollment_ID];
                        $status = $evaluation['status'];
                        $subjectAverages = collect($evaluation['subject_averages'])->map(fn ($average) => round($average));
                        $generalAverage = $subjectAverages->isNotEmpty() ? round($subjectAverages->avg()) : null;
                        $badge = match ($status) { 'eligible', 'promoted', 'completed_junior_high' => 'bg-emerald-100 text-emerald-800', 'retained' => 'bg-red-100 text-red-800', default => 'bg-amber-100 text-amber-800' };
                        $label = match ($status) { 'eligible' => 'Eligible for Promotion', 'promoted' => 'Promoted', 'completed_junior_high' => 'Completed Junior High School', 'conditionally_promoted' => 'Conditionally Promoted', 'retained' => 'Retained', default => 'Pending Requirements' };
                        $isGradeTen = $section->getRelation('gradeLevel')?->grade_label === 'Grade 10';
                        $isGradeTwelve = $section->getRelation('gradeLevel')?->grade_label === 'Grade 12';
                        $alreadyPromoted = in_array((int) $enrollment->student_ID, $alreadyPromotedStudentIds, true);
                        $canPromote = $status === 'eligible' && ! $isGradeTwelve && ! $alreadyPromoted && ($isGradeTen || $nextAcademicYear);
                        $remediationCase = $enrollment->remediationCase;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        @if ($tab === 'promotions')
                        <td class="px-5 py-4 text-center">
                            @if ($canPromote)
                                <input form="bulkPromotionForm" type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" data-promotion-checkbox class="h-4 w-4 rounded border-gray-300 text-[#296374]">
                            @endif
                        </td>
                        @endif
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $student?->lrn ?? '—' }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ $generalAverage === null ? '—' : number_format($generalAverage, 0) }}</td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">{{ $label }}</span></td>
                        <td class="px-5 py-4">
                            @if ($tab === 'remediation' && $remediationCase)
                                <a href="{{ route('teacher.advisory.remediations.show', [$section, $remediationCase]) }}" class="inline-flex rounded-lg border border-[#296374]/30 bg-white px-3 py-1.5 text-xs font-bold text-[#296374] hover:bg-[#296374]/5">Review Remediation</a>
                            @elseif ($canPromote)
                                <form method="POST" action="{{ route('teacher.advisory.promotions.promote', [$section, $enrollment]) }}"
                                    data-confirm-action="{{ $isGradeTen ? 'Complete' : 'Promote' }}" data-confirm-title="{{ $isGradeTen ? 'Complete Junior High School?' : 'Promote learner?' }}" data-confirm-message="{{ $isGradeTen ? 'Mark '.$name.' as having completed Junior High School? No Grade 11 enrollment will be created.' : 'Promote '.$name.' to the next grade level?' }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">{{ $isGradeTen ? 'Mark Completed' : 'Promote' }}</button>
                                </form>
                            @elseif ($isGradeTwelve && $status === 'eligible')
                                <span class="text-xs font-medium text-gray-500">Grade 12 completed</span>
                            @elseif ($status === 'completed_junior_high')
                                <span class="text-xs font-medium text-emerald-700">Junior High School completed</span>
                            @elseif ($alreadyPromoted || $status === 'promoted')
                                <span class="text-xs font-medium text-emerald-700">Already promoted{{ $nextAcademicYear ? ' to '.$nextAcademicYear->school_year : '' }}</span>
                            @elseif ($status === 'eligible' && ! $nextAcademicYear)
                                <span class="text-xs text-gray-500">Configure the next school year to promote.</span>
                            @elseif ($status === 'conditionally_promoted' && $remediationCase)
                                <a href="{{ route('teacher.advisory.remediations.show', [$section, $remediationCase]) }}" class="inline-flex rounded-lg border border-[#296374]/30 bg-white px-3 py-1.5 text-xs font-bold text-[#296374] hover:bg-[#296374]/5">Review Remediation</a>
                            @elseif ($status === 'conditionally_promoted')
                                <form method="POST" action="{{ route('teacher.advisory.remediations.start', [$section, $enrollment]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Start Remediation</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">{{ $evaluation['reason'] ?: 'Promotion requirements are not yet complete.' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $tab === 'promotions' ? 6 : 5 }}" class="px-5 py-12 text-center text-gray-500">{{ $tab === 'remediation' ? 'No learners with remediation records match the selected filters in this advisory section.' : 'No active learners match the selected filters in this advisory section.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('users.partials.archive-confirmation')

<script>
(() => {
    const selectAll = document.querySelector('[data-select-all]');
    const boxes = [...document.querySelectorAll('[data-promotion-checkbox]')];
    selectAll?.addEventListener('change', () => boxes.forEach((box) => { box.checked = selectAll.checked; }));
})();
</script>
@endsection
