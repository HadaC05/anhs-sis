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

    public function __construct(public int $importId) {}

    public function handle(TeacherSectionController $controller): void
    {
        $import = AdvisoryClassListImport::query()->find($this->importId);

        if (! $import || $import->status !== 'queued') {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $temporaryPath = null;

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

            $section = Section::query()->findOrFail($import->section_ID);
            $result = $controller->importAdvisoryClassListRecords($records, $section, $import->requested_by);

            $import->update([
                'status' => 'completed',
                'result' => $result,
                'file_contents' => null,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $import->update([
                'status' => 'failed',
                'failure_message' => $exception->getMessage(),
                'file_contents' => null,
                'completed_at' => now(),
            ]);
        } finally {
            if ($temporaryPath && file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
