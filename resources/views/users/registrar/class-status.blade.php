@extends('users.registrar.layout')

@section('title', 'Class Subject Status')

@section('content')
<a href="{{ route('registrar.teacher-assignments') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-[#296374] hover:underline">
    <span aria-hidden="true">←</span> Back to teacher assignments
</a>

@php
    $assignments = $section->teacherSubjectAssignments;
    $labels = $assignments->map->gradeProgressLabel()->unique()->values();
    $overallStatus = $assignments->isEmpty()
        ? 'No subjects'
        : ($labels->count() === 1 ? $labels->first() : 'In progress');
    $gradedCount = $assignments->filter(fn ($assignment): bool => (int) ($assignment->grades_count ?? 0) > 0)->count();
@endphp

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Class subject status</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-800">{{ $section->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $section->gradeLevel?->grade_label ?? 'No grade level' }} &middot; {{ $section->academicYear?->school_year ?? 'No school year' }}@if ($section->adviser) &middot; Adviser: {{ trim($section->adviser->last_name.', '.$section->adviser->first_name) }}@endif</p>
        </div>
        @include('users.registrar.partials.grade-progress-badge', ['label' => $overallStatus])
    </div>
    <p class="mt-3 text-xs text-gray-500">{{ $assignments->count() }} {{ Str::plural('subject', $assignments->count()) }} assigned &middot; {{ $gradedCount }} with grades encoded</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-5 py-3">Subject</th>
                    <th class="px-5 py-3">Teacher</th>
                    <th class="px-5 py-3">Semester</th>
                    <th class="px-5 py-3">Grade status</th>
                    <th class="px-5 py-3 text-right">Terms</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($assignments as $assignment)
                    @php
                        $subject = $assignment->curriculumSubject?->subject;
                        $teacher = $assignment->staff;
                        $teacherName = $teacher ? trim($teacher->last_name.', '.$teacher->first_name) : 'Unassigned';
                        $statusCounts = $assignment->gradeStatusCounts();
                        $semester = $assignment->curriculumSubject?->semester;
                    @endphp
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800">{{ $subject ? $subject->code.' - '.$subject->title : 'Subject unavailable' }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                @if ((int) ($assignment->grades_count ?? 0) === 0)
                                    No grades yet
                                @else
                                    {{ $assignment->grades_count }} {{ Str::plural('record', $assignment->grades_count) }}
                                    @foreach ($statusCounts as $status => $count)
                                        &middot; {{ $count }} {{ \App\Models\GradeStatus::nameFor($status) }}
                                    @endforeach
                                @endif
                            </p>
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $teacherName }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $semester ? ucfirst($semester).' semester' : 'Full year' }}</td>
                        <td class="px-5 py-4">@include('users.registrar.partials.grade-progress-badge', ['label' => $assignment->gradeProgressLabel()])</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('registrar.class-subjects.show', $assignment) }}" class="text-xs font-bold text-[#296374] hover:underline">View terms</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center text-gray-500">No subject classes are assigned to this advisory class.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
