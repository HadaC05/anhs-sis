<?php

use App\Models\Role;
use App\Models\Staff;

test('admin modules render with responsive navigation and record confirmation', function (string $page) {
    $admin = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'admin'])->id,
        'username' => 'admin.responsive', 'password' => 'password',
        'first_name' => 'System', 'last_name' => 'Admin', 'status' => 'active',
    ]);
    $response = $this->actingAs($admin)->get(route('admin.'.$page));
    $response->assertOk()
        ->assertSee('admin-shell', false)
        ->assertSee('id="sidebar-backdrop"', false)
        ->assertSee('aria-controls="admin-sidebar"', false)
        ->assertSee('id="recordActionConfirmation"', false);
})->with([
    'dashboard', 'users', 'school-information.edit', 'academic-year-config.index',
    'grading-term-config.index', 'curriculum-config.index', 'subject-config.index',
    'teacher-assignments.index', 'attendance-config.index', 'section-config.index',
    'movement-reason-config.index', 'document-return-reason-config.index',
]);
