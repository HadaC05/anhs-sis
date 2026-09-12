@extends('users.registrar.layout')

@section('title', 'Teacher Assignments')

@section('content')
<div class="mb-8 flex items-center gap-3">
    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-lg" style="background: linear-gradient(135deg, #296374 0%, #1e4d5c 100%);">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m18 0v-2a4 4 0 00-3-3.87m-2-12.13a4 4 0 010 7.75M10 11a4 4 0 100-8 4 4 0 000 8z" /></svg>
    </div>
    <div><h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">Teacher Assignments</h1><p class="mt-1 text-sm text-gray-500">View every teacher's advisory class and assigned subjects.</p></div>
</div>

<form method="GET" action="{{ route('registrar.teacher-assignments') }}" class="mb-6">
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Search teacher or employee no." class="h-10 w-56 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
        <select name="SY_ID" class="h-10 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-[#296374]">
            <option value="">All school years</option>
            @foreach ($academicYears as $year)<option value="{{ $year->SY_ID }}" @selected((string) $filters['schoolYearId'] === (string) $year->SY_ID)>{{ $year->school_year }}</option>@endforeach
        </select>
        <select name="per_page" class="h-10 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-[#296374]">
            @foreach ([10, 20, 50] as $size)<option value="{{ $size }}" @selected($filters['perPage'] === $size)>{{ $size }} per page</option>@endforeach
        </select>
        <button class="h-10 rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
        <a href="{{ route('registrar.teacher-assignments') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
    </div>
</form>

<div class="space-y-4">
@forelse ($teachers as $teacher)
    @php($teacherName = trim($teacher->last_name . ', ' . $teacher->first_name . ' ' . $teacher->middle_name))
    <details class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none flex-col justify-between gap-3 bg-slate-50 px-5 py-4 transition hover:bg-[#296374]/5 sm:flex-row sm:items-center [&::-webkit-details-marker]:hidden">
            <div><h2 class="font-bold text-gray-800">{{ $teacherName ?: $teacher->username }}</h2><p class="text-xs text-gray-500">{{ $teacher->employee_no ? 'Employee no. '.$teacher->employee_no : 'No employee number' }}</p></div>
            <div class="flex items-center gap-2 text-xs font-semibold"><span class="rounded-full bg-orange-100 px-3 py-1 text-orange-700">{{ $teacher->sections->count() }} advisory</span><span class="rounded-full bg-[#296374]/10 px-3 py-1 text-[#296374]">{{ $teacher->teacherSubjectAssignments->count() }} subjects</span><svg class="ml-1 h-5 w-5 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg></div>
        </summary>
        <div class="grid gap-5 border-t border-gray-100 p-5 lg:grid-cols-2">
            <section>
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Advisory</h3>
                <div class="space-y-2">
                    @forelse ($teacher->sections as $section)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-orange-100 bg-orange-50/50 px-3 py-2 text-sm text-gray-700">
                            <p class="min-w-0 truncate"><span class="font-semibold">{{ $section->name }}</span><span class="text-gray-500"> &middot; {{ $section->gradeLevel?->grade_label ?? 'No grade level' }} &middot; {{ $section->academicYear?->school_year ?? 'No school year' }}</span></p>
                            <a href="{{ route('registrar.classes.students', $section) }}" class="shrink-0 rounded-md border border-[#296374]/25 bg-white px-2.5 py-1 text-xs font-bold text-[#296374] transition hover:bg-[#296374] hover:text-white">View class</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No advisory class assigned.</p>
                    @endforelse
                </div>
            </section>
            <section>
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Subjects</h3>
                <div class="space-y-2">
                    @forelse ($teacher->teacherSubjectAssignments as $assignment)
                        @php($subject = $assignment->curriculumSubject?->subject)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-[#296374]/10 bg-[#296374]/5 px-3 py-2 text-sm text-gray-700">
                            <p class="min-w-0 truncate"><span class="font-semibold">{{ $subject ? $subject->code.' - '.$subject->title : 'Subject unavailable' }}</span><span class="text-gray-500"> &middot; {{ $assignment->section?->name ?? 'No section' }} &middot; {{ $assignment->section?->academicYear?->school_year ?? 'No school year' }}</span></p>
                            @if ($assignment->section)<a href="{{ route('registrar.classes.students', $assignment->section) }}" class="shrink-0 rounded-md border border-[#296374]/25 bg-white px-2.5 py-1 text-xs font-bold text-[#296374] transition hover:bg-[#296374] hover:text-white">View class</a>@endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No subjects assigned.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </details>
@empty
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center text-gray-500 shadow-sm">No teachers found.</div>
@endforelse
</div>

@if ($teachers->hasPages())<div class="mt-6 rounded-lg border border-gray-200 bg-white px-6 py-4 shadow-sm">{{ $teachers->links() }}</div>@endif
@endsection
