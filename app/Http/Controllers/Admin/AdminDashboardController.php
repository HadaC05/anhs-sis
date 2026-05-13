<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentApplication;
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

        $appsPerPage = (int) $request->integer('recent_applications_per_page', 10);
        if (! in_array($appsPerPage, [5, 10, 15, 25, 50], true)) {
            $appsPerPage = 10;
        }

        $recentUsersStatus = $request->string('recent_users_status')->toString();
        $recentUsersRole = $request->string('recent_users_role')->toString();
        $recentUsersSearch = trim($request->string('recent_users_search')->toString());
        $recentApplicationStatus = $request->string('recent_applications_status')->toString();
        $recentApplicationSearch = trim($request->string('recent_applications_search')->toString());

        $studentCount = Student::query()->count();
        $staffRoleIds = Role::query()
            ->where('role_name', '!=', 'student')
            ->pluck('id');
        $staffCount = Staff::query()->whereIn('role_id', $staffRoleIds)->count();
        $userCount = $staffCount + Student::query()->whereNotNull('username')->count();
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
                        ->orWhere('email', 'like', "%{$recentUsersSearch}%");
                });
            })
            ->latest()
            ->paginate($usersPerPage, ['*'], 'recent_users_page')
            ->withQueryString();
        $applicationCounts = [
            'total' => StudentApplication::query()->count(),
            'pending' => StudentApplication::query()->where('status', 'pending')->count(),
            'approved' => StudentApplication::query()->where('status', 'approved')->count(),
            'rejected' => StudentApplication::query()->where('status', 'rejected')->count(),
        ];
        $recentApplications = StudentApplication::query()
            ->with(['reviewer', 'rejectionReason'])
            ->when(in_array($recentApplicationStatus, ['pending', 'approved', 'rejected'], true), function ($query) use ($recentApplicationStatus): void {
                $query->where('status', $recentApplicationStatus);
            })
            ->when($recentApplicationSearch !== '', function ($query) use ($recentApplicationSearch): void {
                $query->where(function ($inner) use ($recentApplicationSearch): void {
                    $inner->where('lrn', 'like', "%{$recentApplicationSearch}%")
                        ->orWhere('first_name', 'like', "%{$recentApplicationSearch}%")
                        ->orWhere('last_name', 'like', "%{$recentApplicationSearch}%");
                });
            })
            ->latest('submitted_at')
            ->paginate($appsPerPage, ['*'], 'recent_applications_page')
            ->withQueryString();

        $roles = Role::query()->orderBy('role_name')->pluck('role_name');

        return view('users.admin.dashboard', [
            'studentCount' => $studentCount,
            'staffCount' => $staffCount,
            'userCount' => $userCount,
            'recentUsers' => $recentUsers,
            'applicationCounts' => $applicationCounts,
            'recentApplications' => $recentApplications,
            'roles' => $roles,
            'usersPerPage' => $usersPerPage,
            'appsPerPage' => $appsPerPage,
        ]);
    }
}
