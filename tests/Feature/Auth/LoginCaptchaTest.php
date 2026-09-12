<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

function createLoginCaptchaStaff(string $username = 'captcha.user'): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Captcha',
        'last_name' => 'User',
        'status' => 'active',
    ]);
}

function attemptLoginWithCaptcha(string $username, string $password, mixed $captchaAnswer = null): TestResponse
{
    test()->get(route('login'));

    return test()->from(route('login'))->post(route('login.store'), [
        'username' => $username,
        'password' => $password,
        'captcha_answer' => $captchaAnswer ?? session('login_captcha_answer'),
    ]);
}

test('the login page limits the math check answer to three numeric digits', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('id="captcha_answer"', false)
        ->assertSee('inputmode="numeric"', false)
        ->assertSee('pattern="\d{1,3}"', false)
        ->assertSee('maxlength="3"', false)
        ->assertSee("this.value.replace(/\\D/g, '').slice(0, 3)", false);
});

test('login rejects a non-numeric math check answer', function (mixed $answer) {
    $user = createLoginCaptchaStaff();

    $response = attemptLoginWithCaptcha($user->username, 'password', $answer);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['captcha_answer' => 'Enter a number with up to 3 digits.']);
    $this->assertGuest();
})->with([
    'letters' => 'ab',
    'mixed' => '12a',
    'decimal' => '1.5',
    'negative' => '-2',
    'empty' => '',
    'four digits' => '1234',
]);

test('login rejects an incorrect numeric math check answer', function () {
    $user = createLoginCaptchaStaff();

    $this->get(route('login'));

    $expected = (int) session('login_captcha_answer');
    $wrongAnswer = $expected === 18 ? 17 : $expected + 1;

    $response = $this->from(route('login'))->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
        'captcha_answer' => (string) $wrongAnswer,
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['captcha_answer' => 'Incorrect answer to the security check.']);
    $this->assertGuest();
});

test('login accepts a numeric math check answer of up to three digits', function () {
    $user = createLoginCaptchaStaff();

    $response = attemptLoginWithCaptcha($user->username, 'password');

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
