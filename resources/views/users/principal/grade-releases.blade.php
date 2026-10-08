@extends('users.principal.layout')

@section('title', 'Grade Releases')

@section('content')
<div class="mb-6"><h1 class="text-xl md:text-2xl font-bold text-gray-700 tracking-tight">Grade Releases</h1></div>

@include('users.principal.partials.grade-release-toasts')

@include('users.partials.grade-record-filters', [
    'principalFilters' => true,
    'filterRoute' => route('principal.grade-releases'),
    'filterValues' => $filters,
    'statusOptions' => ['all' => 'All statuses', 'approved' => 'Awaiting release', 'released' => 'Released'],
])

<style>
    .grade-release-table tbody tr:nth-child(even) { background-color: #f8fafc; }
    .grade-release-table tbody tr:hover { background-color: #edf5f7; }
    .grade-release-table tbody tr:has(.grade-release-check:checked) { background-color: #dff0f3; }
    .grade-release-table tbody tr:has(.grade-release-check:checked) td:first-child { box-shadow: inset 4px 0 #296374; }
    .grade-release-table input[type="checkbox"] { width: 1.125rem; height: 1.125rem; accent-color: #296374; cursor: pointer; }
    .grade-release-table input[type="checkbox"]:disabled { cursor: default; opacity: .45; }
    .grade-release-table input[type="checkbox"]:focus-visible { outline: 2px solid #296374; outline-offset: 3px; }
</style>

<form action="{{ route('principal.grade-releases.bulk-release') }}" method="POST" id="grade-release-form">
    @csrf
    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800">Grade Records <span class="ml-2 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $assignments->count() }}</span></h2>
            </div>
            <button type="submit" id="release-selected-grades" disabled class="hidden inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50" style="background-color: #296374;">Release Grades</button>
        </div>
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Grade records">
            <table class="grade-release-table min-w-[850px] w-full text-left">
                <thead style="background-color: #296374;" class="text-xs font-semibold uppercase tracking-wide text-white">
                    <tr>
                        <th scope="col" class="w-14 px-5 py-4"><input type="checkbox" id="grade-release-check-all" class="rounded border-gray-300" aria-label="Select all releasable grade records"></th>
                        <th scope="col" class="px-5 py-4">Section</th>
                        <th scope="col" class="px-5 py-4">Subject</th>
                        <th scope="col" class="px-5 py-4">Teacher</th>
                        <th scope="col" class="px-5 py-4 text-center">Records</th>
                        <th scope="col" class="px-5 py-4">Status</th>
                        <th scope="col" class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($assignments as $assignment)
                        @php
                            $grades = $assignment->grades;
                            $approvedCount = $grades->where('status', 'approved')->count();
                            $releasedCount = $grades->where('status', 'released')->count();
                            $canRelease = $approvedCount > 0;
                            $subject = $assignment->subject;
                            $subjectLabel = $subject ? ($subject->code.' - '.$subject->title) : 'N/A';
                        @endphp
                        <tr class="transition-colors">
                            <td class="px-5 py-4">
                                @if($canRelease)
                                    <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->assignment_ID }}" class="grade-release-check rounded border-gray-300" aria-label="Select {{ $assignment->section?->name }} {{ $subjectLabel }}">
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $assignment->section?->name ?? 'N/A' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $assignment->section?->getRelation('gradeLevel')?->grade_label }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-slate-800">{{ $subject?->code ?? 'N/A' }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $subject?->title }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $assignment->staff ? $assignment->staff->last_name.', '.$assignment->staff->first_name : 'N/A' }}</td>
                            <td class="px-5 py-4 text-center text-sm font-semibold tabular-nums text-slate-800">{{ $grades->count() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col items-start gap-2">
                                    @if($approvedCount)
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $approvedCount }} awaiting release</span>
                                    @endif
                                    @if($releasedCount)
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ $releasedCount }} released</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('principal.grade-releases.show', ['assignment' => $assignment, 'status' => $canRelease ? 'approved' : 'released']) }}" class="inline-flex items-center whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-[#296374] transition-colors hover:border-[#296374] hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374]">View grades</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center"><p class="text-sm font-semibold text-slate-700">No grade records found</p><p class="mt-1 text-sm text-slate-500">Try adjusting your search or filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('grade-release-check-all');
    const releaseButton = document.getElementById('release-selected-grades');
    const checkboxes = Array.from(document.querySelectorAll('.grade-release-check'));

    const syncSelection = () => {
        const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
        selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        selectAll.disabled = checkboxes.length === 0;
        releaseButton.disabled = selectedCount === 0;
        releaseButton.classList.toggle('hidden', selectedCount === 0);
        releaseButton.textContent = `Release Grades (${selectedCount} selected)`;
    };

    selectAll.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
        syncSelection();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncSelection));
    document.getElementById('grade-release-form').addEventListener('submit', (event) => {
        if (!checkboxes.some((checkbox) => checkbox.checked)) event.preventDefault();
    });
    window.addEventListener('pageshow', syncSelection);
    syncSelection();
});
</script>
@endsection
