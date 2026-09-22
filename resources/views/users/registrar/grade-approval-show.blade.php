@extends('users.registrar.layout')

@section('title', 'Review Grade Submission')

@section('content')
@php
    $section = $assignment->section;
    $subject = $assignment->curriculumSubject?->subject;
    $teacher = $assignment->staff;
    $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
    $teacherName = $teacher ? trim($teacher->last_name . ', ' . $teacher->first_name . ' ' . $teacher->middle_name) : 'N/A';
    $latestSubmitted = $assignment->grades->max('submitted_at');
    $isApprovedView = ($status ?? 'submitted') === 'approved';
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">{{ $isApprovedView ? 'Approved Grade Record' : 'Review Grade Submission' }}</h1>
</div>

@if (session('status'))
    <div id="gradeApprovalToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span>{{ session('status') }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 hover:bg-emerald-50" data-dismiss-toast aria-label="Close notification">&times;</button>
    </div>
@endif

@if (session('error') || $errors->any())
    <div id="gradeApprovalErrorToast" role="alert" aria-live="assertive" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <span>{{ session('error') ?? $errors->first() }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-rose-700/70 hover:bg-rose-50" data-dismiss-toast aria-label="Close notification">&times;</button>
    </div>
@endif

<section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="grid md:grid-cols-2 md:divide-x md:divide-slate-200">
        <dl class="divide-y divide-slate-100 px-6">
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">School Year</dt><dd class="text-sm font-semibold text-[#296374]">{{ $assignment->academicYear?->school_year ?? 'N/A' }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">Section</dt><dd class="text-sm font-semibold text-[#296374]">{{ $section?->name ?? 'N/A' }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">Teacher</dt><dd class="text-right text-sm font-semibold text-[#296374]">{{ $teacherName }}</dd></div>
        </dl>
        <dl class="divide-y divide-slate-100 px-6">
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">Subject</dt><dd class="text-right text-sm font-semibold text-[#296374]">{{ $subjectLabel }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">Grade Level</dt><dd class="text-sm font-semibold text-[#296374]">{{ $section?->gradeLevel?->grade_label ?? ($section?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : 'N/A') }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-sm text-slate-500">{{ $isApprovedView ? 'Approved Records' : 'Submitted Records' }}</dt><dd class="text-sm font-semibold text-[#296374]">{{ $assignment->grades->count() }}</dd></div>
        </dl>
    </div>
</section>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-left">
            <thead>
                <tr class="border-b border-[#296374] bg-[#296374] text-xs font-bold uppercase tracking-wider text-white">
                    <th class="px-6 py-4">Student</th>
                    <th class="px-6 py-4">LRN</th>
                    @foreach($periods as $period)
                        <th class="px-6 py-4 text-center">{{ $period['label'] }}</th>
                    @endforeach
                    <th class="px-6 py-4 text-center">Average</th>
                    <th class="px-6 py-4">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($rows as $row)
                    <tr class="transition-colors odd:bg-white even:bg-slate-50/70 hover:bg-[#eaf3f5]">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $row['name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['lrn'] }}</td>
                        @foreach($periods as $period)
                            <td class="px-6 py-4 text-center text-sm text-gray-700">
                                {{ $row['period_values'][$period['key']] ?? '-' }}
                            </td>
                        @endforeach
                        <td class="px-6 py-4 text-center text-sm font-bold text-gray-800">
                            {{ $row['average'] !== null ? number_format($row['average'], 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold {{ $row['remarks'] === 'Passed' ? 'text-emerald-700' : ($row['remarks'] === 'Failed' ? 'text-rose-700' : 'text-gray-500') }}">{{ $row['remarks'] !== '' ? $row['remarks'] : '-' }}</td>
                    </tr>
                @endforeach

                @if($rows->isEmpty())
                    <tr>
                        <td colspan="{{ 4 + count($periods) }}" class="px-6 py-12 text-center text-gray-500">No submitted grades found for this assignment.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if(! $isApprovedView)
        <div class="flex flex-col justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-5 sm:flex-row">
            <form action="{{ route('registrar.grade-approvals.approve', $assignment) }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Approve Grades</button>
            </form>
            <button type="button" id="openGradeReturnModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md bg-rose-500 hover:bg-rose-600">Return to Teacher</button>
        </div>
    @else
        <div class="px-6 py-5 border-t border-white/20 bg-white/50 text-sm text-gray-600">
            Approved grades are shown here for reference when a teacher requests a change.
        </div>
    @endif
</div>

@if (! $isApprovedView)
    <div id="gradeReturnModal" class="fixed inset-0 z-[130] hidden items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true" aria-labelledby="gradeReturnModalTitle">
        <form action="{{ route('registrar.grade-approvals.reject', $assignment) }}" method="POST" class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
            @csrf
            <div class="bg-rose-600 px-6 py-4 text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-rose-100">Return grade submission</p>
                <h2 id="gradeReturnModalTitle" class="mt-1 text-lg font-bold">Select a return reason</h2>
            </div>
            <div class="space-y-4 px-6 py-5">
                <p class="text-sm leading-relaxed text-gray-600">The teacher will see this reason and can update the returned grades before submitting again.</p>
                <div>
                    <label for="grade_return_reason_ID" class="mb-1.5 block text-sm font-semibold text-gray-700">Return reason <span class="text-rose-600">*</span></label>
                    <select id="grade_return_reason_ID" name="grade_return_reason_ID" required class="w-full rounded-lg border px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/15 {{ $errors->has('grade_return_reason_ID') ? 'border-rose-400' : 'border-gray-300' }}">
                        <option value="">Select a reason</option>
                        @foreach ($gradeReturnReasons as $reason)
                            <option value="{{ $reason->reason_ID }}" @selected((string) old('grade_return_reason_ID') === (string) $reason->reason_ID)>{{ $reason->name }}</option>
                        @endforeach
                    </select>
                    @error('grade_return_reason_ID')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" data-close-grade-return-modal class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700">Return to Teacher</button>
            </div>
        </form>
    </div>
@endif

<script>
    (function () {
        document.querySelectorAll('[data-dismiss-toast]').forEach((button) => button.addEventListener('click', () => button.closest('[role]')?.remove()));
        window.setTimeout(() => document.getElementById('gradeApprovalToast')?.remove(), 4000);
        window.setTimeout(() => document.getElementById('gradeApprovalErrorToast')?.remove(), 6000);

        const modal = document.getElementById('gradeReturnModal');
        const openModal = () => modal?.classList.replace('hidden', 'flex');
        const closeModal = () => modal?.classList.replace('flex', 'hidden');

        document.getElementById('openGradeReturnModal')?.addEventListener('click', openModal);
        document.querySelector('[data-close-grade-return-modal]')?.addEventListener('click', closeModal);
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        @if ($errors->has('grade_return_reason_ID'))
            openModal();
        @endif
    })();
</script>
@endsection
