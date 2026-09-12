<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

function createInactiveAccessAdmin(): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.inactive.access',
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
}

function createInactiveAccessTeacher(string $status = 'active'): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'teacher']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.inactive.access',
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Pat',
        'last_name' => 'Teacher',
        'status' => $status,
    ]);
}

test('inactive staff cannot log in with the correct password', function () {
    $teacher = createInactiveAccessTeacher('inactive');

    $this->get(route('login'));

    $response = $this->from(route('login'))->post(route('login.store'), [
        'username' => $teacher->username,
        'password' => 'password',
        'captcha_answer' => session('login_captcha_answer'),
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['username' => __('auth.inactive')]);
    $this->assertGuest();
});

test('a logged in staff member is signed out after being deactivated', function () {
    $admin = createInactiveAccessAdmin();
    $teacher = createInactiveAccessTeacher();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->patch(route('admin.users.toggle-status', $teacher->staff_id))
        ->assertRedirect();

    expect($teacher->fresh()?->status)->toBe('inactive');

    $response = $this->actingAs($teacher->fresh())->get(route('teacher.dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['username' => __('auth.inactive')]);
    $this->assertGuest();
});

test('reactivated staff can log in again', function () {
    $teacher = createInactiveAccessTeacher('inactive');
    $teacher->update(['status' => 'active']);

    $this->get(route('login'));

    $this->from(route('login'))->post(route('login.store'), [
        'username' => $teacher->username,
        'password' => 'password',
        'captcha_answer' => session('login_captcha_answer'),
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('inactive students cannot log in with the correct password', function () {
    $student = Student::query()->create([
        'username' => 'student.inactive.access',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '121212121212',
        'first_name' => 'Inactive',
        'last_name' => 'Student',
        'status' => 'inactive',
    ]);

    $this->get(route('login'));

    $response = $this->from(route('login'))->post(route('login.store'), [
        'username' => $student->username,
        'password' => 'password',
        'captcha_answer' => session('login_captcha_answer'),
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['username' => __('auth.inactive')]);
    $this->assertGuest();
});
