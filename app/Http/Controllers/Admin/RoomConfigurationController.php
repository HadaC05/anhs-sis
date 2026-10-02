<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        return view('users.admin.room-config', [
            'editingRoom' => $request->filled('edit') ? Room::query()->findOrFail($request->integer('edit')) : null,
            'rooms' => Room::query()
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rooms,name'],
        ]);

        Room::query()->create($validated);

        $prefix = $request->routeIs('principal.*') ? 'principal.' : 'admin.';

        return redirect()->route($prefix.'room-config.index')
            ->with('success', 'Room added successfully.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name')->ignore($room->id)],
        ]);

        DB::transaction(function () use ($room, $validated): void {
            $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);
            $lockedRoom->sections()->update(['room' => $validated['name']]);
            $lockedRoom->update($validated);
        });

        $prefix = $request->routeIs('principal.*') ? 'principal.' : 'admin.';

        return redirect()->route($prefix.'room-config.index')
            ->with('success', 'Room updated successfully.');
    }
}
