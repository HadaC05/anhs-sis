@extends('users.teacher.layout')

@section('title', 'Promotion')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'promotions'])

@if (session('status'))
    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $errors->first() }}</div>
@endif

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-bold text-gray-800">Promotion eligibility</h2>
            <p class="mt-1 text-sm text-gray-500">Select one or more learners, then evaluate them for promotion. Eligibility requires released grades in every subject and a minimum average of 75 per subject.</p>
        </div>
        <button form="promotionForm" type="submit" class="shrink-0 rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1f4e5c]">Evaluate selected</button>
    </div>

    <form id="promotionForm" method="POST" action="{{ route('teacher.advisory.promotions.evaluate', $section) }}">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-12 px-5 py-3 text-center"><input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-[#296374]"></th>
                        <th class="px-5 py-3">Learner</th>
                        <th class="px-5 py-3">LRN</th>
                        <th class="px-5 py-3">Current eligibility</th>
                        <th class="px-5 py-3">Next step</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $name = $student?->application ? trim($student->application->last_name.', '.$student->application->first_name.' '.$student->application->middle_name) : ($student?->name ?? 'Learner');
                            $status = $enrollment->promotion_status ?: 'pending';
                            $badge = match ($status) { 'eligible' => 'bg-emerald-100 text-emerald-800', 'retained' => 'bg-red-100 text-red-800', default => 'bg-amber-100 text-amber-800' };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4 text-center"><input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" data-promotion-checkbox class="h-4 w-4 rounded border-gray-300 text-[#296374]"></td>
                            <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $student?->lrn ?? '—' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">{{ $enrollment->promotion_status_label ?: 'Pending Evaluation' }}</span></td>
                            <td class="px-5 py-4 text-xs text-gray-500">{{ $status === 'eligible' ? 'Ready for Guidance confirmation.' : ($status === 'retained' ? 'Retained; review grades with the learner.' : 'Evaluate after all final grades are released.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No active learners are assigned to this advisory section.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
(() => {
    const selectAll = document.querySelector('[data-select-all]');
    const boxes = [...document.querySelectorAll('[data-promotion-checkbox]')];
    selectAll?.addEventListener('change', () => boxes.forEach((box) => { box.checked = selectAll.checked; }));
})();
</script>
@endsection
