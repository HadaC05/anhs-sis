@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@php
    $managementRoutePrefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.';
@endphp

@section('title', 'Rooms')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">Rooms</h1>
    <p class="mt-1 text-sm text-gray-500">Add or edit rooms available for section assignments.</p>
</div>

@push('toasts')
    @include('users.admin.partials.section-toasts', ['latestImport' => null])
@endpush

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-gray-300 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-gray-800">Add Room</h2>
        <form method="POST" action="{{ route($managementRoutePrefix.'room-config.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="room_name" class="mb-1.5 block text-sm font-semibold text-gray-700">Room name</label>
                <input id="room_name" name="name" type="text" value="{{ $editingRoom ? '' : old('name') }}" required maxlength="255"
                    placeholder="e.g. Room 201" @if (! $editingRoom && $errors->has('name')) aria-invalid="true" aria-describedby="room_name_error" @endif
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#296374] focus:ring-[#296374]">
                @if (! $editingRoom && $errors->has('name'))
                    <p id="room_name_error" class="mt-1 text-sm text-red-600">{{ $errors->first('name') }}</p>
                @endif
            </div>
            <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90">Add Room</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-sm lg:col-span-2">
        <form method="GET" action="{{ route($managementRoutePrefix.'room-config.index') }}" class="flex flex-wrap items-center gap-2 border-b border-gray-100 p-4">
            <label for="room_search" class="sr-only">Search rooms</label>
            <input id="room_search" type="search" name="search" value="{{ request('search') }}" placeholder="Search rooms"
                class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 px-3 text-sm">
            <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2.5 text-sm font-semibold text-white">Search</button>
            @if (request()->filled('search'))
                <a href="{{ route($managementRoutePrefix.'room-config.index') }}" class="text-sm font-semibold text-gray-600">Reset</a>
            @endif
        </form>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr><th scope="col" class="px-5 py-3 font-semibold">Room name</th><th scope="col" class="px-5 py-3 text-right font-semibold">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rooms as $room)
                    <tr><td class="break-words px-5 py-3 font-medium text-gray-800">{{ $room->name }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route($managementRoutePrefix.'room-config.index', array_merge(request()->query(), ['edit' => $room->id])) }}" class="font-semibold text-[#296374] hover:underline" aria-label="Edit {{ $room->name }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-8 text-center text-gray-500">{{ request()->filled('search') ? 'No rooms match your search.' : 'No rooms yet. Add your first room using the form.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($rooms->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $rooms->links() }}</div>
        @endif
    </div>
</div>
@endsection

@if ($editingRoom)
    @push('modals')
        <dialog id="editRoomModal" aria-labelledby="editRoomTitle" class="m-auto w-full max-w-lg rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/70" style="width: min(32rem, calc(100vw - 2rem)); max-height: calc(100dvh - 2rem);">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 id="editRoomTitle" class="text-lg font-bold text-gray-800">Edit Room</h2>
                <a href="{{ route($managementRoutePrefix.'room-config.index', request()->except('edit')) }}" aria-label="Close edit room" class="rounded px-2 text-2xl text-gray-500">&times;</a>
            </div>
            <form method="POST" action="{{ route($managementRoutePrefix.'room-config.update', $editingRoom) }}" class="space-y-5 p-6">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_room_name" class="mb-1.5 block text-sm font-semibold text-gray-700">Room name</label>
                    <input id="edit_room_name" name="name" type="text" value="{{ old('name', $editingRoom->name) }}" required maxlength="255" autofocus
                        @error('name') aria-invalid="true" aria-describedby="edit_room_name_error" @enderror
                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#296374] focus:ring-[#296374]">
                    @error('name')
                        <p id="edit_room_name_error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route($managementRoutePrefix.'room-config.index', request()->except('edit')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-600">Cancel</a>
                    <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2.5 text-sm font-bold text-white hover:opacity-90">Save Changes</button>
                </div>
            </form>
        </dialog>
        <script>
            (() => {
                const modal = document.getElementById('editRoomModal');
                const closeUrl = @json(route($managementRoutePrefix.'room-config.index', request()->except('edit')));
                modal.showModal();
                modal.addEventListener('cancel', (event) => {
                    event.preventDefault();
                    window.location.assign(closeUrl);
                });
                modal.addEventListener('click', (event) => {
                    const bounds = modal.getBoundingClientRect();
                    if (event.target === modal && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
                        window.location.assign(closeUrl);
                    }
                });
            })();
        </script>
    @endpush
@endif
