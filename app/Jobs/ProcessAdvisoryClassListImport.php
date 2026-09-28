<?php

namespace App\Jobs;

use App\Http\Controllers\Teacher\TeacherSectionController;
use App\Models\AdvisoryClassListImport;
use App\Models\Section;
use App\Support\ClassListSpreadsheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessAdvisoryClassListImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public int $importId)
    {
        $this->timeout = (int) config('queue.class_list_import_timeout', 1200);
    }

    public function handle(TeacherSectionController $controller): void
    {
        $import = AdvisoryClassListImport::query()->find($this->importId);

        if (! $import || ! AdvisoryClassListImport::query()->whereKey($this->importId)
            ->where('status', 'queued')->update(['status' => 'processing', 'started_at' => now()])) {
            return;
        }

        $temporaryPath = null;
        $readingFile = true;

        try {
            $contents = base64_decode((string) $import->file_contents, true);

            if ($contents === false) {
                throw new RuntimeException('The uploaded class list could not be read. Please upload it again.');
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'anhs-class-list-');
            if ($temporaryPath === false || file_put_contents($temporaryPath, $contents) === false) {
                throw new RuntimeException('The uploaded class list could not be prepared for import. Please upload it again.');
            }

            $rows = ClassListSpreadsheet::rowsFromPath(
                $temporaryPath,
                pathinfo($import->original_filename, PATHINFO_EXTENSION),
            );
            $records = $controller->advisoryClassListRecords($rows);

            if ($records === []) {
                throw new RuntimeException('No valid learner rows were found. The file must include each learner\'s 12-digit LRN and name.');
            }

            $readingFile = false;
            $section = Section::query()->findOrFail($import->section_ID);
            $import->update(['total_students' => count($records)]);
            $result = $controller->importAdvisoryClassListRecords(
                $records, $section, $import->requested_by,
                function (int $processed, array $result) use ($import): void {
                    $import->update(['processed_students' => $processed, 'result' => $result]);
                },
            );

            $import->update([
                'status' => 'completed',
                'result' => $result,
                'file_contents' => null,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->failed($exception);
            if ($readingFile && $exception instanceof RuntimeException) {
                $import->update(['failure_message' => $exception->getMessage()]);
            }
        } finally {
            if ($temporaryPath && file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        AdvisoryClassListImport::query()->whereKey($this->importId)
            ->whereIn('status', ['queued', 'processing'])
            ->update([
                'status' => 'failed',
                'failure_message' => 'The import could not finish. Students already processed are saved. Please upload the file again to finish importing.',
                'file_contents' => null,
                'completed_at' => now(),
            ]);
    }
}
