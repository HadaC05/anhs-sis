<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

test('admin applications page is no longer available', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.applications.removed',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get('/admin/applications');

    $response->assertNotFound();
});

test('admin cannot approve applications through the removed review endpoint', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.applications.approve.removed',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post('/admin/applications/1/approve');

    $response->assertNotFound();
});

test('admin cannot reject applications through the removed review endpoint', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.applications.reject.removed',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post('/admin/applications/1/reject');

    $response->assertNotFound();
});
