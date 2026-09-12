<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        $staffRoles = Role::query()->orderBy('role_name')->get();
        $roleCounts = $staffRoles
            ->mapWithKeys(fn (Role $role) => [
                $role->role_name => Staff::query()->where('role_id', $role->id)->count(),
            ]);
        $roleCounts['student'] = Student::query()
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->count();

        if ($activeTab === 'students') {
            // Student portal accounts are provisioned with an "approved" status.
            // Treat that as active in user management, while excluding student
            // application records that do not have a portal account yet.
            $query = Student::query()
                ->whereNotNull('username')
                ->where('username', '!=', '');
        } else {
            $query = Staff::query()->with('role');

            if ($request->filled('role')) {
                $query->whereHas('role', fn ($q) => $q->where('role_name', $request->string('role')->toString()));
            }
        }

        $status = $request->string('status')->toString();
        if ($status === 'inactive') {
            $query->where('status', 'inactive');
        } elseif ($activeTab === 'students') {
            $query->whereIn('status', ['active', 'approved']);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search, $activeTab): void {
                $inner->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");

                if ($activeTab === 'students') {
                    $inner->orWhere('lrn', 'like', "%{$search}%");
                }
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
            'suffixOptions' => StudentApplication::suffixOptions(),
            'earliestBirthdate' => Staff::EARLIEST_BIRTHDATE,
            'latestBirthdate' => Staff::LATEST_BIRTHDATE,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

    public function update(UpdateAdminUserRequest $request): RedirectResponse
    {
        $account = $request->account();
        $validated = $request->validated();

        $updates = [
            'email' => $validated['email'],
        ];

        if (! $account instanceof Student) {
            $updates['username'] = $validated['username'];
        }

        if (! $account instanceof Student && ! empty($validated['role'])) {
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

        if ($newStatus === 'inactive') {
            $this->invalidateAccountSessions($account);
        }

        return back()->with('success', 'User status updated.');
    }

    private function invalidateAccountSessions(Staff|Student $account): void
    {
        $table = config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->where('user_id', $account->getAuthIdentifier())
            ->delete();
    }
}
