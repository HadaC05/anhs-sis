<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordResetOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetOtpController extends Controller
{
    private const OTP_TTL_SECONDS = 600;

    private const MAX_VERIFICATION_ATTEMPTS = 3;

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $email = strtolower($validated['email']);
        $rateLimitKey = $this->sendRateLimitKey($email, $request);

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_VERIFICATION_ATTEMPTS)) {
            return back()->withInput()->withErrors([
                'email' => 'Too many code requests. Please try again in 10 minutes.',
            ]);
        }

        $user = Password::broker()->getUser(['email' => $email]);

        if (! $user) {
            return back()->withInput()->withErrors(['email' => trans('passwords.user')]);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            $user->notify(new PasswordResetOtp($otp));
        } catch (TransportExceptionInterface) {
            return back()->withInput()->withErrors(['email' => trans('passwords.send_failed')]);
        }

        RateLimiter::hit($rateLimitKey, self::OTP_TTL_SECONDS);
        $request->session()->put('password_reset_otp', [
            'email' => $email,
            'hash' => Hash::make($otp),
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS)->timestamp,
            'reset_token' => Password::broker()->createToken($user),
        ]);

        return back()
            ->withInput(['email' => $email])
            ->with('status', 'A one-time password has been sent to your email address.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);
        $email = strtolower($validated['email']);
        $state = $request->session()->get('password_reset_otp');
        $rateLimitKey = $this->verifyRateLimitKey($email, $request);

        if (
            ! is_array($state)
            || ($state['email'] ?? null) !== $email
            || ! is_int($state['expires_at'] ?? null)
            || now()->timestamp > $state['expires_at']
        ) {
            $request->session()->forget('password_reset_otp');

            return back()->withInput(['email' => $email])->withErrors([
                'otp' => 'This code has expired. Please request a new one.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_VERIFICATION_ATTEMPTS)) {
            return back()->withInput(['email' => $email])->withErrors([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['otp'], $state['hash'] ?? '')) {
            RateLimiter::hit($rateLimitKey, self::OTP_TTL_SECONDS);

            return back()->withInput(['email' => $email])->withErrors([
                'otp' => 'The one-time password is incorrect.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->forget('password_reset_otp');

        return redirect()->route('password.reset', [
            'token' => $state['reset_token'],
            'email' => $email,
        ]);
    }

    private function sendRateLimitKey(string $email, Request $request): string
    {
        return 'password-reset-otp:send:'.sha1($email.'|'.$request->ip());
    }

    private function verifyRateLimitKey(string $email, Request $request): string
    {
        return 'password-reset-otp:verify:'.sha1($email.'|'.$request->ip());
    }
}
