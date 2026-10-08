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

<form id="bulkGradeApprovalForm" action="{{ route('registrar.grade-approvals.approve-selected') }}" method="POST" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    @csrf
    @foreach ($filters as $filter => $value)
        <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
    @endforeach
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4">
        <span id="gradeSelectionCount" role="status" class="text-sm text-gray-600">0 submissions selected</span>
        <button id="approveSelectedGrades" type="submit" disabled class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Approve selected</button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-[950px] w-full text-left">
            <thead><tr class="border-b border-[#296374] bg-[#296374] text-xs font-bold uppercase tracking-wider text-white">
                <th class="px-4 py-4"><input id="selectAllGrades" type="checkbox" aria-label="Select all submitted rows" class="h-4 w-4 rounded"></th>
                <th class="px-6 py-4">Section</th><th class="px-6 py-4">Grade Level</th><th class="px-6 py-4">Subject</th><th class="px-6 py-4">Teacher</th><th class="px-6 py-4">Grades</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assignments as $assignment)
                    @php
                        $section = $assignment->section;
                        $subject = $assignment->subject;
                        $teacher = $assignment->staff;
                        $grades = $assignment->grades ?? collect();
                        $rowStatus = $grades->contains('status', 'submitted') ? 'submitted' : ($grades->contains('status', 'approved') ? 'approved' : 'released');
                        $statusClasses = match ($rowStatus) {
                            'submitted' => 'bg-amber-100 text-amber-800',
                            'approved' => 'bg-emerald-100 text-emerald-800',
                            'released' => 'bg-sky-100 text-sky-800',
                        };
                        $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : '—';
                        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : '—';
                        $reviewUrl = route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => $rowStatus]);
                    @endphp
                    <tr class="transition-colors odd:bg-white even:bg-slate-50/70 hover:bg-[#eaf3f5]">
                        <td class="px-4 py-4">
                            @if ($rowStatus === 'submitted')
                                <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->assignment_ID }}" aria-label="Select {{ $subjectLabel }} in {{ $section?->name }}" class="grade-selection h-4 w-4 rounded">
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $section?->gradeLevel?->grade_label ?? ($section?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : '—') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $teacherName }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grades->count() }}</td>
                        <td class="px-6 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses }}">{{ ucfirst($rowStatus) }}</span></td>
                        <td class="px-6 py-4 text-right"><a href="{{ $reviewUrl }}" class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">{{ $rowStatus === 'submitted' ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">No grade submissions match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
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
