<?php

use App\Models\DocumentStatus;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('student documents store a foreign key to the document statuses table', function () {
    expect(Schema::hasColumn('student_documents', 'document_status_ID'))->toBeTrue()
        ->and(Schema::hasColumn('student_documents', 'status'))->toBeFalse();
});

test('document statuses are seeded as pending, verified, and returned', function () {
    expect(DocumentStatus::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(DocumentStatus::slugs())
        ->and(DocumentStatus::options())->toMatchArray([
            'pending' => 'Pending',
            'verified' => 'Verified',
            'returned' => 'Returned',
        ]);
});

test('student documents store a status that exists in the document statuses table', function () {
    $student = Student::query()->create([
        'username' => 'student.document.status',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $document = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'birth_certificate',
        'file_path' => 'student_documents/birth.pdf',
        'status' => DocumentStatus::PENDING,
        'date_uploaded' => now(),
    ]);

    expect($document->status)->toBe(DocumentStatus::PENDING)
        ->and($document->document_status_ID)->toBe(DocumentStatus::idFor(DocumentStatus::PENDING))
        ->and($document->status_label)->toBe('Pending');

    $document->update(['status' => 'rejected']);

    expect($document->fresh()->status)->toBe(DocumentStatus::RETURNED)
        ->and($document->fresh()->isReturned())->toBeTrue();
});
