<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $activeTab = $request->string('tab')->toString() === 'students' ? 'students' : 'staff';
        $staffRoles = Role::query()->where('role_name', '!=', 'student')->orderBy('role_name')->get();
        $roleCounts = Role::query()
            ->get()
            ->mapWithKeys(function (Role $role) {
                $count = $role->role_name === 'student'
                    ? Student::query()->where('role_id', $role->id)->count()
                    : Staff::query()->where('role_id', $role->id)->count();

                return [$role->role_name => $count];
            });

        if ($activeTab === 'students') {
            $query = Student::query()->with('role');
        } else {
            $query = Staff::query()->with('role');

            if ($request->filled('role')) {
                $query->whereHas('role', fn ($q) => $q->where('role_name', $request->string('role')->toString()));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();

        return view('users.admin.users', [
            'activeTab' => $activeTab,
            'users' => $users,
            'staffRoles' => $staffRoles,
            'roleCounts' => $roleCounts,
            'totalUsers' => Staff::query()->count() + Student::query()->whereNotNull('username')->count(),
            'staffCount' => Staff::query()->count(),
            'studentCount' => Student::query()->whereNotNull('username')->count(),
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'username' => ['required', 'string', 'max:255', 'unique:staffs,username'],
            'email' => ['required', 'email', 'max:255', 'unique:staffs,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('roles', 'role_name')],
        ]);

        $role = Role::query()->where('role_name', $validated['role'])->firstOrFail();

        Staff::query()->create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'status' => 'active',
                'change_password' => true,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'birthdate' => $validated['birthdate'] ?? null,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $account = $request->boolean('is_student')
            ? Student::query()->findOrFail($user)
            : Staff::query()->findOrFail($user);
        $table = $account instanceof Student ? 'students' : 'staffs';
        $key = $account instanceof Student ? $account->getKey() : $account->staff_id;

        $validated = $request->validate([
            'username' => [$account instanceof Student ? 'nullable' : 'required', 'string', 'max:255', Rule::unique($table, 'username')->ignore($key)],
            'email' => ['required', 'email', 'max:255', Rule::unique($table, 'email')->ignore($key)],
            'role' => ['nullable', Rule::exists('roles', 'role_name')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $updates = [
            'email' => $validated['email'],
        ];

        if (! $account instanceof Student) {
            $updates['username'] = $validated['username'];
        }

        if (! empty($validated['role'])) {
            $updates['role_id'] = Role::query()->where('role_name', $validated['role'])->value('id');
        }

        if (! empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
            $updates['change_password'] = true;
        }

        $account->update($updates);

        return back()->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, string $user): RedirectResponse
    {
        $account = $request->boolean('is_student')
            ? Student::query()->findOrFail($user)
            : Staff::query()->findOrFail($user);

        if (! $account instanceof Student && auth()->user()?->staff_id === $account->staff_id) {
            return back()->withErrors(['user' => 'You cannot change your own status.']);
        }

        $newStatus = $account->status === 'inactive' ? 'active' : 'inactive';
        $account->update(['status' => $newStatus]);

        return back()->with('success', 'User status updated.');
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $account = $request->boolean('is_student')
            ? Student::query()->findOrFail($user)
            : Staff::query()->findOrFail($user);

        if (! $account instanceof Student && auth()->user()?->staff_id === $account->staff_id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $account->delete();

        return back()->with('success', 'User deleted.');
    }
}
