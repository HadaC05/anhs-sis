<?php

namespace App\Support;

use App\Jobs\ProcessAdvisoryClassListImport;
use App\Models\AdvisoryClassListImport;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SectionStudentImport
{
    public function advisoryClassListImportStatus(Request $request, Section $section, int $import): \Illuminate\Http\JsonResponse
    {
        $import = AdvisoryClassListImport::query()
            ->select(['id', 'status', 'total_students', 'processed_students', 'result', 'created_at'])
            ->where('section_ID', $section->section_ID)
            ->where('requested_by', $request->user()->staff_id)
            ->findOrFail($import);

        if ($import->status === 'queued') {
            app(LocalImportWorker::class)->start();
        }

        return response()->json([
            'status' => $import->status,
            'total_students' => $import->total_students,
            'processed_students' => $import->processed_students,
            'enrolled_students' => ($import->result['createdEnrollments'] ?? 0) + ($import->result['existingEnrollments'] ?? 0),
            'skipped_students' => $import->result['skippedStudents'] ?? 0,
            'waiting_for_worker' => $import->status === 'queued' && $import->created_at->lt(now()->subMinute()),
        ])->header('Cache-Control', 'no-store');
    }

    public function importAdvisoryClassList(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'class_list' => ['required', 'file', 'max:15360', 'mimes:csv,txt,xlsx,pdf'],
        ]);

        $staffId = (int) $request->user()->staff_id;
        $runningImport = AdvisoryClassListImport::query()
            ->where('section_ID', $section->section_ID)
            ->where('requested_by', $staffId)
            ->whereIn('status', ['queued', 'processing'])
            ->exists();

        if ($runningImport) {
            return back()->withErrors(['class_list' => 'An import is already in progress for this advisory class. Wait for it to finish before uploading another file.']);
        }

        $file = $validated['class_list'];
        $import = AdvisoryClassListImport::query()->create([
            'section_ID' => $section->section_ID,
            'requested_by' => $staffId,
            'original_filename' => $file->getClientOriginalName(),
            // The worker is a separate service in production, so keep this
            // temporary payload in the shared database rather than local disk.
            'file_contents' => base64_encode($file->get()),
            'status' => 'queued',
        ]);

        ProcessAdvisoryClassListImport::dispatch($import->id);
        app(LocalImportWorker::class)->start();

        return back()->with('status', 'Student import queued. You can keep using the system; this page will show the result when it finishes.');
    }
}
