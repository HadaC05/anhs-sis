<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('roles table does not include a student role after seeding', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->pluck('role_name')->all())
        ->toContain('admin', 'teacher', 'guidance counselor', 'registrar', 'principal')
        ->not->toContain('student');
});

test('students table no longer stores a role id', function () {
    expect(Schema::hasColumn('students', 'role_id'))->toBeFalse()
        ->and(Schema::hasColumn('staffs', 'role_id'))->toBeTrue();
});

test('authenticated students are treated as students without a roles table entry', function () {
    $student = Student::query()->create([
        'username' => 'student.default.role',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '101010101010',
        'first_name' => 'Default',
        'last_name' => 'Student',
        'status' => 'active',
    ]);

    expect($student->isStudent())->toBeTrue()
        ->and($student->roleName())->toBe('student');

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('staff keep their assigned roles from the roles table', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.default.role',
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    expect($admin->isStudent())->toBeFalse()
        ->and($admin->roleName())->toBe('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($admin)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});
