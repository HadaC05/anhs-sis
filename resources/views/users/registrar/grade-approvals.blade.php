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
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
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
</script>
@endsection
