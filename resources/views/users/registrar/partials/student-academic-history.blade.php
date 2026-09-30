<div id="detail-history" class="registrar-record-section" data-record-section>
    <div class="p-6">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Academic History</h2>

        @forelse($student->enrollments as $enrollment)
            @php
                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                $grades = $enrollment->grades ?? collect();
            @endphp
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $enrollment->academicYear?->school_year ?? 'School Year' }} • {{ $gradeLabel }}</p>
                        <p class="text-xs text-gray-500">Section: {{ $enrollment->section?->name ?? '—' }} | Cluster: {{ $enrollment->cluster?->name ?? '—' }} | Semester: {{ $enrollment->semester ? ucfirst($enrollment->semester) : '—' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('registrar.students.sf9', ['student' => $student, 'enrollment' => $enrollment]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg bg-[#296374] px-3 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm hover:opacity-90">
                            View / Print SF9
                        </a>
                        @if($loop->first)
                        <a href="{{ route('registrar.students.sf10', $student) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg border border-[#296374]/30 bg-white px-3 py-2 text-xs font-bold uppercase tracking-wide text-[#296374] shadow-sm hover:bg-[#296374]/5">
                            View / Print SF10
                        </a>
                        @endif
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-white" style="background-color: #296374;">
                            {{ $enrollment->enrollment_status_label }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                                <th class="px-4 py-2">Subject</th>
                                <th class="px-4 py-2">Period</th>
                                <th class="px-4 py-2">Grade</th>
                                <th class="px-4 py-2">Remarks</th>
                                <th class="px-4 py-2">Teacher</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/20">
                            @forelse($grades as $grade)
                                @php
                                    $subject = $grade->assignment?->curriculumSubject?->subject?->title ?? '—';
                                    $teacher = $grade->assignment?->staff;
                                    $teacherName = $teacher
                                        ? ($teacher->last_name . ', ' . $teacher->first_name)
                                        : '—';
                                @endphp
                                <tr class="hover:bg-white/30 transition-all bg-white/10">
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $subject }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ strtoupper($grade->grading_period ?? '') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $grade->numeric_grade ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $grade->remarks ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $teacherName }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $grade->status_label ?: 'Draft' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-sm text-gray-500">No grades recorded for this enrollment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No enrollment history found.</p>
        @endforelse
    </div>
</div>

