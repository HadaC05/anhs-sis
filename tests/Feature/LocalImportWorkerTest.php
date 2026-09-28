<?php

use App\Support\LocalImportWorker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

test('local imports launch a hidden worker and throttle repeated polling', function () {
    app()->instance('env', 'local');
    config(['queue.auto_start_local_import_worker' => true, 'queue.default' => 'database']);
    Cache::forget('local-import-worker-starting');
    Process::fake();

    app(LocalImportWorker::class)->start();
    app(LocalImportWorker::class)->start();

    Process::assertRanTimes(fn () => true, 1);
    Process::assertRan(function ($process) {
        $script = mb_convert_encoding(base64_decode($process->command[4]), 'UTF-8', 'UTF-16LE');

        return $process->command[0] === 'powershell.exe'
            && str_contains($script, '-WindowStyle Hidden')
            && str_contains($script, 'queue:work')
            && str_contains($script, '--stop-when-empty');
    });
});

test('worker startup is disabled for supervised environments and non database queues', function () {
    app()->instance('env', 'local');
    Process::fake();
    config(['queue.auto_start_local_import_worker' => false, 'queue.default' => 'database']);
    app(LocalImportWorker::class)->start();
    config(['queue.auto_start_local_import_worker' => true, 'queue.default' => 'sync']);
    app(LocalImportWorker::class)->start();
    Process::assertNothingRan();
});

test('production never launches a local worker even when the setting is enabled', function () {
    app()->instance('env', 'production');
    config(['queue.auto_start_local_import_worker' => true, 'queue.default' => 'database']);
    Process::fake();
    app(LocalImportWorker::class)->start();
    Process::assertNothingRan();
});

test('import jobs use the production timeout configured by the dispatching server', function () {
    config(['queue.class_list_import_timeout' => 240]);
    $job = unserialize(serialize(new \App\Jobs\ProcessAdvisoryClassListImport(1)));
    expect($job->timeout)->toBe(240)->and($job->failOnTimeout)->toBeTrue();
});
