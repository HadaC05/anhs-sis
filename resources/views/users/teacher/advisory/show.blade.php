@extends('users.teacher.layout')

@section('title', 'Advisory Grades')

@section('content')
@php
    $selectedTerm = request('term', 'all');
    $availablePeriodKeys = collect($periods)->pluck('key')->take(4)->values();
    $periodKeys = $selectedTerm !== 'all' && $availablePeriodKeys->contains($selectedTerm) ? collect([$selectedTerm]) : $availablePeriodKeys;
    $periodLabels = collect($periods)->pluck('label', 'key');
    $subjectColumnSpan = max(1, $periodKeys->count()) + 1;
    $periodGroupLabel = \App\Models\GradingTerm::periodGroupLabel($periods);
@endphp

@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'grades'])

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="border-b border-gray-100 bg-gray-50/60 px-4 py-4 lg:px-6">
        <form method="GET" action="{{ route('teacher.advisory.show', $section) }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-start">
            <div>
                <label for="assignment_id" class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Subject</label>
                <select id="assignment_id" name="assignment_id" onchange="this.form.submit()" class="h-9 min-w-52 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20">
                    <option value="">All subjects</option>
                    @foreach($availableAssignments as $assignment)
                        @php $subject = $assignment->curriculumSubject?->subject; @endphp
                        <option value="{{ $assignment->assignment_ID }}" @selected($selectedAssignmentId === $assignment->assignment_ID)>{{ $subject?->code ?: $subject?->title ?: 'Subject' }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="search" class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Search learners</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Name" class="h-9 min-w-48 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20"></div>
            <div><label for="term" class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">View {{ $periodGroupLabel }}</label><select id="term" name="term" onchange="this.form.submit()" class="h-9 min-w-40 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20"><option value="all" @selected($selectedTerm === 'all')>All {{ $periodGroupLabel }}s</option>@foreach($availablePeriodKeys as $index => $periodKey)<option value="{{ $periodKey }}" @selected($selectedTerm === $periodKey)>{{ $periodLabels[$periodKey] ?? ($periodGroupLabel.' '.($index + 1)) }}</option>@endforeach</select></div>
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#296374] px-4 text-sm font-semibold text-white transition hover:bg-[#1f4e5c]">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto p-4 md:p-6"><table class="w-full min-w-[760px] border-collapse text-[11px] text-gray-900"><thead><tr><th rowspan="3" class="w-10 border border-gray-200 bg-gray-50 px-2 py-3 text-center font-bold">No.</th><th rowspan="3" class="min-w-64 border border-gray-200 bg-gray-50 px-3 py-3 text-left font-bold">Names of Learners</th>@forelse($assignments as $assignment)@php $subject = $assignment->curriculumSubject?->subject; @endphp<th colspan="{{ $subjectColumnSpan }}" class="border border-gray-200 bg-gray-50 px-2 py-3 text-center font-bold">{{ strtoupper($subject?->code ?: $subject?->title ?: 'Subject') }}</th>@empty<th class="border border-gray-200 bg-gray-50 px-2 py-3 text-center font-bold">No Subjects</th>@endforelse<th rowspan="3" class="w-20 border border-gray-200 bg-gray-50 px-2 py-3 text-center font-bold">Gen.<br>Average</th></tr>@if($assignments->isNotEmpty())<tr>@foreach($assignments as $assignment)<th colspan="{{ max(1, $periodKeys->count()) }}" class="border border-gray-200 bg-gray-50 px-2 py-2 text-center font-bold">{{ $periodGroupLabel }}</th><th rowspan="2" class="w-14 border border-gray-200 bg-gray-50 px-2 py-2 text-center font-bold">Final<br>Grade</th>@endforeach</tr><tr>@foreach($assignments as $assignment)@forelse($periodKeys as $periodKey)@php $termNumber = $availablePeriodKeys->search($periodKey) + 1; @endphp<th class="w-10 border border-gray-200 bg-gray-50 px-2 py-2 text-center font-bold" title="{{ $periodLabels[$periodKey] ?? '' }}">{{ $termNumber }}</th>@empty<th class="w-10 border border-gray-200 px-2 py-2 text-center">-</th>@endforelse@endforeach</tr>@endif</thead><tbody>
        @php $currentSexGroup = null; $sexGroupCounts = []; @endphp
        @forelse($rows as $row)
            @php
                $student = $row['student'];
                $sexGroup = strtolower((string) $student?->sex) === 'female' ? 'Female' : (strtolower((string) $student?->sex) === 'male' ? 'Male' : 'Unspecified');
                $sexGroupCounts[$sexGroup] = ($sexGroupCounts[$sexGroup] ?? 0) + 1;
                $subjectFinals = [];
            @endphp
            @if($currentSexGroup !== $sexGroup)@php $currentSexGroup = $sexGroup; @endphp<tr><td colspan="{{ 3 + ($assignments->count() * $subjectColumnSpan) }}" class="border border-gray-200 bg-[#296374]/10 px-3 py-2 text-left text-xs font-extrabold uppercase text-[#296374]">{{ $sexGroup }}</td></tr>@endif
            <tr class="hover:bg-gray-50"><td class="border border-gray-200 px-2 py-2 text-center font-semibold">{{ $sexGroupCounts[$sexGroup] }}</td><td class="border border-gray-200 px-3 py-2 font-semibold uppercase">{{ $row['name'] }}</td>@foreach($assignments as $assignment)@php $subjectGrades = $row['subjects'][$assignment->assignment_ID] ?? null; $periodValues = $periodKeys->map(fn ($periodKey) => $subjectGrades ? $subjectGrades['periods']->get($periodKey)?->numeric_grade : null)->filter(fn ($value) => $value !== null && $value !== ''); $finalGrade = $periodValues->isNotEmpty() ? round($periodValues->avg()) : null; if ($finalGrade !== null) $subjectFinals[] = $finalGrade; @endphp@forelse($periodKeys as $periodKey)@php $grade = $subjectGrades ? $subjectGrades['periods']->get($periodKey)?->numeric_grade : null; @endphp<td class="border border-gray-200 px-2 py-2 text-center font-semibold">{{ $grade === null || $grade === '' ? '' : number_format((float) $grade, 0) }}</td>@empty<td class="border border-gray-200 px-2 py-2"></td>@endforelse<td class="border border-gray-200 bg-gray-50 px-2 py-2 text-center font-bold">{{ $finalGrade === null ? '' : number_format((float) $finalGrade, 0) }}</td>@endforeach<td class="border border-gray-200 bg-gray-50 px-2 py-2 text-center font-extrabold">
                @php
                    $generalAverage = count($subjectFinals) ? round(collect($subjectFinals)->avg()) : null;
                @endphp
                {{ $generalAverage === null ? '' : number_format((float) $generalAverage, 0) }}
            </td></tr>
        @empty
            <tr><td colspan="{{ 3 + ($assignments->count() * $subjectColumnSpan) }}" class="border border-gray-200 px-4 py-12 text-center text-gray-500">No students found for this advisory section.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
