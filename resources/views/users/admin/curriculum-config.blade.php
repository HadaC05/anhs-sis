@extends('users.admin.layout')

@section('title', 'Curriculum Configuration')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Curriculum Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Configure curricula and assign subjects by grade level and semester.</p>
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
        <div class="border-b border-gray-200 bg-gray-50/50 px-4 py-2">
            <nav class="flex gap-2">
                <button type="button" id="curriculumsTabBtn" onclick="switchCurriculumTab('curriculums')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-[#296374] text-white">Curriculum</button>
                <button type="button" id="curriculumSubjectsTabBtn" onclick="switchCurriculumTab('curriculum_subjects')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-white text-gray-600 hover:bg-gray-100">Curriculum Subjects</button>
                <button type="button" id="curriculumOverviewTabBtn" onclick="switchCurriculumTab('curriculum_overview')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-white text-gray-600 hover:bg-gray-100">Curriculum Overview</button>
            </nav>
        </div>

        <div id="curriculumsTabPanel">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Curriculum</h2>
                <button type="button" onclick="openCurriculumModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Curriculum</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="curriculums">
                    <input type="hidden" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}">
                    <input type="hidden" name="curriculum_subjects_curriculum_ID" value="{{ request('curriculum_subjects_curriculum_ID') }}">
                    <input type="hidden" name="curriculum_subjects_cluster_ID" value="{{ request('curriculum_subjects_cluster_ID') }}">
                    <input type="hidden" name="curriculum_subjects_grade_level" value="{{ request('curriculum_subjects_grade_level') }}">
                    <input type="hidden" name="curriculum_subjects_semester" value="{{ request('curriculum_subjects_semester') }}">
                    <input type="hidden" name="curriculum_subjects_per_page" value="{{ request('curriculum_subjects_per_page', $curriculumSubjectsPerPage ?? 10) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                        <input type="text" name="curriculum_search" value="{{ request('curriculum_search') }}" placeholder="Name or description" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="curriculum_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="active" {{ request('curriculum_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('curriculum_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="curriculum_per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([5, 10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($curriculumPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['curriculum_search', 'curriculum_status', 'curriculum_per_page']))
                        <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculums']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($curriculums as $curriculum)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $curriculum->name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $curriculum->description ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $curriculum->status ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $curriculum->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openCurriculumModal(@json($curriculum))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.curriculum-config.toggle-status', $curriculum) }}" method="POST" class="inline" onsubmit="return confirm('{{ $curriculum->status ? 'Archive this curriculum?' : 'Activate this curriculum?' }}');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1 text-xs rounded {{ $curriculum->status ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                {{ $curriculum->status ? 'Archive' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No curriculum records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($curriculums->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $curriculums->appends(['tab' => 'curriculums'])->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <div id="curriculumSubjectsTabPanel" class="hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Curriculum Subjects</h2>
                <button type="button" onclick="openCurriculumSubjectModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Curriculum Subject</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="curriculum_subjects">
                    <input type="hidden" name="curriculum_search" value="{{ request('curriculum_search') }}">
                    <input type="hidden" name="curriculum_status" value="{{ request('curriculum_status') }}">
                    <input type="hidden" name="curriculum_per_page" value="{{ request('curriculum_per_page', $curriculumPerPage ?? 10) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search Subject</label>
                        <input type="text" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}" placeholder="Code or title" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Curriculum</label>
                        <select name="curriculum_subjects_curriculum_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            @foreach($curriculumOptions as $option)
                                <option value="{{ $option->curriculum_ID }}" {{ (int) request('curriculum_subjects_curriculum_ID') === (int) $option->curriculum_ID ? 'selected' : '' }}>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                        <select name="curriculum_subjects_cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            @foreach($clusters as $cluster)
                                <option value="{{ $cluster->cluster_ID }}" {{ (int) request('curriculum_subjects_cluster_ID') === (int) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-36">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Grade</label>
                        <select name="curriculum_subjects_grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="grade_11" {{ request('curriculum_subjects_grade_level') === 'grade_11' ? 'selected' : '' }}>Grade 11</option>
                            <option value="grade_12" {{ request('curriculum_subjects_grade_level') === 'grade_12' ? 'selected' : '' }}>Grade 12</option>
                        </select>
                    </div>
                    <div class="w-36">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Semester</label>
                        <select name="curriculum_subjects_semester" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All</option>
                            <option value="first" {{ request('curriculum_subjects_semester') === 'first' ? 'selected' : '' }}>First</option>
                            <option value="second" {{ request('curriculum_subjects_semester') === 'second' ? 'selected' : '' }}>Second</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="curriculum_subjects_per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([5, 10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($curriculumSubjectsPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['curriculum_subjects_search', 'curriculum_subjects_curriculum_ID', 'curriculum_subjects_cluster_ID', 'curriculum_subjects_grade_level', 'curriculum_subjects_semester', 'curriculum_subjects_per_page']))
                        <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Curriculum</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Cluster</th>
                            <th class="px-6 py-4">Grade Level</th>
                            <th class="px-6 py-4">Semester</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($curriculumSubjects as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ optional($item->curriculum)->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ optional($item->subject)->code }} - {{ optional($item->subject)->title }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ optional($item->cluster)->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600 uppercase">{{ str_replace('_', ' ', $item->grade_level) }}</td>
                                <td class="px-6 py-4 text-gray-600 capitalize">{{ $item->semester }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openCurriculumSubjectModal(@json($item))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.curriculum-config.subjects.delete', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this subject from curriculum?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No curriculum subjects yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($curriculumSubjects->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $curriculumSubjects->appends(['tab' => 'curriculum_subjects'])->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <div id="curriculumOverviewTabPanel" class="hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Curriculum Subject Overview</h2>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.curriculum-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="curriculum_overview">
                    <input type="hidden" name="curriculum_search" value="{{ request('curriculum_search') }}">
                    <input type="hidden" name="curriculum_status" value="{{ request('curriculum_status') }}">
                    <input type="hidden" name="curriculum_per_page" value="{{ request('curriculum_per_page', $curriculumPerPage ?? 10) }}">
                    <input type="hidden" name="curriculum_subjects_search" value="{{ request('curriculum_subjects_search') }}">
                    <input type="hidden" name="curriculum_subjects_curriculum_ID" value="{{ request('curriculum_subjects_curriculum_ID') }}">
                    <input type="hidden" name="curriculum_subjects_cluster_ID" value="{{ request('curriculum_subjects_cluster_ID') }}">
                    <input type="hidden" name="curriculum_subjects_grade_level" value="{{ request('curriculum_subjects_grade_level') }}">
                    <input type="hidden" name="curriculum_subjects_semester" value="{{ request('curriculum_subjects_semester') }}">
                    <input type="hidden" name="curriculum_subjects_per_page" value="{{ request('curriculum_subjects_per_page', $curriculumSubjectsPerPage ?? 10) }}">

                    <div class="w-64">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Curriculum</label>
                        <select name="overview_curriculum_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Curriculum</option>
                            @foreach($curriculumOptions as $option)
                                <option value="{{ $option->curriculum_ID }}" {{ (int) request('overview_curriculum_ID') === (int) $option->curriculum_ID ? 'selected' : '' }}>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->filled('overview_curriculum_ID'))
                        <a href="{{ route('admin.curriculum-config.index', ['tab' => 'curriculum_overview']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="p-6 space-y-6">
                @forelse($curriculumOverview as $curriculum)
                    @php
                        $grouped = $curriculum->curriculumSubjects->groupBy(fn($item) => $item->grade_level . '|' . $item->semester);
                        $slots = [
                            ['key' => 'grade_11|first', 'label' => 'Grade 11 - First Semester'],
                            ['key' => 'grade_11|second', 'label' => 'Grade 11 - Second Semester'],
                            ['key' => 'grade_12|first', 'label' => 'Grade 12 - First Semester'],
                            ['key' => 'grade_12|second', 'label' => 'Grade 12 - Second Semester'],
                        ];
                    @endphp

                    <div class="border border-gray-200 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 bg-gradient-to-r from-[#296374]/10 to-transparent border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800">{{ $curriculum->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">{{ $curriculum->description ?: 'No description provided.' }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                            @foreach($slots as $slot)
                                <div class="rounded-xl border border-gray-200 bg-white">
                                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                        <h4 class="text-sm font-semibold text-gray-700">{{ $slot['label'] }}</h4>
                                    </div>
                                    <div class="p-4">
                                        @php $items = $grouped->get($slot['key'], collect())->sortBy(fn($row) => optional($row->subject)->code); @endphp
                                        @if($items->isEmpty())
                                            <p class="text-sm text-gray-400">No subjects assigned.</p>
                                        @else
                                            <ul class="space-y-2">
                                                @foreach($items as $row)
                                                    <li class="text-sm text-gray-700">
                                                        <span class="font-semibold">{{ optional($row->subject)->code }}</span>
                                                        <span class="text-gray-600">- {{ optional($row->subject)->title }}</span>
                                                        <span class="text-xs text-gray-400">({{ optional($row->cluster)->name }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500 border border-gray-200 rounded-xl">
                        No curriculum subjects found for the selected filter.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div id="curriculumModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="curriculumModalTitle" class="text-xl font-bold text-gray-800">Add Curriculum</h3>
        </div>
        <form id="curriculumForm" class="p-6 space-y-4" action="{{ route('admin.curriculum-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="curriculum_method" name="_method" value="POST">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                <input id="curriculum_name" name="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea id="curriculum_description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCurriculumModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Curriculum</button>
            </div>
        </form>
    </div>
</div>

<div id="curriculumSubjectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="curriculumSubjectModalTitle" class="text-xl font-bold text-gray-800">Add Curriculum Subject</h3>
        </div>
        <form id="curriculumSubjectForm" class="p-6 space-y-4" action="{{ route('admin.curriculum-config.subjects.store') }}" method="POST">
            @csrf
            <input type="hidden" id="curriculum_subject_method" name="_method" value="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Curriculum</label>
                    <select id="curriculum_subject_curriculum_id" name="curriculum_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select curriculum</option>
                        @foreach ($curriculumOptions as $curriculum)
                            <option value="{{ $curriculum->curriculum_ID }}">{{ $curriculum->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Subject</label>
                    <select id="curriculum_subject_subject_id" name="subject_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->subject_ID }}" data-cluster="{{ $subject->cluster_ID }}">{{ $subject->code }} - {{ $subject->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                    <select id="curriculum_subject_cluster_id" name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select cluster</option>
                        @foreach ($clusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}">{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Grade Level</label>
                    <select id="curriculum_subject_grade_level" name="grade_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        <option value="grade_11">Grade 11</option>
                        <option value="grade_12">Grade 12</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Semester</label>
                    <select id="curriculum_subject_semester" name="semester" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        <option value="first">First</option>
                        <option value="second">Second</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCurriculumSubjectModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Curriculum Subject</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchCurriculumTab(tab) {
        const tabs = {
            curriculums: { panel: document.getElementById('curriculumsTabPanel'), button: document.getElementById('curriculumsTabBtn') },
            curriculum_subjects: { panel: document.getElementById('curriculumSubjectsTabPanel'), button: document.getElementById('curriculumSubjectsTabBtn') },
            curriculum_overview: { panel: document.getElementById('curriculumOverviewTabPanel'), button: document.getElementById('curriculumOverviewTabBtn') },
        };

        Object.keys(tabs).forEach(function (key) {
            const isActive = key === tab;
            tabs[key].panel.classList.toggle('hidden', !isActive);
            tabs[key].button.classList.toggle('bg-[#296374]', isActive);
            tabs[key].button.classList.toggle('text-white', isActive);
            tabs[key].button.classList.toggle('bg-white', !isActive);
            tabs[key].button.classList.toggle('text-gray-600', !isActive);
            tabs[key].button.classList.toggle('hover:bg-gray-100', !isActive);
        });
    }

    function openCurriculumModal(curriculum = null) {
        const form = document.getElementById('curriculumForm');
        const method = document.getElementById('curriculum_method');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.update', ['curriculum' => '__CURR__']) }}';

        if (curriculum) {
            document.getElementById('curriculumModalTitle').textContent = 'Edit Curriculum';
            form.action = updateRouteTemplate.replace('__CURR__', curriculum.curriculum_ID);
            method.value = 'PUT';
            document.getElementById('curriculum_name').value = curriculum.name || '';
            document.getElementById('curriculum_description').value = curriculum.description || '';
        } else {
            document.getElementById('curriculumModalTitle').textContent = 'Add Curriculum';
            form.action = '{{ route('admin.curriculum-config.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('curriculumModal').classList.remove('hidden');
        document.getElementById('curriculumModal').classList.add('flex');
    }

    function closeCurriculumModal() {
        document.getElementById('curriculumModal').classList.add('hidden');
        document.getElementById('curriculumModal').classList.remove('flex');
    }

    function openCurriculumSubjectModal(item = null) {
        const form = document.getElementById('curriculumSubjectForm');
        const method = document.getElementById('curriculum_subject_method');
        const updateRouteTemplate = '{{ route('admin.curriculum-config.subjects.update', ['curriculumSubject' => '__ITEM__']) }}';

        if (item) {
            document.getElementById('curriculumSubjectModalTitle').textContent = 'Edit Curriculum Subject';
            form.action = updateRouteTemplate.replace('__ITEM__', item.curr_subj_ID);
            method.value = 'PUT';
            document.getElementById('curriculum_subject_curriculum_id').value = item.curriculum_ID || '';
            document.getElementById('curriculum_subject_subject_id').value = item.subject_ID || '';
            document.getElementById('curriculum_subject_cluster_id').value = item.cluster_ID || '';
            document.getElementById('curriculum_subject_grade_level').value = item.grade_level || '';
            document.getElementById('curriculum_subject_semester').value = item.semester || '';
        } else {
            document.getElementById('curriculumSubjectModalTitle').textContent = 'Add Curriculum Subject';
            form.action = '{{ route('admin.curriculum-config.subjects.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('curriculumSubjectModal').classList.remove('hidden');
        document.getElementById('curriculumSubjectModal').classList.add('flex');
    }

    function closeCurriculumSubjectModal() {
        document.getElementById('curriculumSubjectModal').classList.add('hidden');
        document.getElementById('curriculumSubjectModal').classList.remove('flex');
    }

    document.getElementById('curriculum_subject_subject_id').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const clusterId = selected ? selected.getAttribute('data-cluster') : '';
        if (clusterId) {
            document.getElementById('curriculum_subject_cluster_id').value = clusterId;
        }
    });

    document.getElementById('curriculumModal').addEventListener('click', function (e) {
        if (e.target === this) closeCurriculumModal();
    });

    document.getElementById('curriculumSubjectModal').addEventListener('click', function (e) {
        if (e.target === this) closeCurriculumSubjectModal();
    });

    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab === 'curriculum_subjects') {
        switchCurriculumTab('curriculum_subjects');
    } else if (tab === 'curriculum_overview') {
        switchCurriculumTab('curriculum_overview');
    } else {
        switchCurriculumTab('curriculums');
    }
</script>
@endsection
