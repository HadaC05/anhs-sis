@extends('users.principal.layout')

@section('title', 'Grade Releases')

@section('content')
@php
    $tabs = [
        'approved' => [
            'label' => 'Awaiting Release',
            'heading' => 'Registrar Approved Grades',
            'empty' => 'No approved grades are waiting for release.',
            'assignments' => $approvedAssignments ?? collect(),
            'date_label' => 'Approved',
        ],
        'released' => [
            'label' => 'Released',
            'heading' => 'Released Grades',
            'empty' => 'No grades have been released yet.',
            'assignments' => $releasedAssignments ?? collect(),
            'date_label' => 'Released',
        ],
    ];
@endphp

<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Grade Releases</h1>
    <p class="text-gray-600 text-sm md:text-base">Review registrar-approved grades and release them to students when ready.</p>
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg bg-red-100 border border-red-300 text-red-700 px-4 py-3">
        {{ $errors->first() }}
    </div>
@endif

<div class="mb-6 inline-flex rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    @foreach($tabs as $key => $tab)
        <button type="button" class="grade-release-tab-btn px-5 py-2.5 text-sm font-semibold {{ $loop->first ? 'text-white' : 'text-gray-600 bg-white' }}" style="{{ $loop->first ? 'background-color: #296374;' : '' }}" data-grade-release-tab="{{ $key }}">
            {{ $tab['label'] }}
            <span class="ml-2 rounded-full px-2 py-0.5 text-[11px] {{ $loop->first ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $tab['assignments']->count() }}</span>
        </button>
    @endforeach
</div>

@foreach($tabs as $key => $tab)
    <div id="grade-release-tab-{{ $key }}" class="grade-release-tab-panel {{ $loop->first ? '' : 'hidden' }}">
        <form action="{{ route('principal.grade-releases.bulk-release') }}" method="POST">
            @csrf
            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
                <div class="px-6 py-4 border-b border-white/20 bg-white/40 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">{{ $tab['heading'] }}</h2>
                    @if($key === 'approved' && $tab['assignments']->isNotEmpty())
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">
                            Release Selected
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                                @if($key === 'approved')
                                    <th class="px-6 py-4 w-12">
                                        <input type="checkbox" class="grade-release-check-all rounded border-gray-300" aria-label="Select all grade batches">
                                    </th>
                                @endif
                                <th class="px-6 py-4">Section</th>
                                <th class="px-6 py-4">Subject</th>
                                <th class="px-6 py-4">Teacher</th>
                                <th class="px-6 py-4">Records</th>
                                <th class="px-6 py-4">{{ $tab['date_label'] }}</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/20">
                            @if($tab['assignments']->isEmpty())
                                <tr>
                                    <td colspan="{{ $key === 'approved' ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">{{ $tab['empty'] }}</td>
                                </tr>
                            @else
                                @foreach($tab['assignments'] as $assignment)
                                    @php
                                        $section = $assignment->section;
                                        $subject = $assignment->curriculumSubject?->subject;
                                        $teacher = $assignment->staff;
                                        $grades = $assignment->grades ?? collect();
                                        $latestDate = $key === 'approved' ? $grades->max('reviewed_at') : $grades->max('updated_at');
                                        $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : 'N/A';
                                        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'N/A';
                                    @endphp
                                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                                        @if($key === 'approved')
                                            <td class="px-6 py-4">
                                                <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->assignment_ID }}" class="grade-release-check rounded border-gray-300" aria-label="Select {{ $subjectLabel }}">
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $section?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $teacherName }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grades->count() }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $latestDate ? $latestDate->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('principal.grade-releases.show', ['assignment' => $assignment, 'status' => $key]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
@endforeach

<script>
    (function () {
        const buttons = Array.from(document.querySelectorAll('.grade-release-tab-btn'));
        const panels = Array.from(document.querySelectorAll('.grade-release-tab-panel'));

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const tab = button.dataset.gradeReleaseTab;

                buttons.forEach((item) => {
                    const isActive = item.dataset.gradeReleaseTab === tab;
                    item.classList.toggle('text-white', isActive);
                    item.classList.toggle('text-gray-600', !isActive);
                    item.classList.toggle('bg-white', !isActive);
                    item.style.backgroundColor = isActive ? '#296374' : 'white';
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.id !== `grade-release-tab-${tab}`);
                });
            });
        });

        document.querySelectorAll('.grade-release-check-all').forEach((checkAll) => {
            checkAll.addEventListener('change', () => {
                const panel = checkAll.closest('.grade-release-tab-panel');
                panel.querySelectorAll('.grade-release-check').forEach((checkbox) => {
                    checkbox.checked = checkAll.checked;
                });
            });
        });
    })();
</script>
@endsection
