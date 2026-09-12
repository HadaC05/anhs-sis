<?php

use App\Http\Requests\Student\StoreStudentDocumentsRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createDocumentsStudent(array $overrides = []): Student
{
    return Student::query()->create(array_merge([
        'username' => 'student.documents',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '123456789099',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ], $overrides));
}

function createStudentDocument(Student $student, array $overrides = []): StudentDocument
{
    return StudentDocument::query()->create(array_merge([
        'student_ID' => $student->id,
        'doc_type' => 'birth_certificate',
        'file_path' => 'student_documents/birth-certificate.pdf',
        'status' => 'pending',
        'date_uploaded' => now(),
    ], $overrides));
}

test('student documents page uses the student profile and grades page design', function () {
    $student = createDocumentsStudent();

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($student)->get(route('student.documents'));

    $response->assertOk();
    $response->assertSee('Documents');
    $response->assertSee('LRN');
    $response->assertSee('Student Name');
    $response->assertSee('Upload your required documents for enrollment verification. Each file must be 15MB or smaller.');
    $response->assertSee('0/2 required documents uploaded');
    $response->assertSee('Submit All');
    $response->assertSee('Requirement');
    $response->assertSee('Date Uploaded');
    $response->assertSee('Remarks');
    $response->assertSee('Actions');
    $response->assertSee('Good Moral Certificate');
    $response->assertDontSee('2x2 Photo');
    $response->assertDontSee('2x2 ID Photo');
    $response->assertDontSee('Document Requirements');
    $response->assertDontSee('Upload Progress');
    $response->assertDontSee('Click to upload or drag & drop');
});

test('student documents page hides replace and delete for verified documents', function () {
    $student = createDocumentsStudent();
    createStudentDocument($student, [
        'status' => 'verified',
        'date_verified' => now(),
    ]);

    $response = $this->actingAs($student)->get(route('student.documents'));

    $response->assertOk();
    $response->assertSee('This document is verified and cannot be replaced.');
    $response->assertDontSee('title="Replace Document"', false);
    $response->assertDontSee('title="Delete"', false);
});

test('student documents page shows the return reason for returned documents', function () {
    $student = createDocumentsStudent();
    $reason = \App\Models\DocumentReturnReason::query()->create([
        'name' => 'Missing PSA dry seal',
        'description' => 'The birth certificate copy is incomplete.',
    ]);

    createStudentDocument($student, [
        'status' => 'rejected',
        'return_reason_ID' => $reason->reason_ID,
        'date_verified' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk()
        ->assertSee('This document was returned: Missing PSA dry seal.');
});

test('student cannot replace a verified document', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();
    $document = createStudentDocument($student, [
        'status' => 'verified',
        'date_verified' => now(),
        'file_path' => 'student_documents/original.pdf',
    ]);

    $response = $this->actingAs($student)->post(route('student.documents.upload'), [
        'doc_type' => 'birth_certificate',
        'document' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    $document->refresh();

    expect($document->status)->toBe('verified')
        ->and($document->file_path)->toBe('student_documents/original.pdf');
});

test('student cannot delete a verified document', function () {
    $student = createDocumentsStudent();
    $document = createStudentDocument($student, [
        'status' => 'verified',
        'date_verified' => now(),
    ]);

    $response = $this->actingAs($student)->delete(route('student.documents.delete', $document));

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    expect(StudentDocument::query()->find($document->doc_ID))->not->toBeNull();
});

test('student can replace a pending document', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();
    createStudentDocument($student, [
        'status' => 'pending',
        'file_path' => 'student_documents/original.pdf',
    ]);

    $response = $this->actingAs($student)->post(route('student.documents.upload'), [
        'doc_type' => 'birth_certificate',
        'document' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $replaced = StudentDocument::query()
        ->where('student_ID', $student->id)
        ->where('doc_type', 'birth_certificate')
        ->first();

    expect($replaced)->not->toBeNull()
        ->and($replaced->status)->toBe('pending')
        ->and($replaced->file_path)->not->toBe('student_documents/original.pdf')
        ->and(StudentDocument::query()->where('student_ID', $student->id)->where('doc_type', 'birth_certificate')->count())->toBe(1);
});

test('uploaded documents remain visible after the page is reloaded', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();

    $this->actingAs($student)
        ->post(route('student.documents.upload'), [
            'documents' => [
                'birth_certificate' => UploadedFile::fake()->create('Birth Certificate: PSA?.pdf', 100, 'application/pdf'),
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $document = StudentDocument::query()
        ->where('student_ID', $student->id)
        ->where('doc_type', 'birth_certificate')
        ->first();

    expect($document)->not->toBeNull()
        ->and($document->file_path)->toBeString()
        ->and($document->file_path)->toStartWith('student_documents/')
        ->and(Storage::disk('public')->exists($document->file_path))->toBeTrue();

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk()
        ->assertSee('1/2 required documents uploaded')
        ->assertSee('View Document')
        ->assertSee('Pending')
        ->assertViewHas('documents', function ($documents) use ($student): bool {
            $uploaded = $documents->firstWhere('doc_type', 'birth_certificate');

            return $uploaded !== null
                && (int) $uploaded->student_ID === (int) $student->id
                && $uploaded->status === 'pending'
                && filled($uploaded->file_path);
        });
});

test('student can submit all selected documents in one request', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();

    $this->actingAs($student)
        ->post(route('student.documents.upload'), [
            'documents' => [
                'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 100, 'application/pdf'),
                'form_137' => UploadedFile::fake()->create('form-137.pdf', 100, 'application/pdf'),
                'good_moral' => UploadedFile::fake()->create('good-moral.jpg', 80, 'image/jpeg'),
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', '3 documents uploaded successfully.');

    expect(StudentDocument::query()->where('student_ID', $student->id)->count())->toBe(3);

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk()
        ->assertSee('2/2 required documents uploaded')
        ->assertSee('View Document');
});

test('student cannot upload a document larger than 15mb', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();

    $this->actingAs($student)
        ->from(route('student.documents'))
        ->post(route('student.documents.upload'), [
            'documents' => [
                'birth_certificate' => UploadedFile::fake()->create(
                    'birth.pdf',
                    StoreStudentDocumentsRequest::MAX_FILE_SIZE_KILOBYTES + 1,
                    'application/pdf'
                ),
            ],
        ])
        ->assertRedirect(route('student.documents'))
        ->assertSessionHasErrors([
            'documents.birth_certificate' => 'Each document must be 15MB or smaller.',
        ]);

    expect(StudentDocument::query()->where('student_ID', $student->id)->exists())->toBeFalse();
});

test('student can upload a document that is 15mb', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();

    $this->actingAs($student)
        ->post(route('student.documents.upload'), [
            'documents' => [
                'birth_certificate' => UploadedFile::fake()->create(
                    'birth.pdf',
                    StoreStudentDocumentsRequest::MAX_FILE_SIZE_KILOBYTES,
                    'application/pdf'
                ),
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Document uploaded successfully.');

    expect(StudentDocument::query()->where('student_ID', $student->id)->where('doc_type', 'birth_certificate')->exists())->toBeTrue();
});

test('student cannot submit documents without selecting a file', function () {
    $student = createDocumentsStudent();

    $this->actingAs($student)
        ->from(route('student.documents'))
        ->post(route('student.documents.upload'), [])
        ->assertRedirect(route('student.documents'))
        ->assertSessionHasErrors('documents');
});

test('submit all does not replace a verified document', function () {
    Storage::fake('public');

    $student = createDocumentsStudent();
    $document = createStudentDocument($student, [
        'status' => 'verified',
        'date_verified' => now(),
        'file_path' => 'student_documents/original.pdf',
    ]);

    $this->actingAs($student)
        ->post(route('student.documents.upload'), [
            'documents' => [
                'birth_certificate' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
                'form_137' => UploadedFile::fake()->create('form-137.pdf', 100, 'application/pdf'),
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    $document->refresh();

    expect($document->status)->toBe('verified')
        ->and($document->file_path)->toBe('student_documents/original.pdf')
        ->and(StudentDocument::query()->where('student_ID', $student->id)->where('doc_type', 'form_137')->exists())->toBeFalse();
});

test('documents page shows the latest file when duplicate types exist', function () {
    $student = createDocumentsStudent();

    createStudentDocument($student, [
        'file_path' => 'student_documents/old.pdf',
        'date_uploaded' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    createStudentDocument($student, [
        'file_path' => 'student_documents/new.pdf',
        'date_uploaded' => now(),
        'created_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk()
        ->assertViewHas('documents', function ($documents): bool {
            $birthCertificates = $documents->where('doc_type', 'birth_certificate');

            return $birthCertificates->count() === 1
                && $birthCertificates->first()->file_path === 'student_documents/new.pdf';
        });
});
