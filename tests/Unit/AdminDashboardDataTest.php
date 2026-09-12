<?php

use App\Models\Role;
use App\Models\Staff;
use App\Support\AdminDashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin dashboard data summarizes users by role and status', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);

    Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.one',
        'password' => Hash::make('password'),
        'first_name' => 'Ada',
        'last_name' => 'Min',
        'status' => 'active',
    ]);

    Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.one',
        'password' => Hash::make('password'),
        'first_name' => 'Teach',
        'last_name' => 'Er',
        'status' => 'inactive',
    ]);

    $summary = AdminDashboardData::summary();

    expect($summary['staffCount'])->toBe(2)
        ->and($summary['activeStaffCount'])->toBe(1)
        ->and($summary['inactiveStaffCount'])->toBe(1)
        ->and($summary['totalUsers'])->toBe(2)
        ->and(collect($summary['usersByRole'])->pluck('total')->sum())->toBe(2);
});
