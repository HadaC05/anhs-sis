@extends('users.teacher.layout')

@section('title', 'Sections')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">My Sections & Subjects</h1>
    <p class="text-gray-600 text-sm md:text-base">View subjects grouped by section for faster grading.</p>
</div>

<div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 overflow-hidden">
    <div class="px-8 py-5 border-b border-white/20 bg-white/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="font-bold text-[#296374] text-lg">Assigned Sections</h3>
            <p class="text-xs text-gray-600 mt-1">Open a section and choose a subject to input grades.</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Section or subject" class="w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
            </div>
            <div>
                <label for="grade_level" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Grade</label>
                <select name="grade_level" id="grade_level" class="w-36 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                    <option value="">All</option>
                    @foreach ($gradeLevels ?? [] as $level)
                        <option value="{{ $level['value'] }}" {{ ($filters['grade_level'] ?? '') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cluster_ID" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Cluster</label>
                <select name="cluster_ID" id="cluster_ID" class="w-56 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                    <option value="">All</option>
                    @foreach ($clusters ?? [] as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" {{ ($filters['cluster_ID'] ?? '') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="SY_ID" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">School Year</label>
                <select name="SY_ID" id="SY_ID" class="w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                    <option value="">All</option>
                    @foreach ($academicYears ?? [] as $year)
                        <option value="{{ $year->SY_ID }}" {{ ($filters['SY_ID'] ?? '') == $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="per_page" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Per Page</label>
                <select name="per_page" id="per_page" class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none bg-white shadow-sm">
                    @foreach ([10, 20, 50] as $size)
                        <option value="{{ $size }}" {{ ($filters['per_page'] ?? 10) == $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold text-white uppercase tracking-wide shadow-md transition-all hover:-translate-y-0.5" style="background-color: #296374;">
                Apply
            </button>
            <a href="{{ route('teacher.sections.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-gray-600 border border-gray-300 bg-white hover:text-[#296374]">
                Reset
            </a>
        </form>
    </div>

    @php
        $grouped = $assignments instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $assignments->getCollection()->groupBy('section_ID')
            : $assignments->groupBy('section_ID');
    @endphp

    <div class="divide-y divide-white/20">
        @forelse($grouped as $sectionId => $items)
            @php
                $section = $items->first()?->section;
                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section?->grade_level));
                $clusterName = $section?->cluster?->name;
            @endphp
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800">{{ $section?->name ?? 'Section' }}</h4>
                        <p class="text-xs text-gray-500">{{ $gradeLabel }} {{ $clusterName ? '• ' . $clusterName : '' }} • {{ $section?->academicYear?->school_year ?? 'N/A' }}</p>
                    </div>
                    <div class="text-xs text-gray-600">Students: {{ $section?->active_enrollments_count ?? 0 }} | Capacity: {{ $section?->capacity ?? 0 }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($items as $assignment)
                        @php
                            $subject = $assignment->curriculumSubject?->subject;
                            $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
                        @endphp
                        <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm">
                            <div class="text-sm font-semibold text-gray-800">{{ $subjectLabel }}</div>
                            @if($assignment->curriculumSubject?->semester)
                                <div class="text-xs text-gray-500 mt-1">Semester: {{ ucfirst($assignment->curriculumSubject->semester) }}</div>
                            @endif
                            <a href="{{ route('teacher.sections.show', $assignment) }}" class="inline-flex items-center mt-3 px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wide text-white shadow-md" style="background-color: #296374;">
                                Input Grades
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="px-8 py-8 text-center text-gray-500">No subjects assigned yet.</div>
        @endforelse
    </div>

    <div class="px-8 py-5 border-t border-white/20 bg-white/50">
        {{ $assignments->links() }}
    </div>
</div>
@endsection
