@extends('users.admin.layout')

@section('title', 'Subject Configuration')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Subject Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Manage subjects and preferred courses masterfile.</p>
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
                <button type="button" id="subjectsTabBtn" onclick="switchSubjectConfigTab('subjects')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-[#296374] text-white">Subjects</button>
                <button type="button" id="preferredCoursesTabBtn" onclick="switchSubjectConfigTab('preferred_courses')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors bg-white text-gray-600 hover:bg-gray-100">Preferred Courses</button>
            </nav>
        </div>

        <div id="subjectsTabPanel">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Subjects</h2>
                <button type="button" onclick="openSubjectModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Subject</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.subject-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="subjects">
                    <input type="hidden" name="preferred_courses_search" value="{{ request('preferred_courses_search') }}">
                    <input type="hidden" name="preferred_courses_cluster_ID" value="{{ request('preferred_courses_cluster_ID') }}">
                    <input type="hidden" name="preferred_courses_per_page" value="{{ request('preferred_courses_per_page', $preferredCoursesPerPage ?? 10) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Code or title" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Type</label>
                        <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Types</option>
                            <option value="core" {{ request('type') === 'core' ? 'selected' : '' }}>Core</option>
                            <option value="applied" {{ request('type') === 'applied' ? 'selected' : '' }}>Applied</option>
                            <option value="specialized" {{ request('type') === 'specialized' ? 'selected' : '' }}>Specialized</option>
                        </select>
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                        <select name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Clusters</option>
                            @foreach ($clusters as $cluster)
                                <option value="{{ $cluster->cluster_ID }}" {{ (int) request('cluster_ID') === (int) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([10, 15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) ($perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['search', 'type', 'cluster_ID', 'status', 'per_page']))
                        <a href="{{ route('admin.subject-config.index', ['tab' => 'subjects']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Code</th>
                            <th class="px-6 py-4">Title</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Cluster</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($subjects as $subject)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-700 font-semibold">{{ $subject->code }}</td>
                                <td class="px-6 py-4 text-gray-800">{{ $subject->title }}</td>
                                <td class="px-6 py-4 text-gray-600 uppercase">{{ $subject->type }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ optional($subject->cluster)->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $subject->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($subject->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openSubjectModal(@json($subject))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.subject-config.delete', $subject) }}" method="POST" class="inline" onsubmit="return confirm('{{ $subject->status === 'active' ? 'Archive this subject?' : 'Restore this subject?' }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs rounded {{ $subject->status === 'active' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                {{ $subject->status === 'active' ? 'Archive' : 'Restore' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No subjects yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subjects->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $subjects->appends(['tab' => 'subjects'])->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <div id="preferredCoursesTabPanel" class="hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent flex items-center justify-between">
                <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Preferred Courses Masterfile</h2>
                <button type="button" onclick="openPreferredCourseModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Preferred Course</button>
            </div>

            <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                <form method="GET" action="{{ route('admin.subject-config.index') }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="preferred_courses">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="cluster_ID" value="{{ request('cluster_ID') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', $perPage ?? 15) }}">

                    <div class="min-w-[220px]">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                        <input type="text" name="preferred_courses_search" value="{{ request('preferred_courses_search') }}" placeholder="Course name" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="w-64">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Academic Cluster</label>
                        <select name="preferred_courses_cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Clusters</option>
                            @foreach ($preferredClusters as $cluster)
                                <option value="{{ $cluster->cluster_ID }}" {{ (int) request('preferred_courses_cluster_ID') === (int) $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                        <select name="preferred_courses_per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @foreach([5, 10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($preferredCoursesPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Filter</button>
                    @if(request()->hasAny(['preferred_courses_search', 'preferred_courses_cluster_ID', 'preferred_courses_per_page']))
                        <a href="{{ route('admin.subject-config.index', ['tab' => 'preferred_courses']) }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Academic Cluster</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($preferredCourses as $course)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $course->name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ optional($course->cluster)->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $course->description ?: '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" onclick='openPreferredCourseModal(@json($course))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                        <form action="{{ route('admin.subject-config.preferred-courses.delete', $course) }}" method="POST" class="inline" onsubmit="return confirm('Delete this preferred course?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No preferred courses yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($preferredCourses->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $preferredCourses->appends(['tab' => 'preferred_courses'])->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="subjectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="subjectModalTitle" class="text-xl font-bold text-gray-800">Add Subject</h3>
        </div>
        <form id="subjectForm" class="p-6 space-y-4" action="{{ route('admin.subject-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="subject_method" name="_method" value="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Code</label>
                    <input id="subject_code" name="code" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Type</label>
                    <select id="subject_type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select</option>
                        <option value="core">Core</option>
                        <option value="applied">Applied</option>
                        <option value="specialized">Specialized</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                <input id="subject_title" name="title" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cluster</label>
                <select id="subject_cluster_id" name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select cluster</option>
                    @foreach ($clusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}">{{ $cluster->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeSubjectModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<div id="preferredCourseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="preferredCourseModalTitle" class="text-xl font-bold text-gray-800">Add Preferred Course</h3>
        </div>
        <form id="preferredCourseForm" class="p-6 space-y-4" action="{{ route('admin.subject-config.preferred-courses.store') }}" method="POST">
            @csrf
            <input type="hidden" id="preferred_course_method" name="_method" value="POST">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Academic Cluster</label>
                <select id="preferred_course_cluster_id" name="cluster_ID" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select cluster</option>
                    @foreach ($preferredClusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}">{{ $cluster->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Course Name</label>
                <input id="preferred_course_name" name="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea id="preferred_course_description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closePreferredCourseModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Preferred Course</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchSubjectConfigTab(tab) {
        const tabs = {
            subjects: { panel: document.getElementById('subjectsTabPanel'), button: document.getElementById('subjectsTabBtn') },
            preferred_courses: { panel: document.getElementById('preferredCoursesTabPanel'), button: document.getElementById('preferredCoursesTabBtn') },
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

    function openSubjectModal(subject = null) {
        const form = document.getElementById('subjectForm');
        const method = document.getElementById('subject_method');
        const updateRouteTemplate = '{{ route('admin.subject-config.update', ['subject' => '__SUBJECT__']) }}';

        if (subject) {
            document.getElementById('subjectModalTitle').textContent = 'Edit Subject';
            form.action = updateRouteTemplate.replace('__SUBJECT__', subject.subject_ID);
            method.value = 'PUT';
            document.getElementById('subject_code').value = subject.code || '';
            document.getElementById('subject_title').value = subject.title || '';
            document.getElementById('subject_type').value = subject.type || '';
            document.getElementById('subject_cluster_id').value = subject.cluster_ID || '';
        } else {
            document.getElementById('subjectModalTitle').textContent = 'Add Subject';
            form.action = '{{ route('admin.subject-config.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('subjectModal').classList.remove('hidden');
        document.getElementById('subjectModal').classList.add('flex');
    }

    function closeSubjectModal() {
        document.getElementById('subjectModal').classList.add('hidden');
        document.getElementById('subjectModal').classList.remove('flex');
    }

    function openPreferredCourseModal(course = null) {
        const form = document.getElementById('preferredCourseForm');
        const method = document.getElementById('preferred_course_method');
        const updateRouteTemplate = '{{ route('admin.subject-config.preferred-courses.update', ['preferredCourse' => '__COURSE__']) }}';

        if (course) {
            document.getElementById('preferredCourseModalTitle').textContent = 'Edit Preferred Course';
            form.action = updateRouteTemplate.replace('__COURSE__', course.course_ID);
            method.value = 'PUT';
            document.getElementById('preferred_course_cluster_id').value = course.cluster_ID || '';
            document.getElementById('preferred_course_name').value = course.name || '';
            document.getElementById('preferred_course_description').value = course.description || '';
        } else {
            document.getElementById('preferredCourseModalTitle').textContent = 'Add Preferred Course';
            form.action = '{{ route('admin.subject-config.preferred-courses.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('preferredCourseModal').classList.remove('hidden');
        document.getElementById('preferredCourseModal').classList.add('flex');
    }

    function closePreferredCourseModal() {
        document.getElementById('preferredCourseModal').classList.add('hidden');
        document.getElementById('preferredCourseModal').classList.remove('flex');
    }

    document.getElementById('subjectModal').addEventListener('click', function (e) {
        if (e.target === this) closeSubjectModal();
    });

    document.getElementById('preferredCourseModal').addEventListener('click', function (e) {
        if (e.target === this) closePreferredCourseModal();
    });

    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab === 'preferred_courses') {
        switchSubjectConfigTab('preferred_courses');
    } else {
        switchSubjectConfigTab('subjects');
    }
</script>
@endsection
