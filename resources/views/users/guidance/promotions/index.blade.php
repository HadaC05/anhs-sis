@extends($isPrincipal ? 'users.principal.layout' : 'users.guidance.layout')

@php
    $promotionsRoute = $isPrincipal ? 'principal.promotions.index' : 'guidance.promotions.index';
@endphp

@section('title', 'Promotions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">{{ $isPrincipal ? 'Promotions' : 'Promotion Confirmation' }}</h1>
</div>

<x-password-reset-toasts test-prefix="promotion" />

<form method="GET" action="{{ route($promotionsRoute) }}" class="mb-5">
    <div class="guidance-filters flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
        <div class="min-w-0 flex-1 sm:min-w-[200px]">
            <label for="promotion-search" class="mb-1 block text-xs font-semibold text-gray-600">Search learner</label>
            <input id="promotion-search" type="search" name="search" value="{{ request('search') }}" placeholder="Name or LRN" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
        </div>
        <div>
            <label for="promotion-grade" class="mb-1 block text-xs font-semibold text-gray-600">Grade level</label>
            <select id="promotion-grade" name="grade_level" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                <option value="">All grades</option>
                @foreach ($gradeLevels as $grade)
                    <option value="{{ $grade->grade_ID }}" @selected(request('grade_level') == $grade->grade_ID)>{{ $grade->grade_label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="promotion-eligibility" class="mb-1 block text-xs font-semibold text-gray-600">Eligibility</label>
            <select id="promotion-eligibility" name="eligibility" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                @foreach (['all' => 'All eligibility', 'eligible' => 'Eligible for Promotion', 'pending' => 'Pending Evaluation', 'retained' => 'Not Eligible', 'conditionally_promoted' => 'Conditionally Promoted', 'promoted' => 'Promoted'] as $value => $label)
                    <option value="{{ $value }}" @selected($eligibility === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="promotion-year" class="mb-1 block text-xs font-semibold text-gray-600">School year</label>
            <select id="promotion-year" name="academic_year_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                <option value="">All years</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->SY_ID }}" @selected(request('academic_year_id') == $year->SY_ID)>{{ $year->school_year }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-[#296374] px-4 text-sm font-bold text-white hover:bg-[#1f4e5c]">Apply</button>
        <a href="{{ route($promotionsRoute) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
    </div>
</form>

<form method="POST" action="{{ route($isPrincipal ? 'principal.promotions.sf5' : 'guidance.promotions.sf5') }}" class="mb-5">
    @csrf
    @foreach (['search', 'grade_level', 'academic_year_id'] as $filter)
        <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
    @endforeach
    <input type="hidden" name="eligibility" value="{{ $eligibility }}">
    <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white hover:bg-[#1f4e5c]">Generate SF 5 (filtered results)</button>
</form>

@if (! $isPrincipal)
<form id="bulkPromotionForm" method="POST" action="{{ route('guidance.promotions.bulk') }}"
    data-confirm-action="Promote" data-confirm-title="Promote selected learners?" data-confirm-message="The selected eligible learners will be promoted to the next active school year.">
    @csrf
</form>
@endif

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
        <h2 class="font-bold text-gray-800">Promotion</h2>
        @include('partials.promotion-criteria')
        </div>
        @if (! $isPrincipal)
            <button id="promote-selected" form="bulkPromotionForm" type="submit" hidden disabled class="shrink-0 rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1f4e5c]">Promote selected (<span id="promotion-selected-count" aria-live="polite">0</span>)</button>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                <tr>
                    @if (! $isPrincipal)
                        <th scope="col" class="w-12 px-5 py-3 text-center"><input type="checkbox" id="promotion-select-all" aria-label="Select all eligible learners on this page" class="h-4 w-4 rounded border-gray-300 text-[#296374]"></th>
                    @endif
                    <th scope="col" class="px-5 py-3">Learner</th>
                    <th scope="col" class="px-5 py-3">LRN</th>
                    <th scope="col" class="px-5 py-3">Completed enrollment</th>
                    <th scope="col" class="px-5 py-3">Eligibility</th>
                    <th scope="col" class="px-5 py-3">{{ $isPrincipal ? 'Remarks' : 'Action' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($enrollments as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $name = $student?->application
                            ? trim($student->application->last_name.', '.$student->application->first_name.' '.$student->application->middle_name)
                            : ($student?->name ?? 'Learner');
                        $status = $enrollment->promotion_status;
                        $badge = match ($status) {
                            'eligible' => 'bg-emerald-100 text-emerald-800',
                            'promoted' => 'bg-emerald-100 text-emerald-800',
                            'retained' => 'bg-red-100 text-red-800',
                            default => 'bg-amber-100 text-amber-800',
                        };
                        $label = match ($status) {
                            'eligible' => 'Eligible for Promotion',
                            'conditionally_promoted' => 'Conditionally Promoted',
                            'promoted' => 'Promoted',
                            'retained' => 'Not Eligible',
                            default => 'Pending Evaluation',
                        };
                        $isCompleter = $enrollment->getRelation('gradeLevel')?->grade_label === 'Grade 12';
                        $canPromote = $status === 'eligible' && ! $isCompleter;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        @if (! $isPrincipal)
                        <td class="px-5 py-4 text-center">
                            @if ($canPromote)
                                <input form="bulkPromotionForm" type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" data-promotion-checkbox aria-label="Select {{ $name }}" class="h-4 w-4 rounded border-gray-300 text-[#296374]">
                            @endif
                        </td>
                        @endif
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $student?->lrn ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">
                            <p>{{ $enrollment->getRelation('gradeLevel')?->grade_label ?? '—' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $enrollment->academicYear?->school_year ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">{{ $label }}</span>
                        </td>
                        <td class="px-5 py-4">
                            @if ($canPromote && ! $isPrincipal)
                            <form method="POST" action="{{ route('guidance.promotions.confirm', $enrollment) }}" class="flex flex-wrap items-center gap-2"
                                data-confirm-action="Promote" data-confirm-title="Promote learner?" data-confirm-message="Promote {{ $name }} to the next grade level in the active school year?">
                                @csrf
                                <button type="submit" class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Promote</button>
                            </form>
                            @else
                                <span class="text-xs text-gray-500">{{ match (true) { $status === 'promoted' => 'Already promoted.', $status === 'conditionally_promoted' => 'Conditional promotion does not permit advancement to the next grade.', $isCompleter && $status === 'eligible' => 'Grade 12 completed', $canPromote => 'Eligible for promotion.', default => 'Promotion requirements are not yet complete.' } }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isPrincipal ? 5 : 6 }}" class="px-5 py-12 text-center text-gray-500">{{ $eligibility === 'eligible' && ! request()->filled('search') && ! request()->filled('grade_level') && ! request()->filled('academic_year_id') ? 'No learners are currently eligible for promotion.' : 'No learners match the selected filters.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 bg-white px-5 py-4">
        {{ $enrollments->links() }}
    </div>
</div>

@if (! $isPrincipal)
@include('users.partials.archive-confirmation')

<script>
(() => {
    const selectAll = document.getElementById('promotion-select-all');
    const boxes = [...document.querySelectorAll('[data-promotion-checkbox]')];
    const button = document.getElementById('promote-selected');
    const count = document.getElementById('promotion-selected-count');
    const update = () => {
        const selected = boxes.filter(box => box.checked).length;
        count.textContent = selected;
        button.hidden = selected === 0;
        button.disabled = selected === 0;
        selectAll.disabled = boxes.length === 0;
        selectAll.checked = boxes.length > 0 && selected === boxes.length;
        selectAll.indeterminate = selected > 0 && selected < boxes.length;
    };
    selectAll.addEventListener('change', () => {
        boxes.forEach(box => { box.checked = selectAll.checked; });
        update();
    });
    boxes.forEach(box => box.addEventListener('change', update));
    window.addEventListener('pageshow', update);
    update();
})();
</script>
@endif
@endsection
