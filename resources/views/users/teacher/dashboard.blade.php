@extends('users.teacher.layout')

@section('title', 'Dashboard')
@section('content-width', 'max-w-none')

@section('content')
<div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Teacher Dashboard</h1>
    </div>
    <span class="self-start rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#296374]">SY {{ $activeYear?->school_year ?? 'Not set' }}</span>
</div>

@if (! $activeYear)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">No active school year is set. Current assignments will appear once an administrator activates a school year.</div>
@endif

<div class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ([
        ['Subject assignments', $assignments->count(), 'Subjects you teach'],
        ['Advisory classes', $advisorySections->count(), 'Classes you advise'],
        ['Active learners', $totalStudents, 'Across your assigned sections'],
        ['Returned grades', $gradeTotals['rejected'], 'Grade records to review'],
    ] as [$label, $value, $note])
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs font-semibold text-slate-600">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold {{ $label === 'Returned grades' && $value > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ number_format($value) }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ $note }}</p>
        </div>
    @endforeach
</div>

<section id="my-advisory" class="mb-8 scroll-mt-6" aria-labelledby="advisory-heading">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div><h2 id="advisory-heading" class="text-lg font-bold text-slate-900">Advisory quick access</h2><p class="mt-1 text-sm text-slate-500">Your class records and everyday advisory tools.</p></div>
        <a href="{{ route('teacher.advisory.index', ['SY_ID' => $activeYear?->SY_ID]) }}" class="text-sm font-semibold text-[#296374] hover:underline">All advisory classes &rarr;</a>
    </div>
    <div class="grid grid-cols-1 gap-4">
        @forelse ($advisorySections as $section)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div><h3 class="text-lg font-bold text-slate-900">{{ $section->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ str_replace('_', ' ', ucfirst($section->grade_level)) }} &middot; {{ $section->room ?: 'Room not set' }}</p></div>
                    <span class="rounded-lg bg-[#296374]/10 px-3 py-2 text-xs font-bold text-[#296374]">{{ $section->active_enrollments_count }} learners</span>
                </div>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ([
                        ['Class list', 'View and import learners', 'teacher.advisory.class-list.index'],
                        ['Attendance', 'Manage SF2 and SF9 attendance', 'teacher.advisory.attendance'],
                        ['Observed values', 'Record learner behavior', 'teacher.advisory.observed-values'],
                        ['Grades & school forms', 'Review grades, SF9 and SF10', 'teacher.advisory.show'],
                        ['Promotion', 'Evaluate learner eligibility', 'teacher.advisory.promotions.index'],
                    ] as [$label, $description, $route])
                        <a href="{{ route($route, $section) }}" class="rounded-lg border border-slate-200 p-3 transition hover:border-[#296374]/40 hover:bg-slate-50"><span class="flex items-center justify-between gap-2 text-sm font-semibold text-[#296374]">{{ $label }} <span aria-hidden="true">&rarr;</span></span><span class="mt-1 block text-xs text-slate-500">{{ $description }}</span></a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center"><p class="font-semibold text-slate-700">No advisory class assigned</p><p class="mt-2 text-sm text-slate-500">Advisory tools appear here when you are assigned as a class adviser for the active school year.</p></div>
        @endforelse
    </div>
</section>

<div class="grid min-w-0 grid-cols-1 items-start gap-6 xl:grid-cols-3">
    <section id="subject-grades" class="min-w-0 scroll-mt-6 rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2" aria-labelledby="subjects-heading">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 p-5">
            <div>
                <h2 id="subjects-heading" class="text-lg font-bold text-slate-900">My subject grades</h2>
                <p class="mt-1 text-sm text-slate-500">Up to five subjects with no grades recorded.</p>
            </div>
            <a href="{{ route('teacher.sections.index', ['SY_ID' => $activeYear?->SY_ID]) }}" class="text-sm font-semibold text-[#296374] hover:underline">All subjects &rarr;</a>
        </div>
        @php
            $displayedAssignments = $ungradedAssignments->take(5);
            $showSemester = $displayedAssignments->contains(fn ($assignment) => \App\Models\GradingTerm::isSeniorHighSection($assignment->section));
        @endphp
        <div class="overflow-x-auto">
            <table class="w-full {{ $showSemester ? 'min-w-[760px]' : 'min-w-[640px]' }} text-left text-sm" aria-labelledby="subjects-heading">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Section</th>
                        <th scope="col" class="px-5 py-3">Subject</th>
                        <th scope="col" class="px-5 py-3">Code</th>
                        @if ($showSemester)
                            <th scope="col" class="px-5 py-3">Semester</th>
                        @endif
                        <th scope="col" class="px-5 py-3">Grade status</th>
                        <th scope="col" class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($displayedAssignments as $assignment)
                        <tr>
                            <td class="px-5 py-4 text-slate-600">{{ $assignment->section?->name ?? 'Section unavailable' }}</td>
                            <th scope="row" class="min-w-48 px-5 py-4 font-bold text-slate-900">{{ $assignment->curriculumSubject?->subject?->title ?? 'Subject unavailable' }}</th>
                            <td class="px-5 py-4 text-slate-600">{{ $assignment->curriculumSubject?->subject?->code ?: '—' }}</td>
                            @if ($showSemester)
                                <td class="px-5 py-4 text-slate-600">{{ \App\Models\GradingTerm::isSeniorHighSection($assignment->section) && $assignment->curriculumSubject?->semester ? ucfirst($assignment->curriculumSubject->semester) : '—' }}</td>
                            @endif
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($assignment->gradeStatusCounts() as $status => $count)
                                        <span class="rounded-md px-2 py-1 text-xs font-semibold {{ match ($status) { 'rejected' => 'bg-rose-50 text-rose-700', 'draft' => 'bg-amber-50 text-amber-800', 'released' => 'bg-emerald-50 text-emerald-700', default => 'bg-slate-100 text-slate-600' } }}">{{ $count }} {{ $status === 'rejected' ? 'returned' : $status }}</span>
                                    @empty
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">No grades recorded</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('teacher.sections.show', $assignment) }}" aria-label="Open grades for {{ $assignment->curriculumSubject?->subject?->title }} in {{ $assignment->section?->name }}" class="inline-flex whitespace-nowrap rounded-lg border border-[#296374]/30 px-4 py-2.5 text-sm font-bold text-[#296374] transition hover:bg-[#296374]/5">{{ $assignment->rejected_grades_count > 0 ? 'Review grades' : 'Open grades' }} &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showSemester ? 6 : 5 }}" class="px-6 py-12 text-center">
                            <p class="font-semibold text-slate-700">{{ $assignments->isEmpty() ? 'No subject assignments yet' : 'No ungraded subjects' }}</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Subjects without grade records appear here. Use All subjects to access your complete teaching load.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="next-heading">
            <h2 id="next-heading" class="text-lg font-bold text-slate-900">Grading checklist</h2>
            <p class="mt-1 text-xs text-slate-500">Subject counts across all terms this school year. Released grades count individual grade records.</p>
            <dl class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach (['Review returned grades' => $returnedAssignments->count(), 'Continue draft grades' => $draftAssignments->count(), 'Start ungraded subjects' => $ungradedAssignments->count(), 'Released grades' => $gradeTotals['released']] as $label => $count)
                    <div class="flex items-center justify-between gap-3 py-3"><dt class="text-slate-600">{{ $label }}</dt><dd class="rounded-md bg-slate-100 px-2.5 py-1 font-bold text-slate-900">{{ $count }}</dd></div>
                @endforeach
            </dl>
            <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">{{ number_format($gradeTotals['submitted']) }} submitted grade records awaiting review. Open a subject to check individual terms and submission requirements.</div>
        </section>
    </aside>
</div>


@endsection
