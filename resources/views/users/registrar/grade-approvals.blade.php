@extends('users.registrar.layout')

@section('title', 'Grade Approvals')

@section('content')
@php
    $tabs = [
        'pending' => [
            'label' => 'Pending',
            'heading' => 'Pending Submissions',
            'empty' => 'No submitted grades awaiting approval.',
            'assignments' => $pendingAssignments ?? collect(),
            'status' => 'submitted',
            'date_label' => 'Last Submitted',
        ],
        'approved' => [
            'label' => 'Approved',
            'heading' => 'Approved Grades',
            'empty' => 'No approved grades found.',
            'assignments' => $approvedAssignments ?? collect(),
            'status' => 'approved',
            'date_label' => 'Last Approved',
        ],
    ];
@endphp

<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Grade Approvals</h1>
    <p class="text-gray-600 text-sm md:text-base">Review pending submissions and revisit approved grades when teachers request changes.</p>
</div>

@if (session('status'))
    <div id="gradeApprovalsToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span>{{ session('status') }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 hover:bg-emerald-50" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

@if (session('error'))
    <div id="gradeApprovalsErrorToast" role="alert" aria-live="assertive" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <span>{{ session('error') }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-rose-700/70 hover:bg-rose-50" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

<div class="mb-6 inline-flex rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    @foreach($tabs as $key => $tab)
        <button type="button" class="grade-tab-btn px-5 py-2.5 text-sm font-semibold {{ $loop->first ? 'text-white' : 'text-gray-600 bg-white' }}" style="{{ $loop->first ? 'background-color: #296374;' : '' }}" data-grade-tab="{{ $key }}">
            {{ $tab['label'] }}
            <span class="ml-2 rounded-full px-2 py-0.5 text-[11px] {{ $loop->first ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $tab['assignments']->count() }}</span>
        </button>
    @endforeach
</div>

@foreach($tabs as $key => $tab)
    <div id="grade-tab-{{ $key }}" class="grade-tab-panel {{ $loop->first ? '' : 'hidden' }}">
        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
            <div class="px-6 py-4 border-b border-white/20 bg-white/40">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">{{ $tab['heading'] }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                            <th class="px-6 py-4">Section</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Teacher</th>
                            <th class="px-6 py-4">{{ $key === 'pending' ? 'Submitted Grades' : 'Approved Grades' }}</th>
                            <th class="px-6 py-4">{{ $tab['date_label'] }}</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/20">
                        @forelse($tab['assignments'] as $assignment)
                            @php
                                $section = $assignment->section;
                                $subject = $assignment->curriculumSubject?->subject;
                                $teacher = $assignment->staff;
                                $grades = $assignment->grades ?? collect();
                                $latestDate = $key === 'pending' ? $grades->max('submitted_at') : $grades->max('reviewed_at');
                                $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : '—';
                                $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : '—';
                                $reviewUrl = $key === 'approved'
                                    ? route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => 'approved'])
                                    : route('registrar.grade-approvals.show', $assignment);
                            @endphp
                            <tr class="hover:bg-white/30 transition-all bg-white/10">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $teacherName }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $grades->count() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $latestDate ? $latestDate->format('M d, Y h:i A') : '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ $reviewUrl }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">
                                            {{ $key === 'pending' ? 'Review' : 'View' }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">{{ $tab['empty'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach

<script>
    (function () {
        const buttons = Array.from(document.querySelectorAll('.grade-tab-btn'));
        const panels = Array.from(document.querySelectorAll('.grade-tab-panel'));

        function activate(tab) {
            buttons.forEach((button) => {
                const isActive = button.dataset.gradeTab === tab;
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('text-gray-600', !isActive);
                button.classList.toggle('bg-white', !isActive);
                button.style.backgroundColor = isActive ? '#296374' : 'white';
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.id !== `grade-tab-${tab}`);
            });
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => activate(button.dataset.gradeTab));
        });
    })();

    document.querySelectorAll('[data-dismiss-grade-toast]').forEach((button) => button.addEventListener('click', () => button.closest('[role]')?.remove()));
    window.setTimeout(() => document.getElementById('gradeApprovalsToast')?.remove(), 4000);
    window.setTimeout(() => document.getElementById('gradeApprovalsErrorToast')?.remove(), 6000);
</script>
@endsection
