<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovementReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MovementReasonConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim($request->string('search')->toString());

        $movementReasons = MovementReason::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.admin.movement-reason-config', [
            'movementReasons' => $movementReasons,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:movement_reasons,name'],
            'description' => ['nullable', 'string'],
        ]);

        MovementReason::query()->create($validated);

        return back()->with('success', 'Movement reason created successfully.');
    }

    public function update(Request $request, MovementReason $movementReason): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('movement_reasons', 'name')->ignore($movementReason->reason_ID, 'reason_ID')],
            'description' => ['nullable', 'string'],
        ]);

        $movementReason->update($validated);

        return back()->with('success', 'Movement reason updated successfully.');
    }

    public function destroy(MovementReason $movementReason): RedirectResponse
    {
        $movementReason->delete();

        return back()->with('success', 'Movement reason deleted successfully.');
    }
}
