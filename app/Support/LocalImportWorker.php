<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

class LocalImportWorker
{
    /** XAMPP does not provide a queue supervisor; start a short-lived worker on demand. */
    public function start(): void
    {
        if (! app()->environment('local') || ! config('queue.auto_start_local_import_worker') || config('queue.default') !== 'database') {
            return;
        }

        try {
            // Concurrent uploads and progress polls should not each spawn a process.
            if (! Cache::add('local-import-worker-starting', true, 15)) {
                return;
            }

            $quote = fn (string $value): string => "'".str_replace("'", "''", $value)."'";
            $php = (new PhpExecutableFinder)->find(false);
            if (! $php) {
                throw new RuntimeException('The local import worker could not find PHP CLI.');
            }
            $arguments = [
                'artisan', 'queue:work', 'database', '--stop-when-empty',
                '--tries=1', '--timeout=1200', '--max-time=1200', '--sleep=1',
                '--queue='.(string) config('queue.connections.database.queue', 'default'),
            ];
            // Encode the PowerShell script so paths are never interpreted by an intervening shell.
            $script = '$ErrorActionPreference = \'Stop\'; Start-Process -WindowStyle Hidden -FilePath '
                .$quote($php)
                .' -WorkingDirectory '.$quote(base_path())
                .' -ArgumentList '.implode(',', array_map($quote, $arguments))
                .' -RedirectStandardOutput '.$quote(storage_path('logs/import-worker.log'))
                .' -RedirectStandardError '.$quote(storage_path('logs/import-worker-error.log'));

            // The development server's $_SERVER omits Windows environment keys.
            // Explicitly inherit the OS environment so child processes can load
            // Windows libraries and connect to MySQL (SystemRoot is required).
            $environment = getenv();
            $result = Process::env($environment)->timeout(10)->run([
                'powershell.exe', '-NoProfile', '-NonInteractive', '-EncodedCommand',
                base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')),
            ]);
            if ($result->failed()) {
                // Some Apache service accounts cannot initialize PowerShell's CLR.
                // Windows Script Host can start the same hidden worker without it.
                $fallback = Process::env($environment)->timeout(10)->run([
                    'cscript.exe', '//Nologo', base_path('scripts/start-import-worker.vbs'),
                    $php, base_path(), (string) config('queue.connections.database.queue', 'default'),
                ])->throw();
                // Script Host sometimes reports a script error with exit code 0.
                if (trim($fallback->errorOutput()) !== '') {
                    throw new RuntimeException($fallback->errorOutput());
                }
            }
        } catch (Throwable $exception) {
            report($exception);
            // Keep the queued job available to a regular worker or a later startup attempt.
        }
    }
}
