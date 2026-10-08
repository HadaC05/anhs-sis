@extends('users.'.$portal.'.layout')

@section('title', 'Remediation Case')

@section('content')
@php
    $student = $case->enrollment->student;
    $name = trim(($student?->last_name ?? '').', '.($student?->first_name ?? '').' '.($student?->middle_name ?? ''));
    $statusLabel = match ($case->status) {
        'in_progress' => 'In Progress',
        'awaiting_approval' => 'Awaiting Principal Approval',
        'approved_passed' => 'Approved — Passed',
        'needs_intervention' => 'Approved — Needs Intervention Review',
        default => str($case->status)->headline(),
    };
    $backUrl = match ($portal) {
        'teacher' => route('teacher.advisory.promotions.index', $section),
        'principal' => route('principal.promotions.index', ['eligibility' => 'conditionally_promoted']),
        default => route('guidance.promotions.index', ['eligibility' => 'conditionally_promoted']),
    };
    $updateUrl = $portal === 'teacher'
        ? route('teacher.advisory.remediations.update', [$section, $case])
        : ($portal === 'guidance' ? route('guidance.remediations.update', $case) : null);
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ $backUrl }}" class="text-sm font-semibold text-[#296374] hover:underline">← Back to promotions</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Remediation Case</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $name }} · {{ $case->enrollment->grade_level }} · {{ $case->enrollment->academicYear?->school_year }}</p>
    </div>
    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ $statusLabel }}</span>
</div>

<x-password-reset-toasts test-prefix="remediation" position-class="top-24" />

<div class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <dl class="grid gap-4 text-sm sm:grid-cols-3">
        <div><dt class="font-semibold text-gray-500">Started by</dt><dd class="mt-1 text-gray-900">{{ trim(($case->starter?->first_name ?? '').' '.($case->starter?->last_name ?? '')) }}</dd></div>
        <div><dt class="font-semibold text-gray-500">Approved by</dt><dd class="mt-1 text-gray-900">{{ $case->approver ? trim($case->approver->first_name.' '.$case->approver->last_name) : 'Pending' }}</dd></div>
        <div><dt class="font-semibold text-gray-500">Formula</dt><dd class="mt-1 text-gray-900">RFG = (Final Rating + Remedial Class Mark) ÷ 2</dd></div>
    </dl>
</div>

<form method="POST" action="{{ $updateUrl ?? '#' }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    @csrf
    @if ($updateUrl) @method('PATCH') @endif
    <div class="grid gap-4 border-b border-gray-200 p-5 sm:grid-cols-2">
        <label class="text-sm font-semibold text-gray-700">Start date
            <input type="date" name="start_date" value="{{ old('start_date', $case->start_date?->format('Y-m-d')) }}" @disabled(! $editable) class="mt-1 block h-10 w-full rounded-lg border border-gray-300 px-3 disabled:bg-gray-100">
        </label>
        <label class="text-sm font-semibold text-gray-700">End date
            <input type="date" name="end_date" value="{{ old('end_date', $case->end_date?->format('Y-m-d')) }}" @disabled(! $editable) class="mt-1 block h-10 w-full rounded-lg border border-gray-300 px-3 disabled:bg-gray-100">
        </label>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500"><tr><th class="px-5 py-3">Learning area</th><th class="px-5 py-3 text-center">Final rating</th><th class="px-5 py-3 text-center">Remedial class mark</th><th class="px-5 py-3 text-center">Recomputed final grade</th><th class="px-5 py-3">Remarks</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($case->subjects as $subject)
                @php $mark = old('subjects.'.$subject->remediation_subject_ID.'.remedial_class_mark', $subject->remedial_class_mark); @endphp
                <tr>
                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $subject->subject?->title ?? $subject->subject?->code }}</td>
                    <td class="px-5 py-4 text-center">{{ number_format((float) $subject->original_final_grade, 2) }}</td>
                    <td class="px-5 py-4 text-center"><input type="number" min="0" max="100" step="0.01" name="subjects[{{ $subject->remediation_subject_ID }}][remedial_class_mark]" value="{{ $mark }}" @disabled(! $editable) class="h-9 w-28 rounded-lg border border-gray-300 px-2 text-center disabled:bg-gray-100"></td>
                    <td class="px-5 py-4 text-center font-bold">{{ $subject->recomputed_final_grade === null ? '—' : number_format((float) $subject->recomputed_final_grade, 2) }}</td>
                    <td class="px-5 py-4"><input type="text" maxlength="255" name="subjects[{{ $subject->remediation_subject_ID }}][remarks]" value="{{ old('subjects.'.$subject->remediation_subject_ID.'.remarks', $subject->remarks) }}" @disabled(! $editable) class="h-9 w-full rounded-lg border border-gray-300 px-2 disabled:bg-gray-100"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 p-5">
        <label class="text-sm font-semibold text-gray-700">Case remarks
            <textarea name="remarks" rows="3" @disabled(! $editable) class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 disabled:bg-gray-100">{{ old('remarks', $case->remarks) }}</textarea>
        </label>
        @if ($editable)
        <div class="mt-4 flex flex-wrap justify-end gap-3">
            <button type="submit" name="action" value="save" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700">Save Draft</button>
            <button type="submit" name="action" value="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white">Submit for Approval</button>
        </div>
        @endif
    </div>
</form>

@if ($portal === 'principal' && $case->status === 'awaiting_approval')
<form method="POST" action="{{ route('principal.remediations.approve', $case) }}" class="mt-5 flex justify-end" data-confirm-action="Approve" data-confirm-title="Approve remediation results?" data-confirm-message="This will resolve the remediation result using the displayed recomputed final grades.">
    @csrf
    <button type="submit" class="rounded-lg bg-[#296374] px-5 py-2.5 text-sm font-bold text-white">Approve Results</button>
</form>
@include('users.partials.archive-confirmation')
@endif
@endsection
