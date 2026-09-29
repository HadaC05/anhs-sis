<style>
    .class-subject-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); align-items: end; gap: .75rem; }
    .class-subject-filters > div { min-width: 0; }
    @media (max-width: 639px) { .class-subject-filters { grid-template-columns: minmax(0, 1fr); } }
</style>
<form method="GET" action="{{ route('registrar.class-subjects.index') }}" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <div class="class-subject-filters">
        <div>
            <label for="search" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Search</label>
            <input type="search" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Section, subject, or teacher" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
        </div>
        <div>
            <label for="grade_level" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Grade level</label>
            <select id="grade_level" name="grade_level" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All grade levels</option>
                @foreach ($gradeLevels as $level)
                <option value="{{ $level['value'] }}" @selected($filters['grade_level'] === $level['value'])>{{ $level['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div data-shs-filter @if (!$periods['showSemesterFilter']) hidden @endif>
            <label for="semester" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Semester</label>
            <select id="semester" name="semester" @disabled(!$periods['showSemesterFilter']) class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All semesters</option>
                <option value="first" @selected($filters['semester'] === 'first')>First Semester</option>
                <option value="second" @selected($filters['semester'] === 'second')>Second Semester</option>
            </select>
        </div>
        <div>
            <label for="term_id" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Term</label>
            <select id="term_id" name="term_id" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="current" @selected($filters['term_id'] === 'current')>Current JHS / SHS terms</option>
                <option value="" @selected(!$filters['term_id'])>All terms</option>
                @foreach ($periods['allTerms'] as $term)
                <option value="{{ $term->term_ID }}" data-school-level="{{ $term->school_level }}" @selected((string) $filters['term_id'] === (string) $term->term_ID)>{{ $term->label }} ({{ $term->school_level === 'senior_high' ? 'SHS' : 'JHS' }})</option>
                @endforeach
            </select>
        </div>
        <div data-shs-filter @if (!$periods['showSemesterFilter']) hidden @endif>
            <label for="cluster_ID" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Cluster</label>
            <select id="cluster_ID" name="cluster_ID" @disabled(!$periods['showSemesterFilter']) class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All clusters</option>
                @foreach ($clusters as $cluster)
                <option value="{{ $cluster->cluster_ID }}" @selected((string) $filters['cluster_ID'] === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="SY_ID" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">School year</label>
            <select id="SY_ID" name="SY_ID" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All school years</option>
                @foreach ($academicYears as $year)
                <option value="{{ $year->SY_ID }}" @selected((string) $filters['SY_ID'] === (string) $year->SY_ID)>{{ $year->school_year }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#296374] px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-md">Filter</button>
        <a href="{{ route('registrar.class-subjects.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-600 hover:bg-gray-50">Clear</a>
        <div class="ml-auto flex items-center gap-2">
            <label for="per_page" class="text-xs font-semibold text-gray-500">Rows per page</label>
            <select id="per_page" name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700">
                @foreach ([10, 20, 50] as $size)
                <option value="{{ $size }}" @selected($filters['per_page'] == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
    </div>
</form>
<script>
(() => {
    const grade = document.getElementById('grade_level');
    const semester = document.getElementById('semester');
    const cluster = document.getElementById('cluster_ID');
    const term = document.getElementById('term_id');
    const options = Array.from(term.options);
    const defaults = @json($periods['termDefaults']);
    const activeSemester = @json($periods['activeSemester']);
    let previousLevel;
    function sync(changed = false) {
        const senior = ['grade_11', 'grade_12'].includes(grade.value);
        const level = grade.value ? (senior ? 'senior_high' : 'junior_high') : '';
        document.querySelectorAll('[data-shs-filter]').forEach(field => field.hidden = !senior);
        semester.disabled = cluster.disabled = !senior;
        if (!senior) cluster.value = '';
        if (changed && senior && previousLevel !== level) semester.value = activeSemester;
        const selected = term.value;
        const visible = options.filter(option => option.value === 'current' ? !level : (!option.value || !level || option.dataset.schoolLevel === level));
        term.replaceChildren(...visible);
        term.value = ((changed && level !== previousLevel) || !visible.some(option => option.value === selected))
            ? (level ? String(defaults[level] ?? '') : 'current') : selected;
        previousLevel = level;
    }
    grade.addEventListener('change', () => sync(true));
    window.addEventListener('pageshow', () => sync());
    sync();
})();
</script>
