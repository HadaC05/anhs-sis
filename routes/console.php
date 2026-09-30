<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('registrar:grade-digest', function () {
    $count = \App\Support\RegistrarGradeDigest::sendWhenDue();
    $this->info("{$count} new grade submission(s) included in the registrar digest.");
})->purpose('Send grouped grade submission notifications when the two-hour interval is due');

Schedule::command('registrar:grade-digest')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
