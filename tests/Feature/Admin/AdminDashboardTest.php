<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

test('admin can view the user-focused dashboard', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.dashboard',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin Dashboard');
    $response->assertSee('Users in the System');
    $response->assertSee('Total Users');
    $response->assertSee('Staff Members');
    $response->assertSee('Users by Role');
    $response->assertSee('Staff Account Status');
    $response->assertSee('System Users');
    $response->assertDontSee('Total Enrollees');
    $response->assertDontSee('Gender Ratio');
    $response->assertDontSee('Student Application Summary');
    $response->assertDontSee('Recent Student Applications');
    $response->assertDontSee('>Applications</span>', false);
});
