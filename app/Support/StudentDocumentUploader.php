<?php

namespace App\Support;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StudentDocumentUploader
{
    /**
     * @param  array<string, UploadedFile>  $uploads
     * @return list<StudentDocument>
     */
    public static function storeMany(Student $student, array $uploads): array
    {
        return DB::transaction(function () use ($student, $uploads): array {
            $stored = [];

            foreach ($uploads as $docType => $file) {
                $stored[] = self::store($student, $docType, $file);
            }

            return $stored;
        });
    }

    public static function store(Student $student, string $docType, UploadedFile $file): StudentDocument
    {
        $filePath = self::storeFile($file);
        $originalFilename = Str::limit($file->getClientOriginalName(), 255, '');

        $existing = StudentDocument::query()
            ->where('student_ID', $student->id)
            ->where('doc_type', $docType)
            ->orderByDesc('created_at')
            ->get();

        $current = $existing->shift();
        $previousPath = $current?->file_path;

        if ($current) {
            $current->update([
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'status' => 'pending',
                'date_uploaded' => now(),
                'date_verified' => null,
                'verified_by' => null,
                'return_reason_ID' => null,
            ]);
        } else {
            $current = StudentDocument::query()->create([
                'student_ID' => $student->id,
                'doc_type' => $docType,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'status' => 'pending',
                'date_uploaded' => now(),
            ]);
        }

        foreach ($existing as $duplicate) {
            self::deleteStoredFile($duplicate->file_path);
            $duplicate->delete();
        }

        if ($previousPath && $previousPath !== $filePath) {
            self::deleteStoredFile($previousPath);
        }

        return $current->refresh();
    }

    private static function storeFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $extension = 'bin';
        }

        $path = $file->storeAs('student_documents', Str::uuid()->toString().'.'.$extension, 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The document could not be stored.');
        }

        return $path;
    }

    private static function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
