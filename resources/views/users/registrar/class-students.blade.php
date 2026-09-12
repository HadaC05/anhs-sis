@extends('users.registrar.layout')

@section('title', 'Class Roster')

@section('content')
<a href="{{ route('registrar.teacher-assignments') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-[#296374] hover:underline">
    <span aria-hidden="true">←</span> Back to teacher assignments
</a>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Class roster</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">{{ $section->name }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $section->gradeLevel?->grade_label ?? 'No grade level' }} &middot; {{ $section->academicYear?->school_year ?? 'No school year' }}@if ($section->adviser) &middot; Adviser: {{ trim($section->adviser->last_name.', '.$section->adviser->first_name) }}@endif</p>
</div>

<form method="GET" class="mb-5 flex flex-wrap gap-2">
    <input name="search" value="{{ $search }}" placeholder="Search student or LRN" class="h-10 w-56 rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
    <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none focus:border-[#296374]">@foreach ([10, 20, 50] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} per page</option>@endforeach</select>
    <button class="h-10 rounded-lg px-4 text-sm font-bold text-white" style="background-color: #296374;">Apply</button>
</form>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-gray-500"><tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">LRN</th><th class="px-5 py-3">Enrollment status</th><th class="px-5 py-3 text-right">Record</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse ($students as $enrollment)@php($student = $enrollment->student)@php($application = $student?->application)@php($name = trim(($application?->last_name ?? $student?->last_name ?? '').', '.($application?->first_name ?? $student?->first_name ?? '')))@php($name = trim($name, ', '))<tr><td class="px-5 py-4 font-semibold text-gray-800">{{ $name ?: 'Unnamed student' }}</td><td class="px-5 py-4 font-mono text-xs text-gray-600">{{ $student?->lrn ?? '-' }}</td><td class="px-5 py-4"><span class="rounded-full bg-[#296374]/10 px-2.5 py-1 text-xs font-semibold text-[#296374]">{{ $enrollment->enrollment_status_label ?: 'Active' }}</span></td><td class="px-5 py-4 text-right">@if ($student)<a href="{{ route('registrar.students.show', $student) }}" class="text-xs font-bold text-[#296374] hover:underline">View record</a>@endif</td></tr>@empty<tr><td colspan="4" class="px-5 py-14 text-center text-gray-500">No active students are assigned to this class.</td></tr>@endforelse</tbody></table></div>
    @if ($students->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $students->links() }}</div>@endif
</div>
@endsection
