@extends('users.registrar.layout')

@section('title', 'Grade Approvals')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">Grade Approvals</h1>
</div>

@push('toasts')
@if (session('status'))
    <div id="gradeApprovalsToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
        <span>{{ session('status') }}</span><button type="button" class="ml-auto" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

@if (session('error'))
    <div id="gradeApprovalsErrorToast" role="alert" aria-live="assertive" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl">
        <span>{{ session('error') }}</span><button type="button" class="ml-auto" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

@endpush

@include('users.partials.grade-record-filters', [
    'principalFilters' => false,
    'filterRoute' => route('registrar.grade-approvals'),
    'filterValues' => $filters,
    'statusOptions' => ['' => 'All statuses', 'submitted' => 'Submitted', 'approved' => 'Approved', 'released' => 'Released'],
])

@if ($errors->any())
    <div role="alert" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
@endif

<style>
    .grade-approval-table tbody tr:nth-child(even) { background-color: #f8fafc; }
    .grade-approval-table tbody tr:hover { background-color: #edf5f7; }
    .grade-approval-table tbody tr:has(.grade-selection:checked) { background-color: #dff0f3; }
    .grade-approval-table tbody tr:has(.grade-selection:checked) td:first-child { box-shadow: inset 4px 0 #296374; }
    .grade-approval-table input[type="checkbox"] { width: 1.125rem; height: 1.125rem; accent-color: #296374; cursor: pointer; }
    .grade-approval-table input[type="checkbox"]:disabled { cursor: default; opacity: .45; }
    .grade-approval-table input[type="checkbox"]:focus-visible { outline: 2px solid #296374; outline-offset: 3px; }
</style>

<form id="bulkGradeApprovalForm" action="{{ route('registrar.grade-approvals.approve-selected') }}" method="POST">
    @csrf
    @foreach ($filters as $filter => $value)
        <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
    @endforeach
    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-bold text-slate-800">Subjects <span class="ml-2 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $assignments->total() }}</span></h2>
            <div class="flex flex-wrap items-center gap-3">
                <span id="gradeSelectionCount" role="status" class="text-sm text-slate-600">0 submissions selected</span>
                <button id="approveSelectedGrades" type="submit" disabled class="hidden inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50" style="background-color: #296374;">Approve selected</button>
            </div>
        </div>
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Grade approval records">
            <table class="grade-approval-table min-w-[950px] w-full text-left">
                <thead style="background-color: #296374;" class="text-xs font-semibold uppercase tracking-wide text-white">
                    <tr>
                        <th scope="col" class="w-14 px-5 py-4"><input id="selectAllGrades" type="checkbox" aria-label="Select all submitted rows" class="rounded border-gray-300"></th>
                        <th scope="col" class="min-w-44 px-5 py-4">Section</th>
                        <th scope="col" class="min-w-56 px-5 py-4">Subject</th>
                        <th scope="col" class="min-w-48 px-5 py-4">Teacher</th>
                        <th scope="col" class="px-5 py-4 text-center">Records</th>
                        <th scope="col" class="min-w-44 px-5 py-4">Status</th>
                        <th scope="col" class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($assignments as $assignment)
                    @php
                        $section = $assignment->section;
                        $subject = $assignment->subject;
                        $teacher = $assignment->staff;
                        $grades = $assignment->grades ?? collect();
                        $submittedCount = $grades->where('status', 'submitted')->count();
                        $approvedCount = $grades->where('status', 'approved')->count();
                        $releasedCount = $grades->where('status', 'released')->count();
                        $rowStatus = $submittedCount ? 'submitted' : ($approvedCount ? 'approved' : 'released');
                        $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : 'N/A';
                        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'N/A';
                        $reviewUrl = route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => $rowStatus]);
                    @endphp
                    <tr class="transition-colors">
                        <td class="px-5 py-4">
                            @if ($rowStatus === 'submitted')
                                <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->assignment_ID }}" aria-label="Select {{ $subjectLabel }} in {{ $section?->name }}" class="grade-selection rounded border-gray-300">
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ $section?->name ?? 'N/A' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $section?->gradeLevel?->grade_label ?? ($section?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : 'N/A') }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-sm font-semibold text-slate-800">{{ $subject?->code ?? 'N/A' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $subject?->title }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">{{ $teacherName }}</td>
                        <td class="px-5 py-4 text-center text-sm font-semibold tabular-nums text-slate-800">{{ $grades->count() }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-col items-start gap-2">
                                @if ($submittedCount)
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Submitted</span>
                                @endif
                                @if ($approvedCount)
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                                @endif
                                @if ($releasedCount)
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>Released</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right"><a href="{{ $reviewUrl }}" class="inline-flex items-center whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-[#296374] transition-colors hover:border-[#296374] hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374]">{{ $rowStatus === 'submitted' ? 'Review grades' : 'View grades' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-14 text-center"><p class="text-sm font-semibold text-slate-700">No grade records found</p><p class="mt-1 text-sm text-slate-500">Try adjusting your search or filters.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($assignments->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</form>

<script>
    const gradeSelections = [...document.querySelectorAll('.grade-selection')];
    const selectAllGrades = document.getElementById('selectAllGrades');
    const approveSelectedGrades = document.getElementById('approveSelectedGrades');
    const updateGradeSelection = () => {
        const count = gradeSelections.filter((checkbox) => checkbox.checked).length;
        document.getElementById('gradeSelectionCount').textContent = `${count} submission${count === 1 ? '' : 's'} selected`;
        approveSelectedGrades.disabled = count === 0;
        approveSelectedGrades.classList.toggle('hidden', count === 0);
        selectAllGrades.disabled = gradeSelections.length === 0;
        selectAllGrades.checked = count > 0 && count === gradeSelections.length;
        selectAllGrades.indeterminate = count > 0 && count < gradeSelections.length;
    };
    selectAllGrades.addEventListener('change', () => {
        gradeSelections.forEach((checkbox) => checkbox.checked = selectAllGrades.checked);
        updateGradeSelection();
    });
    gradeSelections.forEach((checkbox) => checkbox.addEventListener('change', updateGradeSelection));
    document.getElementById('bulkGradeApprovalForm').addEventListener('submit', (event) => {
        if (!gradeSelections.some((checkbox) => checkbox.checked)) {
            event.preventDefault();
            return;
        }
        approveSelectedGrades.disabled = true;
        approveSelectedGrades.textContent = 'Approving…';
    });
    window.addEventListener('pageshow', updateGradeSelection);
    updateGradeSelection();
    document.querySelectorAll('[data-dismiss-grade-toast]').forEach((button) => button.addEventListener('click', () => button.closest('[role]')?.remove()));
    window.setTimeout(() => document.getElementById('gradeApprovalsToast')?.remove(), 4000);
    window.setTimeout(() => document.getElementById('gradeApprovalsErrorToast')?.remove(), 6000);
</script>
@endsection
