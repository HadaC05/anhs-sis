@extends('users.registrar.layout')

@section('title', 'Grade Approvals')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Grade Approvals</h1>
    <p class="text-gray-600 text-sm md:text-base">Review and approve submitted grades before releasing them to students.</p>
</div>

@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-6 py-4 border-b border-white/20 bg-white/40">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Pending Submissions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                    <th class="px-6 py-4">Section</th>
                    <th class="px-6 py-4">Subject</th>
                    <th class="px-6 py-4">Teacher</th>
                    <th class="px-6 py-4">Submitted Grades</th>
                    <th class="px-6 py-4">Last Submitted</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/20">
                @forelse($assignments as $assignment)
                    @php
                        $section = $assignment->section;
                        $subject = $assignment->curriculumSubject?->subject;
                        $teacher = $assignment->staff;
                        $submittedGrades = $assignment->grades ?? collect();
                        $latestSubmitted = $submittedGrades->max('submitted_at');
                        $teacherName = $teacher ? ($teacher->last_name . ', ' . $teacher->first_name) : '—';
                        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : '—';
                    @endphp
                    <tr class="hover:bg-white/30 transition-all bg-white/10">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $section?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $subjectLabel }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $teacherName }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $submittedGrades->count() }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $latestSubmitted ? $latestSubmitted->format('M d, Y h:i A') : '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <form action="{{ route('registrar.grade-approvals.approve', $assignment) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Approve</button>
                                </form>
                                <form action="{{ route('registrar.grade-approvals.reject', $assignment) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md bg-rose-500 hover:bg-rose-600">Return</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No submitted grades awaiting approval.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
