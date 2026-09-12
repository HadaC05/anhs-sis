<?php

use App\Models\DocumentType;
use Illuminate\Support\Facades\Schema;

test('student documents store a foreign key to the document types table', function () {
    expect(Schema::hasColumn('student_documents', 'document_type_ID'))->toBeTrue()
        ->and(Schema::hasColumn('student_documents', 'doc_type'))->toBeFalse();
});

test('document types are seeded as the canonical reference list', function () {
    expect(DocumentType::query()->orderBy('sort_order')->pluck('slug')->all())
        ->toBe(DocumentType::slugs())
        ->and(DocumentType::typeDefinitions()[DocumentType::BIRTH_CERTIFICATE]['title'])->toBe('Birth Certificate')
        ->and(DocumentType::typeDefinitions()[DocumentType::FORM_137]['title'])->toBe('Form 137 / SF9')
        ->and(DocumentType::typeDefinitions()[DocumentType::GOOD_MORAL]['title'])->toBe('Good Moral Certificate')
        ->and(DocumentType::typeDefinitions()[DocumentType::ID_PHOTO]['title'])->toBe('2x2 Photo');
});

test('enrollment document types exclude the 2x2 photo', function () {
    expect(array_keys(DocumentType::typeDefinitions(enrollmentOnly: true)))
        ->toBe(DocumentType::enrollmentSlugs())
        ->and(DocumentType::enrollmentSlugs())->not->toContain(DocumentType::ID_PHOTO);
});
