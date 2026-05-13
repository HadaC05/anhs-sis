@extends('users.admin.layout')

@section('title', 'Section Configuration')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Section Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Manage sections by cluster, adviser, academic year, and curriculum.</p>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
            <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Sections</h2>
            <button type="button" onclick="openSectionModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Section</button>
        </div>

        <div class="p-6 border-b border-gray-200 bg-gray-50/50">
            <form method="GET" action="{{ route('admin.section-config.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[220px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Section or room" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="w-44">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                    <select name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" {{ (int) request('cluster_ID') === (int) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Grade</label>
                    <select name="grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($gradeLevels as $level)
                            <option value="{{ $level['value'] }}" {{ request('grade_level') === $level['value'] ? 'selected' : '' }}>{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Academic Year</label>
                    <select name="SY_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" {{ (int) request('SY_ID') === (int) $year->SY_ID ? 'selected' : '' }}>{{ $year->school_year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Curriculum</label>
                    <select name="curriculum_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        @foreach($curriculums as $curriculum)
                            <option value="{{ $curriculum->curriculum_ID }}" {{ (int) request('curriculum_ID') === (int) $curriculum->curriculum_ID ? 'selected' : '' }}>{{ $curriculum->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                    <select name="per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        @foreach([5, 10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                @if(request()->hasAny(['search', 'cluster_ID', 'grade_level', 'SY_ID', 'curriculum_ID', 'per_page']))
                    <a href="{{ route('admin.section-config.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Cluster</th>
                        <th class="px-6 py-4">Grade</th>
                        <th class="px-6 py-4">Adviser</th>
                        <th class="px-6 py-4">Academic Year</th>
                        <th class="px-6 py-4">Curriculum</th>
                        <th class="px-6 py-4">Room</th>
                        <th class="px-6 py-4">Capacity</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sections as $section)
                        @php
                            $adviserName = optional($section->adviser)->last_name
                                ? optional($section->adviser)->last_name . ', ' . optional($section->adviser)->first_name
                                : 'N/A';
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $section->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($section->cluster)->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600 uppercase">{{ str_replace('_', ' ', $section->grade_level) }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $adviserName }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($section->academicYear)->school_year ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($section->curriculum)->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $section->room ?: '-' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $section->capacity }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" onclick='openSectionModal(@json($section))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                    <form action="{{ route('admin.section-config.delete', $section) }}" method="POST" class="inline" onsubmit="return confirm('Delete this section?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">No sections yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sections->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $sections->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<div id="sectionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="sectionModalTitle" class="text-xl font-bold text-gray-800">Add Section</h3>
        </div>
        <form id="sectionForm" class="p-6 space-y-4" action="{{ route('admin.section-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="section_method" name="_method" value="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Section Name</label>
                    <input id="section_name" name="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                    <select id="section_cluster_id" name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Select cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}">{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                    <p id="section_cluster_hint" class="mt-1 text-xs text-gray-500">Required for senior high school sections only.</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Grade Level</label>
                    <select id="section_grade_level" name="grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        @foreach($gradeLevels as $level)
                            <option value="{{ $level['value'] }}">{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Adviser</label>
                    <select id="section_staff_id" name="staff_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">None</option>
                        @foreach($staffs as $staff)
                            @php
                                $name = $staff->last_name . ', ' . $staff->first_name;
                            @endphp
                            <option value="{{ $staff->staff_id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Academic Year</label>
                    <select id="section_sy_id" name="SY_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select school year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->SY_ID }}">{{ $year->school_year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Curriculum</label>
                    <select id="section_curriculum_id" name="curriculum_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select curriculum</option>
                        @foreach($curriculums as $curriculum)
                            <option value="{{ $curriculum->curriculum_ID }}">{{ $curriculum->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Room</label>
                    <input id="section_room" name="room" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Capacity</label>
                    <input id="section_capacity" name="capacity" type="number" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeSectionModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Section</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSectionModal(section = null) {
        const form = document.getElementById('sectionForm');
        const method = document.getElementById('section_method');
        const updateRouteTemplate = '{{ route('admin.section-config.update', ['section' => '__SECTION__']) }}';

        if (section) {
            document.getElementById('sectionModalTitle').textContent = 'Edit Section';
            form.action = updateRouteTemplate.replace('__SECTION__', section.section_ID);
            method.value = 'PUT';
            document.getElementById('section_name').value = section.name || '';
            document.getElementById('section_cluster_id').value = section.cluster_ID || '';
            document.getElementById('section_grade_level').value = section.grade_level || '';
            document.getElementById('section_staff_id').value = section.staff_ID || '';
            document.getElementById('section_sy_id').value = section.SY_ID || '';
            document.getElementById('section_curriculum_id').value = section.curriculum_ID || '';
            document.getElementById('section_room').value = section.room || '';
            document.getElementById('section_capacity').value = section.capacity || '';
        } else {
            document.getElementById('sectionModalTitle').textContent = 'Add Section';
            form.action = '{{ route('admin.section-config.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('sectionModal').classList.remove('hidden');
        document.getElementById('sectionModal').classList.add('flex');
        toggleSectionClusterRequirement();
    }

    function closeSectionModal() {
        document.getElementById('sectionModal').classList.add('hidden');
        document.getElementById('sectionModal').classList.remove('flex');
    }

    document.getElementById('sectionModal').addEventListener('click', function (e) {
        if (e.target === this) closeSectionModal();
    });

    function toggleSectionClusterRequirement() {
        const gradeSelect = document.getElementById('section_grade_level');
        const clusterSelect = document.getElementById('section_cluster_id');
        const hint = document.getElementById('section_cluster_hint');
        const isSeniorHigh = ['grade_11', 'grade_12'].includes(gradeSelect.value);

        clusterSelect.required = isSeniorHigh;
        clusterSelect.disabled = !isSeniorHigh;
        hint.textContent = isSeniorHigh
            ? 'Required for senior high school sections.'
            : 'Junior high school sections do not use clusters.';

        if (!isSeniorHigh) {
            clusterSelect.value = '';
        }
    }

    document.getElementById('section_grade_level').addEventListener('change', toggleSectionClusterRequirement);
</script>
@endsection
