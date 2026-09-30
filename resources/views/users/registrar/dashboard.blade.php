@extends('users.registrar.layout')

@section('title', 'Dashboard')

@section('content')
@php
    $yearFilters = $selectedYear ? ['academic_year_id' => $selectedYear->SY_ID] : [];
    $approvalUrl = route('registrar.grade-approvals', $yearFilters + ['status' => 'submitted', 'term_id' => '']);
    $subjectsUrl = route('registrar.class-subjects.index', $selectedYear ? ['SY_ID' => $selectedYear->SY_ID] : []);
    $studentsUrl = route('registrar.students', $yearFilters);
    $totalGrades = array_sum($gradeCounts);
    $statusRows = [
        ['key' => 'draft', 'label' => 'Draft', 'color' => 'bg-amber-400', 'dot' => 'bg-amber-100 text-amber-800'],
        ['key' => 'submitted', 'label' => 'Awaiting approval', 'color' => 'bg-violet-500', 'dot' => 'bg-violet-100 text-violet-800'],
        ['key' => 'approved', 'label' => 'Approved', 'color' => 'bg-emerald-500', 'dot' => 'bg-emerald-100 text-emerald-800'],
        ['key' => 'released', 'label' => 'Released', 'color' => 'bg-sky-500', 'dot' => 'bg-sky-100 text-sky-800'],
        ['key' => 'rejected', 'label' => 'Returned for correction', 'color' => 'bg-rose-500', 'dot' => 'bg-rose-100 text-rose-800'],
    ];
@endphp
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <h1 class="text-2xl font-bold text-gray-800 sm:text-3xl">Registrar Dashboard</h1>
        <form method="GET" action="{{ route('registrar.dashboard') }}" class="shrink-0">
            <label for="dashboard-year" class="sr-only">School year</label>
            <select id="dashboard-year" name="academic_year_id" onchange="this.form.requestSubmit()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#296374] focus:ring-[#296374]" @disabled($academicYears->isEmpty())>
                @forelse ($academicYears as $year)
                <option value="{{ $year->SY_ID }}" @selected($selectedYear?->SY_ID === $year->SY_ID)>{{ $year->school_year }}{{ $year->status ? ' (Active)' : '' }}</option>
                @empty
                <option>No school years</option>
                @endforelse
            </select>
        </form>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Grades awaiting approval', 'value' => $gradeCounts['submitted'], 'detail' => 'Submitted grade records to review', 'url' => $approvalUrl, 'color' => 'text-violet-700', 'border' => 'border-violet-200'],
            ['label' => 'Subjects awaiting review', 'value' => $pendingSubjects, 'detail' => 'Class subjects with submitted grades', 'url' => $approvalUrl, 'color' => 'text-amber-700', 'border' => 'border-amber-200'],
            ['label' => 'Class subjects', 'value' => $subjectCount, 'detail' => number_format($sectionCount).' sections in this school year', 'url' => $subjectsUrl, 'color' => 'text-[#296374]', 'border' => 'border-teal-200'],
            ['label' => 'Active students', 'value' => $studentCount, 'detail' => 'Enrolled and temporarily enrolled', 'url' => $studentsUrl, 'color' => 'text-sky-700', 'border' => 'border-sky-200'],
        ] as $card)
        <a href="{{ $card['url'] }}" class="group rounded-xl border {{ $card['border'] }} bg-white p-5 shadow-sm transition hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#296374]">
            <p class="text-sm font-medium text-gray-600">{{ $card['label'] }}</p>
            <p class="mt-3 text-3xl font-bold tabular-nums {{ $card['color'] }}">{{ number_format($card['value']) }}</p>
            <p class="mt-2 text-xs leading-5 text-gray-500">{{ $card['detail'] }}</p>
            <span class="mt-4 inline-block text-xs font-semibold {{ $card['color'] }}">Open module <span aria-hidden="true">&rarr;</span></span>
        </a>
        @endforeach
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2" aria-labelledby="review-queue-title">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5">
                <div>
                    <h2 id="review-queue-title" class="text-lg font-bold text-gray-800">Ready for review</h2>
                    <p class="mt-1 text-xs text-gray-500">Up to six class subjects, oldest submissions first.</p>
                </div>
                <a href="{{ $approvalUrl }}" class="text-sm font-semibold text-[#296374] hover:underline">View all approvals &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Class subjects with grades awaiting approval</caption>
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th scope="col" class="px-5 py-3">Class / subject</th>
                        <th scope="col" class="px-5 py-3">Pending grades</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Action</span></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($reviewQueue as $assignment)
                        <tr>
                            <th scope="row" class="px-5 py-4 font-normal">
                                <p class="font-semibold text-gray-800">{{ $assignment->curriculumSubject?->subject?->title ?? 'Subject unavailable' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $assignment->section?->getRelation('gradeLevel')?->grade_label }} &middot; {{ $assignment->section?->name ?? 'Section unavailable' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $assignment->staff ? trim($assignment->staff->first_name.' '.$assignment->staff->last_name) : 'Unassigned teacher' }}</p>
                            </th>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-bold tabular-nums text-violet-800">{{ number_format($assignment->pending_grades_count) }}</span></td>
                            <td class="px-5 py-4"><a href="{{ route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => 'submitted']) }}" class="inline-flex rounded-lg bg-[#296374] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e4d5c]" aria-label="Review grades for {{ $assignment->curriculumSubject?->subject?->title }} in {{ $assignment->section?->name }}">Review</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-6 py-12 text-center">
                            <p class="font-semibold text-gray-700">No grades awaiting approval</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-gray-500">Submitted grades for this school year will appear here when they are ready for review.</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="grade-status-title">
            <h2 id="grade-status-title" class="text-lg font-bold text-gray-800">Grade record status</h2>
            <p class="mt-1 text-xs leading-5 text-gray-500">{{ number_format($totalGrades) }} recorded grades across all terms. Counts represent student grades, not subjects.</p>
            <div class="my-5 flex h-3 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                @foreach ($statusRows as $row)
                <div class="{{ $row['color'] }}" style="width: {{ $totalGrades > 0 ? ($gradeCounts[$row['key']] / $totalGrades * 100) : 0 }}%"></div>
                @endforeach
            </div>
            <dl class="space-y-4">
                @foreach ($statusRows as $row)
                <div class="flex items-center justify-between gap-2 text-sm">
                    <dt><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['dot'] }}">{{ $row['label'] }}</span></dt>
                    <dd class="font-bold tabular-nums text-gray-700">{{ number_format($gradeCounts[$row['key']]) }}</dd>
                </div>
                @endforeach
            </dl>
            @if ($totalGrades === 0)
            <p class="mt-5 border-t border-gray-100 pt-4 text-xs text-gray-500">No grade records for this school year yet.</p>
            @endif
        </section>
    </div>


</div>
@endsection
