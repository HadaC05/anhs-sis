@extends('users.admin.layout')

@section('title', 'Academic Year Configuration')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Academic Year Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Manage school years and active/inactive status.</p>
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
            <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Academic Years</h2>
            <button type="button" onclick="openAcademicYearModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Academic Year</button>
        </div>

        <div class="p-6 border-b border-gray-200 bg-gray-50/50">
            <form method="GET" action="{{ route('admin.academic-year-config.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[220px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="School year" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="w-40">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                @if(request()->hasAny(['search', 'status', 'per_page']))
                    <a href="{{ route('admin.academic-year-config.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">School Year</th>
                        <th class="px-6 py-4">Start Date</th>
                        <th class="px-6 py-4">End Date</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($academicYears as $year)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $year->school_year }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($year->start_date)->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($year->end_date)->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $year->status ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $year->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" onclick='openAcademicYearModal(@json($year))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                    <form action="{{ route('admin.academic-year-config.toggle-status', $year) }}" method="POST" class="inline" onsubmit="return confirm('{{ $year->status ? 'Archive this academic year?' : 'Activate this academic year?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1 text-xs rounded {{ $year->status ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                            {{ $year->status ? 'Archive' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No academic years yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $academicYears->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<div id="academicYearModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="academicYearModalTitle" class="text-xl font-bold text-gray-800">Add Academic Year</h3>
        </div>
        <form id="academicYearForm" class="p-6 space-y-4" action="{{ route('admin.academic-year-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="academic_year_method" name="_method" value="POST">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">School Year</label>
                <input id="school_year" name="school_year" type="text" placeholder="2026-2027" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                    <input id="start_date" name="start_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                    <input id="end_date" name="end_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAcademicYearModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Academic Year</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAcademicYearModal(year = null) {
        const form = document.getElementById('academicYearForm');
        const method = document.getElementById('academic_year_method');
        const updateRouteTemplate = '{{ route('admin.academic-year-config.update', ['academicYear' => '__SY__']) }}';

        if (year) {
            document.getElementById('academicYearModalTitle').textContent = 'Edit Academic Year';
            form.action = updateRouteTemplate.replace('__SY__', year.SY_ID);
            method.value = 'PUT';
            document.getElementById('school_year').value = year.school_year || '';
            document.getElementById('start_date').value = year.start_date ? year.start_date.substring(0, 10) : '';
            document.getElementById('end_date').value = year.end_date ? year.end_date.substring(0, 10) : '';
        } else {
            document.getElementById('academicYearModalTitle').textContent = 'Add Academic Year';
            form.action = '{{ route('admin.academic-year-config.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('academicYearModal').classList.remove('hidden');
        document.getElementById('academicYearModal').classList.add('flex');
    }

    function closeAcademicYearModal() {
        document.getElementById('academicYearModal').classList.add('hidden');
        document.getElementById('academicYearModal').classList.remove('flex');
    }

    document.getElementById('academicYearModal').addEventListener('click', function (e) {
        if (e.target === this) closeAcademicYearModal();
    });
</script>
@endsection
