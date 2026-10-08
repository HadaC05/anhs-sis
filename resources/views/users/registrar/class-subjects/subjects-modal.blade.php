<div class="overflow-x-auto" data-section-progress="{{ $section->section_ID }}" data-submitted="{{ $sectionProgress[$section->section_ID]['submitted'] ?? 0 }}" data-expected="{{ $sectionProgress[$section->section_ID]['expected'] ?? 0 }}">
    <p class="px-4 py-3 text-xs text-gray-500">Submission status and counts use the active term and semester. Submitted counts include approved and released grades.</p>
    <table class="w-full min-w-[760px] text-left text-sm">
        <caption class="sr-only">Subjects and grade submission status for {{ $section->name }}</caption>
        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
                @foreach (['Subject', 'Teacher', 'Grade submission', 'Total students', 'Grades submitted', 'Action'] as $heading)
                <th scope="col" class="px-4 py-3">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($section->teacherSubjectAssignments as $assignment)
            @php
                $subject = $assignment->subject;
                $teacher = $assignment->staff;
                $activeTerm = $assignment->active_term_summary;
                $studentsCount = (int) ($assignment->subject_students_count ?? 0);
                $submittedCount = (int) ($assignment->active_submitted_count ?? 0);
                $submissionLabel = ! $activeTerm ? 'No active term' : ($submittedCount > 0
                    ? ($submittedCount >= $studentsCount ? 'Submitted' : 'Partially submitted')
                    : (($activeTerm['draft'] ?? 0) > 0 ? 'Draft' : 'Ungraded'));
                $activeRecordsId = $activeTerm ? 'records-'.$assignment->assignment_ID.'-'.$activeTerm['key'] : null;
            @endphp
            <tr class="align-top">
                <th scope="row" class="px-4 py-4 font-semibold text-gray-800">{{ $subject ? $subject->code.' - '.$subject->title : 'Subject unavailable' }}</th>
                <td class="px-4 py-4">{{ $teacher ? trim($teacher->last_name.', '.$teacher->first_name) : 'Unassigned' }}</td>
                <td class="px-4 py-4">
                    @include('users.registrar.partials.grade-progress-badge', ['label' => $submissionLabel])
                </td>
                <td class="px-4 py-4 font-semibold tabular-nums">{{ number_format($studentsCount) }}</td>
                <td class="px-4 py-4 font-semibold tabular-nums text-[#296374]">{{ number_format($submittedCount) }}</td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-toggle-terms="terms-{{ $assignment->assignment_ID }}" aria-controls="terms-{{ $assignment->assignment_ID }}" aria-expanded="false" class="whitespace-nowrap rounded-lg bg-[#296374] px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#1e4d5c]">Edit term status</button>
                        @if ($activeTerm && $submittedCount > 0)
                        <button type="button" data-view-grades="{{ route('registrar.class-subjects.grade-records', ['assignment' => $assignment, 'grading_period' => $activeTerm['key']]) }}" data-records-target="{{ $activeRecordsId }}" data-open-terms="terms-{{ $assignment->assignment_ID }}" aria-controls="{{ $activeRecordsId }}" class="whitespace-nowrap rounded-lg border border-[#296374] px-3 py-2 text-xs font-semibold text-[#296374] hover:bg-slate-100">View grades</button>
                        @endif
                    </div>
                </td>
            </tr>
            <tr id="terms-{{ $assignment->assignment_ID }}" hidden>
                <td colspan="6" class="bg-slate-50 px-4 py-4">
                    <p class="mb-3 text-sm font-semibold">Term status &mdash; {{ $subject?->title ?? 'Subject' }}</p>
                    <p class="mb-3 text-xs text-gray-500">Unlock a term to allow teacher corrections and resubmission. Locked grade records return to draft.</p>
                    <table class="w-full text-left text-xs">
                        <thead><tr>
                            @foreach (['Term', 'Editing status', 'Grade submission', 'Action'] as $heading)
                            <th scope="col" class="px-3 py-2">{{ $heading }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($allTermsByAssignment[$assignment->assignment_ID] as $term)
                            <tr class="align-top">
                                <th scope="row" class="px-3 py-3 font-semibold">
                                    {{ $term['label'] }}
                                    @if ($term['is_current_term'])<span class="mt-1 block font-normal text-emerald-700">Current school term</span>@endif
                                </th>
                                <td class="px-3 py-3">{{ ! ($term['is_available'] ?? true) ? 'Not yet available' : ($term['is_registrar_unlocked'] ? 'Registrar unlocked' : ($term['is_school_locked'] ? 'School term closed' : ($term['can_unlock'] ? 'Grades locked' : 'Open for editing'))) }}</td>
                                <td class="px-3 py-3">
                                    @if ($term['total'] === 0)
                                    No grades yet
                                    @else
                                    @foreach (['draft', 'submitted', 'approved', 'released'] as $status)
                                    @if ($term[$status] > 0)<p>{{ $term[$status] }} {{ $status }}</p>@endif
                                    @endforeach
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($term['submitted'] + $term['approved'] + $term['released'] > 0)
                                    <button type="button" data-view-grades="{{ route('registrar.class-subjects.grade-records', ['assignment' => $assignment, 'grading_period' => $term['key']]) }}" data-records-target="records-{{ $assignment->assignment_ID }}-{{ $term['key'] }}" aria-controls="records-{{ $assignment->assignment_ID }}-{{ $term['key'] }}" class="mb-3 rounded-md border border-[#296374] bg-white px-3 py-2 font-semibold text-[#296374] hover:bg-slate-100">View grades</button>
                                    @endif
                                    @if (($term['is_available'] ?? true) && ($term['can_unlock'] || $term['is_registrar_unlocked']))
                                    <form method="POST" action="{{ route('registrar.class-subjects.unlock-term', $assignment) }}" data-unlock-term data-term-label="{{ $term['label'] }}">
                                        @csrf
                                        <input type="hidden" name="grading_period" value="{{ $term['key'] }}">
                                        <label class="block text-gray-600">Approval note (optional)
                                            <input type="text" name="notes" maxlength="500" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2 py-2">
                                        </label>
                                        <button type="submit" class="mt-2 rounded-md bg-[#296374] px-3 py-2 font-semibold text-white disabled:opacity-50">{{ $term['is_registrar_unlocked'] ? 'Re-unlock term' : 'Unlock term' }}</button>
                                    </form>
                                    @else
                                    <span class="text-gray-500">{{ ($term['is_available'] ?? true) ? 'Open for editing' : 'Not yet available' }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr id="records-{{ $assignment->assignment_ID }}-{{ $term['key'] }}" hidden>
                                <td colspan="4" class="px-3 py-3" data-records-content aria-live="polite"></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-3 py-4 text-gray-500">No grading terms are available for this subject.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No subjects assigned for this section.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
