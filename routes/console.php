<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('database:reset-once', function () {
    $token = (string) getenv('DATABASE_RESET_TOKEN');

    if (strlen($token) < 16) {
        $this->error('DATABASE_RESET_TOKEN must be a unique value of at least 16 characters.');

        return 1;
    }

    $tokenHash = hash('sha256', $token);

    if (Schema::hasTable('database_reset_runs')
        && DB::table('database_reset_runs')->where('token_hash', $tokenHash)->exists()) {
        $this->info('Database reset already completed for this token; skipping.');

        return 0;
    }

    $this->warn('Resetting all database tables and seeding default data.');

    // Production blocks db:wipe and migrate:fresh by default. Allow only this
    // validated, one-time command to wipe the database, then restore the guard.
    DB::prohibitDestructiveCommands(false);

    try {
        foreach (['db:wipe', 'migrate', 'db:seed'] as $command) {
            $result = $this->call($command, ['--force' => true]);

            if ($result !== 0) {
                return $result;
            }
        }
    } finally {
        DB::prohibitDestructiveCommands(app()->isProduction());
    }

    DB::table('database_reset_runs')->insert([
        'token_hash' => $tokenHash,
        'completed_at' => now(),
    ]);

    $this->info('Database reset completed and recorded.');

    return 0;
})->purpose('Run a full database reset and default seed once per token');

Artisan::command('registrar:grade-digest', function () {
    $count = \App\Support\RegistrarGradeDigest::sendWhenDue();
    $this->info("{$count} new grade submission(s) included in the registrar digest.");
})->purpose('Send grouped grade submission notifications when the two-hour interval is due');

Schedule::command('registrar:grade-digest')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
