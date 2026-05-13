@extends('users.admin.layout')

@section('title', 'Teacher Assignments')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Teacher Subject Assignments</h1>
                <p class="text-gray-600 text-sm md:text-base">Assign teachers to subjects per section.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" id="open-single" class="px-4 py-2 rounded-lg text-sm font-bold text-white shadow-md" style="background-color: #296374;">New Assignment</button>
                <button type="button" id="open-bulk" class="px-4 py-2 rounded-lg text-sm font-bold text-white shadow-md bg-emerald-600 hover:bg-emerald-700">Bulk Assign</button>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-100 border border-green-400 text-green-700 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-100 border border-red-400 text-red-700 px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-white/50">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="px-3 py-2 border rounded text-sm" placeholder="Section/subject/teacher">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Grade</label>
                    <select name="grade_level" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        @foreach($gradeLevels as $level)
                            <option value="{{ $level['value'] }}" {{ request('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cluster</label>
                    <select name="cluster_ID" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" {{ request('cluster_ID') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">School Year</label>
                    <select name="SY_ID" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" {{ request('SY_ID') == $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Per Page</label>
                    <select name="per_page" class="px-3 py-2 border rounded text-sm">
                        @foreach([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 rounded text-white text-sm" style="background-color: #296374;">Apply</button>
                <a href="{{ route('admin.teacher-assignments.index') }}" class="px-4 py-2 rounded text-sm bg-gray-200 text-gray-700">Clear</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Section</th>
                        <th class="px-6 py-4">Subject</th>
                        <th class="px-6 py-4">Teacher</th>
                        <th class="px-6 py-4">Semester</th>
                        <th class="px-6 py-4">School Year</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assignments as $assignment)
                        @php
                            $section = $assignment->section;
                            $subject = $assignment->curriculumSubject?->subject;
                            $teacher = $assignment->staff;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <p class="font-semibold">{{ $section?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ strtoupper(str_replace('grade_', 'Grade ', $section?->grade_level ?? '')) }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <p class="font-semibold">{{ $subject?->code ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $subject?->title ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <p class="font-semibold">{{ $teacher?->last_name }}, {{ $teacher?->first_name }}</p>
                                <p class="text-xs text-gray-500">{{ $teacher?->user?->username ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $assignment->curriculumSubject?->semester ? ucfirst($assignment->curriculumSubject->semester) : 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $section?->academicYear?->school_year ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="{{ route('admin.teacher-assignments.update', $assignment) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="staff_ID" class="border rounded px-2 py-1 text-xs">
                                        @foreach($teachers as $teacherOption)
                                            <option value="{{ $teacherOption->staff_id }}" {{ $assignment->staff_ID == $teacherOption->staff_id ? 'selected' : '' }}>
                                                {{ $teacherOption->last_name }}, {{ $teacherOption->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="px-3 py-1 text-xs font-semibold text-white rounded" style="background-color:#296374;">Update</button>
                                </form>
                                <form method="POST" action="{{ route('admin.teacher-assignments.delete', $assignment) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 text-xs font-semibold text-white rounded bg-rose-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $assignments->links() }}
        </div>
    </div>
</div>

<div id="modal-backdrop" class="fixed top-0 left-0 w-screen h-screen bg-black/50 hidden items-start justify-center overflow-y-auto p-4 md:p-6" style="z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[calc(100vh-2rem)] overflow-hidden mt-2 md:mt-6">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 id="modal-title" class="text-lg font-bold text-gray-700">New Assignment</h3>
            <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-600">?</button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(100vh-8rem)]">
            <form method="POST" action="{{ route('admin.teacher-assignments.store') }}" id="single-assignment-form" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Section</label>
                    <select name="section_ID" id="section_ID" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required>
                        <option value="">Select section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_ID }}" data-curriculum="{{ $section->curriculum_ID }}" data-grade="{{ $section->grade_level }}" data-cluster="{{ $section->cluster_ID ?? '' }}">
                                {{ $section->name }} | {{ strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) }} | {{ $section->academicYear?->school_year ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                    <select name="curr_subj_ID" id="curr_subj_ID" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required>
                        <option value="">Select subject</option>
                        @foreach($curriculumSubjects as $currSubj)
                            <option value="{{ $currSubj->curr_subj_ID }}" data-curriculum="{{ $currSubj->curriculum_ID }}" data-grade="{{ $currSubj->grade_level }}" data-cluster="{{ $currSubj->cluster_ID }}">
                                {{ strtoupper($currSubj->grade_level) }} {{ $currSubj->semester ? '- ' . ucfirst($currSubj->semester) . ' Sem' : '' }} | {{ $currSubj->subject?->code }} - {{ $currSubj->subject?->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Subjects filter automatically based on section.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Teacher</label>
                    <select name="staff_ID" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required>
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}">
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg px-4 py-2 text-sm font-bold text-white shadow-md" style="background-color: #296374;">Assign</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.teacher-assignments.bulk') }}" id="bulk-assignment-form" class="hidden mt-6 space-y-6">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Teacher</label>
                    <select name="staff_ID" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required>
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->staff_id }}">
                                {{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="block text-xs font-semibold text-gray-500">Grade Levels</label>
                        <span id="bulk-grade-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5">
                        @foreach($gradeLevels as $level)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2.5 text-xs md:text-sm text-gray-700">
                                <input type="checkbox" name="grade_levels[]" value="{{ $level['value'] }}" class="bulk-grade-checkbox h-4 w-4 accent-[#296374]">
                                <span class="font-medium">{{ $level['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="block text-xs font-semibold text-gray-500">Sections</label>
                        <span id="bulk-section-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div id="bulk-sections-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                        Select one or more grade levels to show matching sections.
                    </div>
                    <div id="bulk-sections-list" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2 max-h-72 overflow-y-auto pr-1">
                        @foreach($sections as $section)
                            <label
                                class="bulk-section-option hidden flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-2.5 py-2 text-xs text-gray-700"
                                data-grade="{{ $section->grade_level }}"
                                data-curriculum="{{ $section->curriculum_ID }}"
                                data-cluster="{{ $section->cluster_ID ?? '' }}"
                                >
                                <input
                                    type="checkbox"
                                    name="section_ids[]"
                                    value="{{ $section->section_ID }}"
                                    class="bulk-section-checkbox mt-0.5 h-4 w-4 accent-[#296374]"
                                    data-grade="{{ $section->grade_level }}"
                                    data-curriculum="{{ $section->curriculum_ID }}"
                                    data-cluster="{{ $section->cluster_ID ?? '' }}"
                                >
                                <span>
                                    <span class="block font-semibold text-[13px] leading-tight">{{ $section->name }}</span>
                                    <span class="block text-[11px] text-gray-500 leading-tight mt-1">
                                        {{ strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) }}
                                        @if($section->cluster)
                                            | {{ $section->cluster->name }}
                                        @endif
                                    </span>
                                    <span class="block text-[11px] text-gray-500 leading-tight">{{ $section->academicYear?->school_year ?? 'N/A' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="block text-xs font-semibold text-gray-500">Subjects</label>
                        <span id="bulk-subject-count" class="text-xs text-gray-400">0 selected</span>
                    </div>
                    <div id="bulk-subjects-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                        Select one or more sections to show compatible subjects.
                    </div>
                    <div id="bulk-subjects-list" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2 max-h-80 overflow-y-auto pr-1">
                        @foreach($curriculumSubjects as $currSubj)
                            <label
                                class="bulk-subject-option hidden flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-2.5 py-2 text-xs text-gray-700"
                                data-grade="{{ $currSubj->grade_level }}"
                                data-curriculum="{{ $currSubj->curriculum_ID }}"
                                data-cluster="{{ $currSubj->cluster_ID ?? '' }}"
                                >
                                <input
                                    type="checkbox"
                                    name="curr_subj_ids[]"
                                    value="{{ $currSubj->curr_subj_ID }}"
                                    class="bulk-subject-checkbox mt-0.5 h-4 w-4 accent-[#296374]"
                                    data-grade="{{ $currSubj->grade_level }}"
                                    data-curriculum="{{ $currSubj->curriculum_ID }}"
                                    data-cluster="{{ $currSubj->cluster_ID ?? '' }}"
                                >
                                <span>
                                    <span class="block font-semibold text-[13px] leading-tight">{{ $currSubj->subject?->code }} - {{ $currSubj->subject?->title }}</span>
                                    <span class="block text-[11px] text-gray-500 leading-tight mt-1">
                                        {{ strtoupper(str_replace('grade_', 'Grade ', $currSubj->grade_level)) }}
                                        @if($currSubj->semester)
                                            | {{ ucfirst($currSubj->semester) }} Semester
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 pt-4">
                    <button type="submit" class="rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-md bg-emerald-600 hover:bg-emerald-700">Confirm Bulk Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var openSingle = document.getElementById('open-single');
        var openBulk = document.getElementById('open-bulk');
        var modal = document.getElementById('modal-backdrop');
        var closeBtn = document.getElementById('close-modal');
        var singleForm = document.getElementById('single-assignment-form');
        var bulkForm = document.getElementById('bulk-assignment-form');
        var title = document.getElementById('modal-title');

        function openModal(mode) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            if (mode === 'bulk') {
                title.textContent = 'Bulk Assignment';
                singleForm.classList.add('hidden');
                bulkForm.classList.remove('hidden');
            } else {
                title.textContent = 'New Assignment';
                bulkForm.classList.add('hidden');
                singleForm.classList.remove('hidden');
            }
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        openSingle.addEventListener('click', function () { openModal('single'); });
        openBulk.addEventListener('click', function () { openModal('bulk'); });
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        var sectionSelect = document.getElementById('section_ID');
        var subjectSelect = document.getElementById('curr_subj_ID');

        function filterSubjects() {
            if (!sectionSelect || !subjectSelect) return;
            var selected = sectionSelect.options[sectionSelect.selectedIndex];
            var curriculumId = selected ? selected.getAttribute('data-curriculum') : '';
            var gradeLevel = selected ? selected.getAttribute('data-grade') : '';
            var clusterId = selected ? selected.getAttribute('data-cluster') : '';

            Array.from(subjectSelect.options).forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false;
                    return;
                }
                var matchesCurriculum = opt.getAttribute('data-curriculum') === curriculumId;
                var matchesGrade = opt.getAttribute('data-grade') === gradeLevel;
                var matchesCluster = true;
                if (clusterId && clusterId !== '') {
                    matchesCluster = opt.getAttribute('data-cluster') === clusterId;
                }
                opt.hidden = !(matchesCurriculum && matchesGrade && matchesCluster);
            });

            subjectSelect.value = '';
        }

        if (sectionSelect) {
            sectionSelect.addEventListener('change', filterSubjects);
            filterSubjects();
        }

        var bulkGradeCheckboxes = Array.from(document.querySelectorAll('.bulk-grade-checkbox'));
        var bulkSectionOptions = Array.from(document.querySelectorAll('.bulk-section-option'));
        var bulkSectionCheckboxes = Array.from(document.querySelectorAll('.bulk-section-checkbox'));
        var bulkSubjectOptions = Array.from(document.querySelectorAll('.bulk-subject-option'));
        var bulkSubjectCheckboxes = Array.from(document.querySelectorAll('.bulk-subject-checkbox'));
        var bulkSectionsEmpty = document.getElementById('bulk-sections-empty');
        var bulkSectionsList = document.getElementById('bulk-sections-list');
        var bulkSubjectsEmpty = document.getElementById('bulk-subjects-empty');
        var bulkSubjectsList = document.getElementById('bulk-subjects-list');
        var bulkGradeCount = document.getElementById('bulk-grade-count');
        var bulkSectionCount = document.getElementById('bulk-section-count');
        var bulkSubjectCount = document.getElementById('bulk-subject-count');

        function updateBulkCounts() {
            if (bulkGradeCount) {
                bulkGradeCount.textContent = bulkGradeCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
            }
            if (bulkSectionCount) {
                bulkSectionCount.textContent = bulkSectionCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
            }
            if (bulkSubjectCount) {
                bulkSubjectCount.textContent = bulkSubjectCheckboxes.filter(function (input) { return input.checked; }).length + ' selected';
            }
        }

        function selectedSectionMeta() {
            return bulkSectionCheckboxes
                .filter(function (input) {
                    return input.checked && !input.closest('.bulk-section-option').classList.contains('hidden');
                })
                .map(function (input) {
                    return {
                        grade: input.getAttribute('data-grade') || '',
                        curriculum: input.getAttribute('data-curriculum') || '',
                        cluster: input.getAttribute('data-cluster') || ''
                    };
                });
        }

        function subjectMatchesAnySection(subjectOption, sections) {
            var subjectGrade = subjectOption.getAttribute('data-grade') || '';
            var subjectCurriculum = subjectOption.getAttribute('data-curriculum') || '';
            var subjectCluster = subjectOption.getAttribute('data-cluster') || '';

            return sections.some(function (section) {
                var matchesGrade = section.grade === subjectGrade;
                var matchesCurriculum = section.curriculum === subjectCurriculum;
                var matchesCluster = true;

                if (section.cluster) {
                    matchesCluster = section.cluster === subjectCluster;
                }

                return matchesGrade && matchesCurriculum && matchesCluster;
            });
        }

        function updateBulkSections() {
            var selectedGrades = bulkGradeCheckboxes
                .filter(function (input) { return input.checked; })
                .map(function (input) { return input.value; });

            var visibleSections = 0;
            bulkSectionOptions.forEach(function (option) {
                var shouldShow = selectedGrades.indexOf(option.getAttribute('data-grade')) !== -1;
                option.classList.toggle('hidden', !shouldShow);

                var input = option.querySelector('.bulk-section-checkbox');
                if (!shouldShow && input) {
                    input.checked = false;
                }

                if (shouldShow) {
                    visibleSections++;
                }
            });

            if (bulkSectionsEmpty) {
                bulkSectionsEmpty.classList.toggle('hidden', visibleSections > 0);
            }
            if (bulkSectionsList) {
                bulkSectionsList.classList.toggle('hidden', visibleSections === 0);
            }
        }

        function updateBulkSubjects() {
            var sections = selectedSectionMeta();
            var visibleSubjects = 0;

            bulkSubjectOptions.forEach(function (option) {
                var shouldShow = sections.length > 0 && subjectMatchesAnySection(option, sections);
                option.classList.toggle('hidden', !shouldShow);

                var input = option.querySelector('.bulk-subject-checkbox');
                if (!shouldShow && input) {
                    input.checked = false;
                }

                if (shouldShow) {
                    visibleSubjects++;
                }
            });

            if (bulkSubjectsEmpty) {
                bulkSubjectsEmpty.classList.toggle('hidden', visibleSubjects > 0);
            }
            if (bulkSubjectsList) {
                bulkSubjectsList.classList.toggle('hidden', visibleSubjects === 0);
            }
        }

        function refreshBulkFilters() {
            updateBulkSections();
            updateBulkSubjects();
            updateBulkCounts();
        }

        bulkGradeCheckboxes.forEach(function (input) {
            input.addEventListener('change', refreshBulkFilters);
        });

        bulkSectionCheckboxes.forEach(function (input) {
            input.addEventListener('change', function () {
                updateBulkSubjects();
                updateBulkCounts();
            });
        });

        bulkSubjectCheckboxes.forEach(function (input) {
            input.addEventListener('change', updateBulkCounts);
        });

        refreshBulkFilters();
    })();
</script>
@endsection
