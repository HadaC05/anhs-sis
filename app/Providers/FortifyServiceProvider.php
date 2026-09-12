<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Auth\LoginRateLimiter;
use App\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter as FortifyLoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FortifyLoginRateLimiter::class, LoginRateLimiter::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponseContract::class, FailedPasswordResetLinkRequestResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configurePasswordResetMail();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            $first = random_int(1, 9);
            $second = random_int(1, 9);
            $limiter = app(FortifyLoginRateLimiter::class);

            session(['login_captcha_answer' => $first + $second]);

            return view('livewire.auth.login', [
                'captcha_first' => $first,
                'captcha_second' => $second,
                'loginAttemptsRemaining' => $limiter->shouldShowRemainingAttempts($request)
                    ? $limiter->remainingAttempts($request)
                    : null,
                'loginLockoutMinutes' => $limiter->lockoutMinutesRemaining($request),
            ]);
        });
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
    }

    /**
     * Customize the password reset email.
     */
    private function configurePasswordResetMail(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            $message = (new MailMessage)
                ->subject('Reset your password')
                ->greeting('Hello '.$notifiable->first_name.',')
                ->line('You are receiving this email because we received a password reset request for your Agusan National High School account.')
                ->action('Reset Password', $url)
                ->line("This password reset link will expire in {$minutes} minutes.")
                ->line('If you did not request a password reset, no further action is required.');

            $from = config('mail.from.address');
            $name = config('mail.from.name');

            if (is_string($from) && $from !== '') {
                $message->from($from, is_string($name) ? $name : null);
            }

            return $message;
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
