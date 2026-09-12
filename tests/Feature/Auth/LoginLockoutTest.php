<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

function createLoginLockoutStaff(string $username = 'lockout.user'): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Lockout',
        'last_name' => 'User',
        'status' => 'active',
    ]);
}

function attemptLockoutLogin(string $username, string $password): TestResponse
{
    test()->get(route('login'));

    return test()->from(route('login'))->post(route('login.store'), [
        'username' => $username,
        'password' => $password,
        'captcha_answer' => session('login_captcha_answer'),
    ]);
}

test('five consecutive failed logins lock the account for fifteen minutes', function () {
    $user = createLoginLockoutStaff();

    foreach (range(1, 5) as $ignored) {
        $response = attemptLockoutLogin($user->username, 'wrong-password');

        $response->assertSessionHasErrors('username');
        expect(session('errors')->first('username'))->toBe(__('auth.failed'));
        $this->assertGuest();
    }

    $response = attemptLockoutLogin($user->username, 'wrong-password');

    $response->assertSessionHasErrors('username');
    expect(session('errors')->first('username'))->toContain('Too many failed login attempts');
    expect(session('errors')->first('username'))->toContain('15 minutes');
    $this->assertGuest();
});

test('correct password is rejected while the account is locked', function () {
    $user = createLoginLockoutStaff();

    foreach (range(1, 5) as $ignored) {
        attemptLockoutLogin($user->username, 'wrong-password');
    }

    $response = attemptLockoutLogin($user->username, 'password');

    $response->assertSessionHasErrors('username');
    expect(session('errors')->first('username'))->toContain('Too many failed login attempts');
    $this->assertGuest();
});

test('a successful login clears consecutive failed attempts', function () {
    $user = createLoginLockoutStaff();

    foreach (range(1, 4) as $ignored) {
        attemptLockoutLogin($user->username, 'wrong-password')->assertSessionHasErrors('username');
    }

    attemptLockoutLogin($user->username, 'password')
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->post(route('logout'));
    $this->assertGuest();

    foreach (range(1, 5) as $ignored) {
        $response = attemptLockoutLogin($user->username, 'wrong-password');

        $response->assertSessionHasErrors('username');
        expect(session('errors')->first('username'))->toBe(__('auth.failed'));
    }

    $this->assertGuest();
});

test('failed logins show how many attempts remain', function () {
    $user = createLoginLockoutStaff();

    foreach ([4, 3, 2, 1] as $remaining) {
        attemptLockoutLogin($user->username, 'wrong-password');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('auth.failed'))
            ->assertSee(trans_choice('auth.attempts_remaining', $remaining, ['count' => $remaining]));
    }

    attemptLockoutLogin($user->username, 'wrong-password');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Too many failed login attempts')
        ->assertSee('15 minutes')
        ->assertDontSee('attempt remaining')
        ->assertDontSee('attempts remaining');
});

test('the login page does not show remaining attempts before any failures', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('attempt remaining')
        ->assertDontSee('attempts remaining');
});

test('login is allowed again after the fifteen minute lockout expires', function () {
    $user = createLoginLockoutStaff();

    foreach (range(1, 5) as $ignored) {
        attemptLockoutLogin($user->username, 'wrong-password');
    }

    attemptLockoutLogin($user->username, 'password')->assertSessionHasErrors('username');
    $this->assertGuest();

    $this->travel(15)->minutes();

    attemptLockoutLogin($user->username, 'password')
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
