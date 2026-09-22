<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureGuidanceCounselor;
use App\Http\Middleware\EnsureLoginCaptcha;
use App\Http\Middleware\EnsurePrincipal;
use App\Http\Middleware\EnsureRegistrar;
use App\Http\Middleware\EnsureStudent;
use App\Http\Middleware\EnsureTeacher;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            PreventBackHistory::class,
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'guidance' => EnsureGuidanceCounselor::class,
            'login_captcha' => EnsureLoginCaptcha::class,
            'principal' => EnsurePrincipal::class,
            'registrar' => EnsureRegistrar::class,
            'student' => EnsureStudent::class,
            'teacher' => EnsureTeacher::class,
            'force_password' => ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            $request = app()->bound('request') ? request() : null;
            $context = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'method' => $request?->method(),
                'path' => $request?->path(),
                'trace' => $exception->getTraceAsString(),
            ];

            Log::error('Unhandled application exception.', $context);
            error_log('Unhandled application exception: '.json_encode($context));
        });

        $exceptions->render(function (TransportExceptionInterface $exception, Request $request) {
            if (! $request->routeIs('password.email')) {
                return null;
            }

            Log::warning('Password reset email could not be sent.', [
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans('passwords.send_failed')]);
        });
    })->create();
