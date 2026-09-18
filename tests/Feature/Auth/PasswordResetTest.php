<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Notifications\PasswordResetOtp;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;

function createPasswordResetStaff(array $overrides = []): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create(array_merge([
        'role_id' => $role->id,
        'username' => 'reset.staff',
        'password' => Hash::make('password'),
        'change_password' => true,
        'first_name' => 'Reset',
        'last_name' => 'Staff',
        'email' => 'reset.staff@example.com',
        'status' => 'active',
    ], $overrides));
}

function createPasswordResetStudent(array $overrides = []): Student
{
    return Student::query()->create(array_merge([
        'username' => '123456789012',
        'password' => Hash::make('password'),
        'change_password' => true,
        'lrn' => '123456789012',
        'first_name' => 'Reset',
        'last_name' => 'Student',
        'email' => 'reset.student@example.com',
        'status' => 'active',
    ], $overrides));
}

test('login screen includes a forgot password link', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'), false)
        ->assertSee('Forgot password?');
});

test('reset password OTP screen can be rendered', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Email Address')
        ->assertSee('Send OTP')
        ->assertSee('formnovalidate', false)
        ->assertSee('One-Time Password')
        ->assertSee('Verify OTP');
});

test('a password reset OTP can be requested for an account email', function () {
    Notification::fake();

    $user = createPasswordResetStudent();

    $this->from(route('password.request'))
        ->post(route('password.otp.send'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'A one-time password has been sent to your email address.')
        ->assertSessionHas('password_reset_otp.email', $user->email)
        ->assertRedirect(route('password.request'));

    Notification::assertSentTo($user, PasswordResetOtp::class);
});

test('a correct password reset OTP redirects to the new password screen', function () {
    $user = createPasswordResetStudent();
    $otp = '123456';
    $token = Password::broker()->createToken($user);

    session()->put('password_reset_otp', [
        'email' => $user->email,
        'hash' => Hash::make($otp),
        'expires_at' => now()->addMinutes(10)->timestamp,
        'reset_token' => $token,
    ]);

    $this->post(route('password.otp.verify'), [
        'email' => $user->email,
        'otp' => $otp,
    ])->assertRedirect(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]));
});

test('a reset link can be requested for an account email', function (string $account) {
    Notification::fake();

    $user = $account === 'staff' ? createPasswordResetStaff() : createPasswordResetStudent();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', trans('passwords.sent'))
        ->assertRedirect(route('password.request'));

    Notification::assertSentTo($user, ResetPassword::class);
})->with(['staff', 'student']);

test('unknown emails show that the account was not found', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'missing@example.com'])
        ->assertSessionHasErrors(['email' => trans('passwords.user')])
        ->assertSessionMissing('status')
        ->assertRedirect(route('password.request'));

    Notification::assertNothingSent();
});

test('failed password reset delivery shows that the email was not found', function () {
    Event::listen(MessageSending::class, function (): void {
        throw new TransportException('Could not connect to SMTP host.');
    });

    $user = createPasswordResetStudent();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors(['email' => trans('passwords.send_failed')])
        ->assertSessionMissing('status')
        ->assertRedirect(route('password.request'));
});

test('password reset email can be delivered when the from address is blank', function () {
    config([
        'mail.default' => 'array',
        'mail.from.address' => '',
    ]);

    $user = createPasswordResetStudent();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', trans('passwords.sent'));
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = createPasswordResetStudent();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]))->assertOk()->assertSee('New Password');

        return true;
    });
});

test('password can be reset with a valid token', function (string $account) {
    Notification::fake();

    $user = $account === 'staff' ? createPasswordResetStaff() : createPasswordResetStudent();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $user->refresh();

        expect(Hash::check('NewPassword123!', $user->password))->toBeTrue()
            ->and($user->change_password)->toBeFalse()
            ->and($user->password_changed_at)->not->toBeNull();

        return true;
    });
})->with(['staff', 'student']);

test('password cannot be reset with a weak password', function () {
    Notification::fake();

    $user = createPasswordResetStudent();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->from(route('password.reset', $notification->token))
            ->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});
