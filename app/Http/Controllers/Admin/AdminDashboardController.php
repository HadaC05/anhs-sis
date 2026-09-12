<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Staff;
use App\Support\AdminDashboardData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usersPerPage = (int) $request->integer('recent_users_per_page', 10);
        if (! in_array($usersPerPage, [5, 10, 15, 25, 50], true)) {
            $usersPerPage = 10;
        }

        $recentUsersStatus = $request->string('recent_users_status')->toString();
        $recentUsersRole = $request->string('recent_users_role')->toString();
        $recentUsersSearch = trim($request->string('recent_users_search')->toString());

        $recentUsers = Staff::query()
            ->with('role')
            ->when(in_array($recentUsersStatus, ['active', 'inactive'], true), function ($query) use ($recentUsersStatus): void {
                $query->where('status', $recentUsersStatus);
            })
            ->when($recentUsersRole !== '', function ($query) use ($recentUsersRole): void {
                $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', $recentUsersRole));
            })
            ->when($recentUsersSearch !== '', function ($query) use ($recentUsersSearch): void {
                $query->where(function ($inner) use ($recentUsersSearch): void {
                    $inner->where('username', 'like', "%{$recentUsersSearch}%")
                        ->orWhere('email', 'like', "%{$recentUsersSearch}%")
                        ->orWhere('first_name', 'like', "%{$recentUsersSearch}%")
                        ->orWhere('last_name', 'like', "%{$recentUsersSearch}%");
                });
            })
            ->latest()
            ->paginate($usersPerPage, ['*'], 'recent_users_page')
            ->withQueryString();

        $roles = Role::query()
            ->orderBy('role_name')
            ->pluck('role_name');

        return view('users.admin.dashboard', array_merge(
            AdminDashboardData::summary(),
            [
                'recentUsers' => $recentUsers,
                'roles' => $roles,
                'usersPerPage' => $usersPerPage,
            ]
        ));
    }
}
