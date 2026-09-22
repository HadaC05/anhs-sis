@extends('users.principal.layout')

@section('title', 'Grade Releases')

@section('content')
<div class="mb-6"><h1 class="text-xl md:text-2xl font-bold text-gray-700 tracking-tight">Grade Releases</h1></div>

@if (session('status')) <div class="mb-6 rounded-lg border border-green-400 bg-green-100 px-4 py-3 text-green-700">{{ session('status') }}</div> @endif
@if ($errors->any()) <div class="mb-6 rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-red-700">{{ $errors->first() }}</div> @endif

<form method="GET" action="{{ route('principal.grade-releases') }}" class="mb-6">
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm">
        <select name="subject_id" class="h-10 min-w-[180px] flex-1 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700"><option value="">All subjects</option>@foreach($subjects as $subject)<option value="{{ $subject->subject_ID }}" @selected((string) $filters['subject_id'] === (string) $subject->subject_ID)>{{ $subject->code }} — {{ $subject->title }}</option>@endforeach</select>
        <select name="academic_year_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700"><option value="">All school years</option>@foreach($academicYears as $academicYear)<option value="{{ $academicYear->SY_ID }}" @selected((string) $filters['academic_year_id'] === (string) $academicYear->SY_ID)>{{ $academicYear->school_year }}</option>@endforeach</select>
        <select name="grade_level" id="grade-level-filter" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700"><option value="">All grade levels</option>@foreach($gradeLevels as $gradeLevel)<option value="{{ $gradeLevel->grade_ID }}" data-senior-high="{{ in_array($gradeLevel->grade_label, ['Grade 11', 'Grade 12'], true) ? 'true' : 'false' }}" @selected((string) $filters['grade_level'] === (string) $gradeLevel->grade_ID)>{{ $gradeLevel->grade_label }}</option>@endforeach</select>
        <select name="term_id" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700"><option value="">All terms</option>@foreach($terms as $term)<option value="{{ $term->term_ID }}" @selected((string) $filters['term_id'] === (string) $term->term_ID)>{{ $term->label }}</option>@endforeach</select>
        <select name="semester" id="semester-filter" class="{{ $showSemesterFilter ? '' : 'hidden' }} h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700" {{ $showSemesterFilter ? '' : 'disabled' }}><option value="">All semesters</option><option value="first" @selected($filters['semester'] === 'first')>First Semester</option><option value="second" @selected($filters['semester'] === 'second')>Second Semester</option></select>
        <div class="relative min-w-[180px] flex-1"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z" /></svg><input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search section, subject, or teacher" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm text-gray-700"></div>
        <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button><a href="{{ route('principal.grade-releases') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600">Reset</a>
    </div>
</form>

<form action="{{ route('principal.grade-releases.bulk-release') }}" method="POST">@csrf
    <div class="overflow-hidden rounded-lg border border-white/20 bg-white/95 shadow-xl">
        <div class="flex flex-col gap-3 border-b border-white/20 bg-white/40 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"><h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Grade Records</h2>@if($assignments->contains(fn ($assignment) => $assignment->grades->contains(fn ($grade) => $grade->status === 'approved')))<button type="submit" id="release-selected-grades" disabled class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md disabled:cursor-not-allowed disabled:opacity-50" style="background-color: #296374;">Release Selected Grade Records</button>@endif</div>
        <div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="border-b border-white/20 bg-white/30 text-xs font-bold uppercase tracking-wider text-[#296374]"><th class="w-12 px-6 py-4"><input type="checkbox" id="grade-release-check-all" class="rounded border-gray-300" aria-label="Select all releasable grade records"></th><th class="px-6 py-4">Section</th><th class="px-6 py-4">Subject</th><th class="px-6 py-4">Teacher</th><th class="px-6 py-4">Records</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-white/20">@forelse($assignments as $assignment)
                @php($grades = $assignment->grades) @php($approvedCount = $grades->where('status', 'approved')->count()) @php($releasedCount = $grades->where('status', 'released')->count()) @php($canRelease = $approvedCount > 0) @php($subject = $assignment->curriculumSubject?->subject) @php($subjectLabel = $subject ? ($subject->code.' - '.$subject->title) : 'N/A')
                <tr class="bg-white/10 transition-all hover:bg-white/30"><td class="px-6 py-4">@if($canRelease)<input type="checkbox" name="assignment_ids[]" value="{{ $assignment->assignment_ID }}" class="grade-release-check rounded border-gray-300" aria-label="Select {{ $subjectLabel }}">@endif</td><td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $assignment->section?->name ?? 'N/A' }}</td><td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td><td class="px-6 py-4 text-sm text-gray-700">{{ $assignment->staff ? $assignment->staff->last_name.', '.$assignment->staff->first_name : 'N/A' }}</td><td class="px-6 py-4 text-sm text-gray-700">{{ $grades->count() }}</td><td class="px-6 py-4 text-sm">@if($approvedCount)<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ $approvedCount }} awaiting release</span>@endif @if($releasedCount)<span class="ml-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ $releasedCount }} released</span>@endif</td><td class="px-6 py-4 text-right"><a href="{{ route('principal.grade-releases.show', ['assignment' => $assignment, 'status' => $canRelease ? 'approved' : 'released']) }}" class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">View</a></td></tr>
            @empty <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No grade records match the selected filters.</td></tr> @endforelse</tbody></table></div>
    </div>
</form>

<script>
(() => {
    const gradeLevel = document.getElementById('grade-level-filter');
    const semester = document.getElementById('semester-filter');
    const selectAll = document.getElementById('grade-release-check-all');
    const releaseButton = document.getElementById('release-selected-grades');
    const checkboxes = Array.from(document.querySelectorAll('.grade-release-check'));

    gradeLevel.addEventListener('change', () => {
        const seniorHigh = gradeLevel.options[gradeLevel.selectedIndex]?.dataset.seniorHigh === 'true';
        semester.classList.toggle('hidden', !seniorHigh);
        semester.disabled = !seniorHigh;
        if (!seniorHigh) semester.value = '';
    });

    const syncSelection = () => {
        const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
        selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        selectAll.disabled = checkboxes.length === 0;
        if (releaseButton) releaseButton.disabled = selectedCount === 0;
    };

    selectAll.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
        syncSelection();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncSelection));
    syncSelection();
})();
</script>
@endsection
