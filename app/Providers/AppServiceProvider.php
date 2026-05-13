<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use App\Auth\MergedUserProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('merged_users', fn () => new MergedUserProvider());

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => tap(
            Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols(),
            function (Password $password): void {
                if (app()->isProduction()) {
                    $password->uncompromised();
                }
            }
        ));
    }
}
