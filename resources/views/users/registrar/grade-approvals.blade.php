@extends('users.registrar.layout')

@section('title', 'Grade Approvals')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold tracking-tight text-gray-700 md:text-2xl">Grade Approvals</h1>
</div>

@if (session('status'))
    <div id="gradeApprovalsToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
        <span>{{ session('status') }}</span><button type="button" class="ml-auto" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

@if (session('error'))
    <div id="gradeApprovalsErrorToast" role="alert" aria-live="assertive" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl">
        <span>{{ session('error') }}</span><button type="button" class="ml-auto" data-dismiss-grade-toast aria-label="Close notification">&times;</button>
    </div>
@endif

<form method="GET" action="{{ route('registrar.grade-approvals') }}" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex items-end gap-3 overflow-x-auto pb-1">
        <div class="w-60 shrink-0">
            <label for="grade-approval-search" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Search</label>
            <input id="grade-approval-search" type="search" name="search" value="{{ request('search') }}" placeholder="Section, subject, or teacher" class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
        </div>
        <div class="min-w-[170px]">
            <label for="grade-approval-subject" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Subject</label>
            <select id="grade-approval-subject" name="subject_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All subjects</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->subject_ID }}" @selected((string) request('subject_id') === (string) $subject->subject_ID)>{{ $subject->code }} - {{ $subject->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label for="grade-approval-level" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Grade level</label>
            <select id="grade-approval-level" name="grade_level" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All grade levels</option>
                @foreach($gradeLevels as $level)
                    <option value="{{ $level->value }}" @selected(request('grade_level') === $level->value)>{{ $level->grade_label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label for="grade-approval-year" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">School year</label>
            <select id="grade-approval-year" name="academic_year_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All school years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->SY_ID }}" @selected((string) request('academic_year_id') === (string) $academicYear->SY_ID)>{{ $academicYear->school_year }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[150px]">
            <label for="grade-approval-status" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Status</label>
            <select id="grade-approval-status" name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All statuses</option>
                <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            </select>
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="submit" class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Filter</button>
            <a href="{{ route('registrar.grade-approvals') }}" class="inline-flex items-center rounded-lg border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-600 hover:bg-gray-50">Clear</a>
        </div>
    </div>
</form>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[950px] w-full text-left">
            <thead><tr class="border-b border-[#296374] bg-[#296374] text-xs font-bold uppercase tracking-wider text-white">
                <th class="px-6 py-4">Section</th><th class="px-6 py-4">Grade Level</th><th class="px-6 py-4">Subject</th><th class="px-6 py-4">Teacher</th><th class="px-6 py-4">Grades</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assignments as $assignment)
                    @php
                        $section = $assignment->section;
                        $subject = $assignment->curriculumSubject?->subject;
                        $teacher = $assignment->staff;
                        $grades = $assignment->grades ?? collect();
                        $rowStatus = $grades->contains('status', 'submitted') ? 'submitted' : 'approved';
                        $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : '—';
                        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : '—';
                        $reviewUrl = $rowStatus === 'approved' ? route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => 'approved']) : route('registrar.grade-approvals.show', $assignment);
                    @endphp
                    <tr class="transition-colors odd:bg-white even:bg-slate-50/70 hover:bg-[#eaf3f5]">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $section?->gradeLevel?->grade_label ?? ($section?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : '—') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $teacherName }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grades->count() }}</td>
                        <td class="px-6 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $rowStatus === 'submitted' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ ucfirst($rowStatus) }}</span></td>
                        <td class="px-6 py-4 text-right"><a href="{{ $reviewUrl }}" class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">{{ $rowStatus === 'submitted' ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No grade submissions match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.querySelectorAll('[data-dismiss-grade-toast]').forEach((button) => button.addEventListener('click', () => button.closest('[role]')?.remove()));
    window.setTimeout(() => document.getElementById('gradeApprovalsToast')?.remove(), 4000);
    window.setTimeout(() => document.getElementById('gradeApprovalsErrorToast')?.remove(), 6000);
</script>
@endsection
