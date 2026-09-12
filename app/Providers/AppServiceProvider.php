<?php

namespace App\Providers;

use App\Auth\MergedUserProvider;
use App\Notifications\Channels\DatabaseChannel;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LaravelDatabaseChannel::class, DatabaseChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('merged_users', fn () => new MergedUserProvider);

        $this->configureDefaults();
        $this->configureMail();
    }

    /**
     * Always attach a From header so empty .env values cannot crash mail sending.
     */
    protected function configureMail(): void
    {
        $address = config('mail.from.address');
        $name = config('mail.from.name');

        if (! is_string($address) || $address === '') {
            $address = 'hello@example.com';
            config(['mail.from.address' => $address]);
        }

        if (! is_string($name) || $name === '') {
            $name = 'Agusan National High School';
            config(['mail.from.name' => $name]);
        }

        Mail::alwaysFrom($address, $name);
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
