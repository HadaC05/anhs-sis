@extends('users.admin.layout')

@section('title', 'Movement Reasons')

@section('content')
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl font-bold text-gray-800">Movement Reasons</h1>
        <p class="text-sm text-gray-500 mt-1">Manage reasons/causes for student movement and dropping out.</p>
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
            <h2 class="text-base font-bold text-[#296374] uppercase tracking-wider">Masterfile</h2>
            <button type="button" onclick="openMovementReasonModal()" class="inline-flex items-center px-3 py-2 bg-[#296374] text-white text-sm rounded-lg hover:bg-[#1e4a57] transition-colors">Add Reason</button>
        </div>

        <div class="p-6 border-b border-gray-200 bg-gray-50/50">
            <form method="GET" action="{{ route('admin.movement-reason-config.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[280px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Reason or description" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
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
                @if(request()->hasAny(['search', 'per_page']))
                    <a href="{{ route('admin.movement-reason-config.index') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Reason</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movementReasons as $reason)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-800 font-semibold">{{ $reason->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $reason->description ?: '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" onclick='openMovementReasonModal(@json($reason))' class="px-3 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                                    <form action="{{ route('admin.movement-reason-config.delete', $reason) }}" method="POST" class="inline" onsubmit="return confirm('Delete this movement reason?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">No movement reasons yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movementReasons->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $movementReasons->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<div id="movementReasonModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 id="movementReasonModalTitle" class="text-xl font-bold text-gray-800">Add Reason</h3>
        </div>
        <form id="movementReasonForm" class="p-6 space-y-4" action="{{ route('admin.movement-reason-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="movement_reason_method" name="_method" value="POST">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                <input id="movement_reason_name" name="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea id="movement_reason_description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeMovementReasonModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57]">Save Reason</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMovementReasonModal(reason = null) {
        const form = document.getElementById('movementReasonForm');
        const method = document.getElementById('movement_reason_method');
        const updateRouteTemplate = '{{ route('admin.movement-reason-config.update', ['movementReason' => '__REASON__']) }}';

        if (reason) {
            document.getElementById('movementReasonModalTitle').textContent = 'Edit Reason';
            form.action = updateRouteTemplate.replace('__REASON__', reason.reason_ID);
            method.value = 'PUT';
            document.getElementById('movement_reason_name').value = reason.name || '';
            document.getElementById('movement_reason_description').value = reason.description || '';
        } else {
            document.getElementById('movementReasonModalTitle').textContent = 'Add Reason';
            form.action = '{{ route('admin.movement-reason-config.store') }}';
            method.value = 'POST';
            form.reset();
        }

        document.getElementById('movementReasonModal').classList.remove('hidden');
        document.getElementById('movementReasonModal').classList.add('flex');
    }

    function closeMovementReasonModal() {
        document.getElementById('movementReasonModal').classList.add('hidden');
        document.getElementById('movementReasonModal').classList.remove('flex');
    }

    document.getElementById('movementReasonModal').addEventListener('click', function (e) {
        if (e.target === this) closeMovementReasonModal();
    });
</script>
@endsection
