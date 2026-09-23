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

<form id="bulkPromotionForm" method="POST" action="{{ route('teacher.advisory.promotions.bulk', $section) }}">
    @csrf
</form>

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-bold text-gray-800">Promotion</h2>
            <p class="mt-1 text-sm text-gray-500">Eligibility is determined automatically. Learners can be promoted only after complete released grades, no failing grades, and closure of all Junior High terms or their Senior High semester.</p>
        </div>
        <button form="bulkPromotionForm" type="submit" class="shrink-0 rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1f4e5c]">Bulk promote selected</button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="w-12 px-5 py-3 text-center"><input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-[#296374]"></th>
                    <th class="px-5 py-3">Learner</th>
                    <th class="px-5 py-3">LRN</th>
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
                        $badge = match ($status) { 'eligible' => 'bg-emerald-100 text-emerald-800', 'retained' => 'bg-red-100 text-red-800', default => 'bg-amber-100 text-amber-800' };
                        $label = match ($status) { 'eligible' => 'Eligible for Promotion', 'retained' => 'Not Eligible', default => 'Pending Requirements' };
                        $isGradeTwelve = $section->gradeLevel?->grade_label === 'Grade 12';
                        $alreadyPromoted = in_array((int) $enrollment->student_ID, $alreadyPromotedStudentIds, true);
                        $canPromote = $status === 'eligible' && ! $isGradeTwelve && ! $alreadyPromoted && $nextAcademicYear;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 text-center">
                            @if ($canPromote)
                                <input form="bulkPromotionForm" type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" data-promotion-checkbox class="h-4 w-4 rounded border-gray-300 text-[#296374]">
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $student?->lrn ?? '—' }}</td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">{{ $label }}</span></td>
                        <td class="px-5 py-4">
                            @if ($canPromote)
                                <form method="POST" action="{{ route('teacher.advisory.promotions.promote', [$section, $enrollment]) }}" onsubmit="return confirm('Promote this learner to the next grade?');">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Promote</button>
                                </form>
                            @elseif ($isGradeTwelve && $status === 'eligible')
                                <span class="text-xs font-medium text-gray-500">Grade 12 completed</span>
                            @elseif ($alreadyPromoted)
                                <span class="text-xs font-medium text-emerald-700">Already promoted{{ $nextAcademicYear ? ' to '.$nextAcademicYear->school_year : '' }}</span>
                            @elseif ($status === 'eligible' && ! $nextAcademicYear)
                                <span class="text-xs text-gray-500">Configure the next school year to promote.</span>
                            @else
                                <span class="text-xs text-gray-500">{{ $evaluation['reason'] ?: 'Promotion requirements are not yet complete.' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No active learners are assigned to this advisory section.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const selectAll = document.querySelector('[data-select-all]');
    const boxes = [...document.querySelectorAll('[data-promotion-checkbox]')];
    selectAll?.addEventListener('change', () => boxes.forEach((box) => { box.checked = selectAll.checked; }));
})();
</script>
@endsection
