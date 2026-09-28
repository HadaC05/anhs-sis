<style>
    .grade-record-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); align-items: end; gap: .75rem; }
    .grade-record-filters > div { min-width: 0; }
    .grade-record-filters select { width: 100%; min-width: 0; }
    .grade-filter-actions > * { min-height: 2.5rem; justify-content: center; }
    @media (max-width: 639px) {
        .grade-record-filters { grid-template-columns: minmax(0, 1fr); }
        .grade-filter-actions > * { flex: 1; }
    }
</style>
<form method="GET" action="{{ $filterRoute }}" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <div class="grade-record-filters">
        <div class="grade-filter-search">
            <label for="grade-approval-search" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Search</label>
            <input id="grade-approval-search" type="search" name="search" value="{{ $filterValues['search'] }}" placeholder="Section, subject, or teacher" class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
        </div>
        <div>
            <label for="grade-approval-level" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Grade level</label>
            <select id="grade-approval-level" name="grade_level" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All grade levels</option>
                @foreach($gradeLevels as $level)
                    <option data-grade-id="{{ $level->grade_ID }}" data-senior-high="{{ in_array($level->grade_label, ['Grade 11', 'Grade 12'], true) ? 'true' : 'false' }}" value="{{ $principalFilters ? $level->grade_ID : $level->value }}" @selected((string) $filterValues['grade_level'] === (string) ($principalFilters ? $level->grade_ID : $level->value))>{{ $level->grade_label }}</option>
                @endforeach
            </select>
        </div>
        <div data-semester-field @class(['hidden' => !$showSemesterFilter])>
            <label for="semester-filter" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Semester</label>
            <select id="semester-filter" name="semester" @disabled(!$showSemesterFilter) class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700">
                <option value="">All semesters</option>
                <option value="first" @selected($filters['semester'] === 'first')>First Semester</option>
                <option value="second" @selected($filters['semester'] === 'second')>Second Semester</option>
            </select>
        </div>
        <div>
            <label for="grade-release-term" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Term</label>
            <select id="grade-release-term" name="term_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700">
                <option value="current" @selected($filters['term_id'] === 'current')>Current JHS / SHS terms</option>
                <option value="" @selected(!$filters['term_id'])>All terms</option>
                @foreach($allTerms as $term)
                    <option data-school-level="{{ $term->school_level }}" value="{{ $term->term_ID }}" @selected((string) $filters['term_id'] === (string) $term->term_ID)>{{ $term->label }} ({{ $term->school_level === 'senior_high' ? 'SHS' : 'JHS' }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="grade-approval-subject" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Subject</label>
            <select id="grade-approval-subject" name="subject_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All subjects</option>
                @foreach($subjects as $subject)
                    <option data-grade-ids="{{ $subject->curriculumSubjects->pluck('curriculumGradeLevel.grade_ID')->filter()->unique()->implode(',') }}" value="{{ $subject->subject_ID }}" @selected((string) $filterValues['subject_id'] === (string) $subject->subject_ID)>{{ $subject->code }} - {{ $subject->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="grade-approval-year" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">School year</label>
            <select id="grade-approval-year" name="academic_year_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All school years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->SY_ID }}" @selected((string) $filterValues['academic_year_id'] === (string) $academicYear->SY_ID)>{{ $academicYear->school_year }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="grade-approval-status" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Status</label>
            <select id="grade-approval-status" name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filterValues['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grade-filter-actions flex gap-2">
            <button type="submit" class="inline-flex items-center rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">Filter</button>
            <a href="{{ $filterRoute }}" class="inline-flex items-center rounded-lg border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-600 hover:bg-gray-50">Clear</a>
        </div>
    </div>
</form>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const grade = document.getElementById('grade-approval-level');
        const subject = document.getElementById('grade-approval-subject');
        const semester = document.getElementById('semester-filter');
        const term = document.getElementById('grade-release-term');
        const subjectOptions = Array.from(subject.options);
        const termOptions = Array.from(term.options);
        const defaults = @json($termDefaults);
        const activeSemester = @json($activeSemester);
        let previousSchoolLevel;
        const syncFilters = (changed = false) => {
            const gradeId = grade.selectedOptions[0]?.dataset.gradeId;
            const seniorHigh = grade.selectedOptions[0]?.dataset.seniorHigh === 'true';
            const schoolLevel = gradeId ? (seniorHigh ? 'senior_high' : 'junior_high') : '';
            const selectedSubject = subject.value;
            const subjects = subjectOptions.filter(option => !option.value || !gradeId || option.dataset.gradeIds.split(',').includes(gradeId));
            subject.replaceChildren(...subjects);
            subject.value = subjects.some(option => option.value === selectedSubject) ? selectedSubject : '';

            semester.closest('[data-semester-field]').classList.toggle('hidden', !seniorHigh);
            semester.disabled = !seniorHigh;
            if (!seniorHigh) semester.value = '';
            else if (changed && previousSchoolLevel !== schoolLevel) semester.value = activeSemester;

            const selectedTerm = term.value;
            const terms = termOptions.filter(option => option.value === 'current' ? !schoolLevel : (!option.value || !schoolLevel || option.dataset.schoolLevel === schoolLevel));
            term.replaceChildren(...terms);
            const useDefault = (changed && previousSchoolLevel !== schoolLevel) || !terms.some(option => option.value === selectedTerm);
            term.value = useDefault ? (schoolLevel ? String(defaults[schoolLevel] ?? '') : 'current') : selectedTerm;
            previousSchoolLevel = schoolLevel;
        };
        grade.addEventListener('change', () => syncFilters(true));
        window.addEventListener('pageshow', () => syncFilters());
        syncFilters();
    });
</script>
