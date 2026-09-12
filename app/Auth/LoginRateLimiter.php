<?php

namespace App\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter as FortifyLoginRateLimiter;

class LoginRateLimiter extends FortifyLoginRateLimiter
{
    /**
     * Determine if the user has too many failed login attempts.
     */
    public function tooManyAttempts(Request $request): bool
    {
        return $this->limiter->tooManyAttempts($this->throttleKey($request), $this->maxAttempts());
    }

    /**
     * Increment the login attempts for the user.
     *
     * After the fifth consecutive failure, the lockout timer starts at 15 minutes.
     */
    public function increment(Request $request): void
    {
        $key = $this->throttleKey($request);
        $decaySeconds = $this->lockoutSeconds();

        if ($this->limiter->attempts($key) + 1 >= $this->maxAttempts()) {
            $this->limiter->clear($key);
            $this->limiter->increment($key, $decaySeconds, $this->maxAttempts());

            return;
        }

        $this->limiter->hit($key, $decaySeconds);
    }

    /**
     * Get the number of login attempts remaining before lockout.
     */
    public function remainingAttempts(Request $request): int
    {
        if ($this->usernameFrom($request) === '') {
            return $this->maxAttempts();
        }

        return max(0, $this->maxAttempts() - (int) $this->limiter->attempts($this->throttleKey($request)));
    }

    /**
     * Determine whether the login form should show remaining attempts.
     */
    public function shouldShowRemainingAttempts(Request $request): bool
    {
        if ($this->usernameFrom($request) === '' || $this->tooManyAttempts($request)) {
            return false;
        }

        return $this->remainingAttempts($request) < $this->maxAttempts();
    }

    /**
     * Get the lockout minutes remaining, or null when the account is not locked.
     */
    public function lockoutMinutesRemaining(Request $request): ?int
    {
        if (! $this->tooManyAttempts($request)) {
            return null;
        }

        return max(1, (int) ceil($this->availableIn($request) / 60));
    }

    /**
     * Get the throttle key for the given request.
     *
     * Lockouts are tied to the username so the same account cannot keep trying from another IP.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate($this->usernameFrom($request));
    }

    protected function usernameFrom(Request $request): string
    {
        $username = $request->input(Fortify::username())
            ?: $request->old(Fortify::username())
            ?: '';

        return Str::lower(trim((string) $username));
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('fortify.login_max_attempts', 5));
    }

    protected function lockoutSeconds(): int
    {
        return max(1, (int) config('fortify.login_lockout_minutes', 15)) * 60;
    }
}
