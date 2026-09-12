<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

test('idle session timeout is configured for five minutes', function () {
    expect(config('session.idle_timeout'))->toBe(5);
});

test('authenticated dashboards include a five minute idle timeout modal', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.idle.timeout',
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Idle',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('id="idle-session-timeout"', false);
    $response->assertSee('data-timeout-ms="300000"', false);
    $response->assertSee('data-test="idle-session-modal"', false);
    $response->assertSee('Session expired');
    $response->assertSee('You were idle for 5 minutes, so you have been logged out.');
    $response->assertSee(route('logout'), false);
    $response->assertSee('pageshow', false);
    $response->assertSee('window.location.replace(loginUrl)', false);
    $response->assertSee('signingOut = true', false);
    $response->assertSee('clearInterval(idleTimer)', false);
    $response->assertSee("localStorage.setItem('anhs-auth-fingerprint', 'guest')", false);
});

test('student dashboards include the idle timeout modal', function () {
    $student = Student::query()->create([
        'username' => 'student.idle.timeout',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '109876543210',
        'first_name' => 'Idle',
        'last_name' => 'Student',
        'status' => 'active',
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('id="idle-session-timeout"', false);
    $response->assertSee('data-timeout-ms="300000"', false);
});

test('guests do not see the idle session timeout modal', function () {
    $this->get(route('login'))->assertDontSee('id="idle-session-timeout"', false);
    $this->get(route('home'))->assertDontSee('id="idle-session-timeout"', false);
});
