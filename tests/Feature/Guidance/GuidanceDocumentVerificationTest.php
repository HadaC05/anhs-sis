<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createDocumentVerificationFixtures(): array
{
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.documents',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $student = Student::query()->create([
        'username' => 'student.document.verify',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '888888888888',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $document = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'form_137',
        'file_path' => 'student_documents/form-137.pdf',
        'status' => 'verified',
        'date_uploaded' => now(),
        'date_verified' => now(),
        'verified_by' => $user->staff_id,
    ]);

    return compact('user', 'student', 'enrollment', 'document');
}

test('guidance counselor can view unverify action for verified documents', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createDocumentVerificationFixtures();

    $response = $this->actingAs($user)->get(route('guidance.enrollments.show', $enrollment));

    $response->assertOk();
    $response->assertSee('Unverify');
    $response->assertSee('Verified');
});

test('guidance counselor can unverify a verified document', function () {
    ['user' => $user, 'document' => $document] = createDocumentVerificationFixtures();

    $response = $this->actingAs($user)->post(route('guidance.documents.unverify', $document));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $document->refresh();

    expect($document->status)->toBe('pending')
        ->and($document->date_verified)->toBeNull()
        ->and($document->verified_by)->toBeNull();
});

test('guidance counselor cannot unverify a pending document', function () {
    ['user' => $user, 'document' => $document] = createDocumentVerificationFixtures();

    $document->update([
        'status' => 'pending',
        'date_verified' => null,
        'verified_by' => null,
    ]);

    $response = $this->actingAs($user)->post(route('guidance.documents.unverify', $document));

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    expect($document->refresh()->status)->toBe('pending');
});

test('student can replace a document after guidance unverifies it', function () {
    Storage::fake('public');

    ['user' => $user, 'student' => $student, 'document' => $document] = createDocumentVerificationFixtures();

    $this->actingAs($user)->post(route('guidance.documents.unverify', $document))
        ->assertSessionHas('success');

    $response = $this->actingAs($student)->post(route('student.documents.upload'), [
        'doc_type' => 'form_137',
        'document' => UploadedFile::fake()->create('new-form-137.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $replaced = StudentDocument::query()
        ->where('student_ID', $student->id)
        ->where('doc_type', 'form_137')
        ->first();

    expect($replaced)->not->toBeNull()
        ->and($replaced->status)->toBe('pending')
        ->and($replaced->file_path)->not->toBe('student_documents/form-137.pdf');
});

test('guidance counselor verify redirects back to the documents step', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'document' => $document] = createDocumentVerificationFixtures();

    $document->update([
        'status' => 'pending',
        'date_verified' => null,
        'verified_by' => null,
    ]);

    $this->actingAs($user)
        ->post(route('guidance.documents.verify', $document))
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'documents',
        ]))
        ->assertSessionHas('success');

    expect($document->fresh()->status)->toBe('verified');
});

test('guidance counselor can verify multiple pending documents at once', function () {
    ['user' => $user, 'student' => $student, 'enrollment' => $enrollment] = createDocumentVerificationFixtures();

    $birthCertificate = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'birth_certificate',
        'file_path' => 'student_documents/birth.pdf',
        'status' => 'pending',
        'date_uploaded' => now(),
    ]);

    $goodMoral = StudentDocument::query()->create([
        'student_ID' => $student->id,
        'doc_type' => 'good_moral',
        'file_path' => 'student_documents/good-moral.pdf',
        'status' => 'pending',
        'date_uploaded' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('guidance.documents.bulk-verify'), [
            'enrollment_ID' => $enrollment->enrollment_ID,
            'document_ids' => [$birthCertificate->doc_ID, $goodMoral->doc_ID],
        ])
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'documents',
        ]))
        ->assertSessionHas('success');

    expect($birthCertificate->fresh()->status)->toBe('verified')
        ->and($goodMoral->fresh()->status)->toBe('verified');
});

test('guidance counselor must choose a return reason when returning a document', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'document' => $document] = createDocumentVerificationFixtures();

    $document->update([
        'status' => 'pending',
        'date_verified' => null,
        'verified_by' => null,
    ]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', ['enrollment' => $enrollment, 'step' => 'documents']))
        ->post(route('guidance.documents.reject', $document), [])
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'documents',
        ]))
        ->assertSessionHasErrors('return_reason_ID');

    expect($document->fresh()->status)->toBe('pending');
});

test('guidance counselor can return a document with a reason from the return reasons table', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'document' => $document] = createDocumentVerificationFixtures();

    $document->update([
        'status' => 'pending',
        'date_verified' => null,
        'verified_by' => null,
    ]);

    $reason = \App\Models\DocumentReturnReason::query()->create([
        'name' => 'Unreadable photocopy',
        'description' => 'The scan cannot be read.',
    ]);

    $this->actingAs($user)
        ->post(route('guidance.documents.reject', $document), [
            'return_reason_ID' => $reason->reason_ID,
        ])
        ->assertRedirect(route('guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'step' => 'documents',
        ]))
        ->assertSessionHas('success');

    $document->refresh();

    expect($document->status)->toBe(\App\Models\DocumentStatus::RETURNED)
        ->and($document->return_reason_ID)->toBe($reason->reason_ID);
});
