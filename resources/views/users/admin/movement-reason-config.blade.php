@extends('users.admin.layout')

@section('title', 'Movement Reasons')

@section('content')
@php
    $modalOpen = $errors->any();
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Movement Reasons</h1>
        <p class="mt-1 text-sm text-gray-500">Manage reasons for student movement and dropping out.</p>
    </div>
    <button type="button" onclick="openMovementReasonModal()" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
        Add Reason
    </button>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any() && ! $modalOpen)
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="border-b border-gray-100 px-4 py-4">
        <form method="GET" action="{{ route('admin.movement-reason-config.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                </svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reason or description"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([5, 10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" @selected((int) ($perPage ?? 10) === $size)>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            @if (request()->hasAny(['search', 'per_page']))
                <a href="{{ route('admin.movement-reason-config.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">Reason</th>
                    <th class="border-r border-gray-200 px-5 py-4">Description</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($movementReasons as $reason)
                    @php
                        $reasonPayload = [
                            'reason_ID' => $reason->reason_ID,
                            'name' => $reason->name,
                            'description' => $reason->description,
                        ];
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $reason->name }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $reason->description ?: '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick='openMovementReasonModal(@json($reasonPayload))'
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.movement-reason-config.delete', $reason) }}" method="POST" class="inline" onsubmit="return confirm('Delete this movement reason?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-16 text-center text-gray-500">No movement reasons yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($movementReasons->hasPages())
        <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            {{ $movementReasons->withQueryString()->links() }}
        </div>
    @endif
</div>

<div id="movementReasonModal" role="dialog" aria-modal="true" aria-labelledby="movementReasonModalTitle" data-open="{{ $modalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $modalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="movementReasonModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Reason</h3>
                </div>
                <button type="button" onclick="closeMovementReasonModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="movementReasonForm" action="{{ route('admin.movement-reason-config.store') }}" method="POST">
            @csrf
            <input type="hidden" id="movement_reason_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="movement_reason_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Reason <span class="text-red-500">*</span></label>
                    <input id="movement_reason_name" name="name" type="text" value="{{ old('name') }}" required
                        class="{{ $fieldClass }} {{ $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}">
                    @error('name')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="movement_reason_description" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Description</label>
                    <textarea id="movement_reason_description" name="description" rows="4"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('description') ? 'border-red-300' : 'border-gray-200' }}">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeMovementReasonModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="movementReasonSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Reason
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMovementReasonModal(reason = null) {
        const form = document.getElementById('movementReasonForm');
        const method = document.getElementById('movement_reason_method');
        const title = document.getElementById('movementReasonModalTitle');
        const submit = document.getElementById('movementReasonSubmit');
        const modal = document.getElementById('movementReasonModal');
        const updateRouteTemplate = '{{ route('admin.movement-reason-config.update', ['movementReason' => '__REASON__']) }}';

        if (reason) {
            title.textContent = 'Edit Reason';
            submit.textContent = 'Update Reason';
            form.action = updateRouteTemplate.replace('__REASON__', reason.reason_ID);
            method.value = 'PUT';
            document.getElementById('movement_reason_name').value = reason.name || '';
            document.getElementById('movement_reason_description').value = reason.description || '';
        } else {
            title.textContent = 'Add Reason';
            submit.textContent = 'Save Reason';
            form.action = '{{ route('admin.movement-reason-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('movement_reason_name').focus();
    }

    function closeMovementReasonModal() {
        const modal = document.getElementById('movementReasonModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    document.getElementById('movementReasonModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeMovementReasonModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('movementReasonModal').getAttribute('data-open') === 'true') {
            closeMovementReasonModal();
        }
    });
</script>
@endsection
