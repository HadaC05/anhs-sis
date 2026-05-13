<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureGuidanceCounselor;
use App\Http\Middleware\EnsureLoginCaptcha;
use App\Http\Middleware\EnsurePrincipal;
use App\Http\Middleware\EnsureRegistrar;
use App\Http\Middleware\EnsureStudent;
use App\Http\Middleware\EnsureTeacher;
use App\Http\Middleware\ForcePasswordChange;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
        //
    })->create();
