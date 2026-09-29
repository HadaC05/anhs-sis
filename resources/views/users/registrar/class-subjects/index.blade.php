@extends('users.registrar.layout')

@section('title', 'Class Subjects')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">Class Subjects</h1>
</div>

@if (session('status'))
<div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
    {{ session('status') }}
</div>
@endif

@php
$sectionList = $sections instanceof \Illuminate\Pagination\LengthAwarePaginator ? $sections->getCollection() : $sections;
@endphp

@include('users.registrar.class-subjects.filters')

@if ($sectionList->isNotEmpty())
<p class="mb-3 text-xs text-gray-500">A subject counts as submitted when all active students have submitted, approved, or released grades for the selected terms. Total subjects reflects the selected semester.</p>
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] text-left text-sm">
            <caption class="sr-only">Class sections and assigned subjects</caption>
            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-3">Section</th>
                    <th scope="col" class="px-4 py-3">School year</th>
                    <th scope="col" class="px-4 py-3">Adviser</th>
                    @if ($showPeriodColumns)
                    <th scope="col" class="px-4 py-3">Term</th>
                    <th scope="col" class="px-4 py-3">Semester</th>
                    @endif
                    <th scope="col" class="px-4 py-3 text-right">Subjects submitted</th>
                    <th scope="col" class="px-4 py-3 text-right">Total subjects</th>
                    <th scope="col" class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($sectionList as $section)
                @php
                $progress = $sectionProgress[$section->section_ID] ?? ['submitted' => 0, 'expected' => 0];
                $gradeLabel = ucwords(str_replace('_', ' ', $section->grade_level));
                $adviserName = $section->adviser ? trim($section->adviser->last_name.', '.$section->adviser->first_name) : 'Unassigned';
                @endphp
                <tr class="align-top transition hover:bg-gray-50/60">
                    <th scope="row" class="px-4 py-4 font-normal">
                        <p class="font-semibold text-gray-800">{{ $section->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $gradeLabel }}</p>
                        @if ($section->cluster?->name)
                        <p class="mt-1 text-xs text-gray-500">{{ $section->cluster->name }}</p>
                        @endif
                    </th>
                    <td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $section->academicYear?->school_year ?? 'N/A' }}</td>
                    <td class="px-4 py-4 text-gray-600">{{ $adviserName }}</td>
                    @if ($showPeriodColumns)
                    @php
                        $seniorHigh = \App\Models\GradingTerm::isSeniorHighSection($section);
                        $schoolLevel = $seniorHigh ? 'senior_high' : 'junior_high';
                        $rowTermId = $filters['term_id'] === 'current' ? ($periods['termDefaults'][$schoolLevel] ?? null) : $filters['term_id'];
                        $rowTerm = $periods['allTerms']->firstWhere('term_ID', $rowTermId);
                    @endphp
                    <td class="px-4 py-4 text-gray-600">{{ $rowTerm?->label ?? ($filters['term_id'] ? 'No active term' : 'All terms') }}</td>
                    <td class="px-4 py-4 text-gray-600">{{ $seniorHigh ? ($filters['semester'] ? ucfirst($filters['semester']).' Semester' : 'All semesters') : 'Full year' }}</td>
                    @endif
                    <td data-submitted-grades="{{ $section->section_ID }}" class="px-4 py-4 text-right font-semibold tabular-nums text-[#296374]">{{ number_format($progress['submitted']) }}</td>
                    <td data-expected-grades="{{ $section->section_ID }}" class="px-4 py-4 text-right font-semibold tabular-nums text-gray-700">{{ number_format($progress['expected']) }}</td>
                    <td class="px-4 py-4">
                        <button type="button" data-subjects-template="section-subjects-{{ $section->section_ID }}" data-subjects-url="{{ route('registrar.classes.subjects', ['section' => $section, 'grade_level' => $filters['grade_level'], 'term_id' => $filters['term_id'] ?? '', 'semester' => $filters['semester'] ?? '']) }}" data-section-name="{{ $section->name }}" class="whitespace-nowrap rounded-lg border border-[#296374]/25 px-3 py-2 text-xs font-semibold text-[#296374] hover:bg-[#296374] hover:text-white">
                            View subjects
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($sections instanceof \Illuminate\Pagination\LengthAwarePaginator && $sections->hasPages())
<div class="mt-6 rounded-lg border border-gray-200/80 bg-white/90 px-6 py-4 shadow-sm">
    {{ $sections->links() }}
</div>
@endif
@else
<div class="rounded-lg border border-gray-200/80 bg-white/95 px-6 py-16 text-center shadow-md">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
    </div>
    <p class="mt-4 font-medium text-gray-600">No classes found</p>
    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back once sections are set up for the school year.</p>
</div>
@endif
@foreach ($sectionList as $section)
<template id="section-subjects-{{ $section->section_ID }}">
    @include('users.registrar.class-subjects.subjects-modal', ['section' => $section])
</template>
@endforeach
@include('users.registrar.class-subjects.modal-shell')
@endsection
