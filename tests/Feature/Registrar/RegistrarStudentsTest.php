<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createRegistrarStudentFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'registrar']);
    $registrar = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'registrar.students',
        'password' => Hash::make('password'),
        'first_name' => 'Reg',
        'last_name' => 'istrar',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'DepEd SHS - GAS',
        'description' => 'General Academic Strand curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 301',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '777777777777',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'birthdate' => '2010-01-15',
        'sex' => 'female',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('registrar', 'student', 'enrollment');
}

test('registrar can view the student masterlist', function () {
    ['registrar' => $registrar] = createRegistrarStudentFixtures();
    $response = $this->actingAs($registrar)->get(route('registrar.students'));

    $response->assertOk();
    $response->assertSee('Student Masterlist');
    $response->assertSee('Santos, Ana');
    $response->assertSee('777777777777');
    $response->assertSee('Rizal');
});

test('registrar student details use guidance sections with document viewing and academic records', function () {
    ['registrar' => $registrar, 'student' => $student, 'enrollment' => $enrollment] = createRegistrarStudentFixtures();
    $student->addresses()->create([
        'address_type' => 'current', 'house_no' => '12', 'street_name' => 'Test Street',
        'barangay' => 'Test Barangay', 'municipality' => 'Butuan City',
        'province' => 'Agusan del Norte', 'country' => 'Philippines', 'zip_code' => '8600',
    ]);
    $student->guardians()->create([
        'relationship' => 'mother', 'first_name' => 'Maria', 'last_name' => 'Santos', 'contact_no' => '09123456789',
    ]);
    $document = StudentDocument::query()->create([
        'student_ID' => $student->id, 'doc_type' => 'birth_certificate', 'status' => 'verified',
        'file_path' => 'documents/birth.pdf', 'original_filename' => 'birth.pdf', 'date_uploaded' => now(),
    ]);
    StudentDocument::query()->create([
        'student_ID' => $student->id, 'doc_type' => 'id_photo', 'status' => 'pending',
        'file_path' => 'documents/photo.jpg', 'original_filename' => 'private-id-photo.jpg',
    ]);
    $url = route('registrar.students.show', ['student' => $student, 'enrollment_id' => $enrollment->enrollment_ID]);
    $this->actingAs($registrar)->get(route('registrar.students'))->assertOk()
        ->assertSee('1 uploaded')->assertSee($url.'#detail-documents');
    $this->get($url)->assertOk()->assertSee('Santos, Ana')->assertSee('Jump to section')
        ->assertSee('Enrollment Information')->assertSee('Personal Information')
        ->assertSee('Current Address')->assertSee('Butuan City')->assertSee('Maria')->assertSee('09123456789')
        ->assertSee('Parents / Guardian')->assertSee('Academic History')
        ->assertSee('Birth Certificate')->assertSee('Verified')->assertSee('birth.pdf')
        ->assertDontSee('private-id-photo.jpg')
        ->assertSee(route('registrar.students.documents.view', ['student' => $student, 'document' => $document]))
        ->assertSee(route('registrar.students.sf9', ['student' => $student, 'enrollment' => $enrollment]))
        ->assertSee(route('registrar.students.sf10', $student))
        ->assertDontSee(route('guidance.documents.verify', $document));
});

test('registrar can view a student without enrollment or supporting documents', function () {
    ['registrar' => $registrar] = createRegistrarStudentFixtures();
    $student = Student::query()->create(['lrn' => '777777777778', 'first_name' => 'New', 'last_name' => 'Student', 'status' => 'active']);
    $this->actingAs($registrar)->get(route('registrar.students.show', $student))->assertOk()
        ->assertSee('Student, New')->assertSee('No enrollment history found.')
        ->assertSee('No supporting documents uploaded yet.');
});

test('registrar document viewer streams files and rejects a different student or staff role', function () {
    Storage::fake('public');
    ['registrar' => $registrar, 'student' => $student] = createRegistrarStudentFixtures();
    $contents = '%PDF-1.4 test student document';
    Storage::disk('public')->put('documents/birth.pdf', $contents);
    $document = StudentDocument::query()->create([
        'student_ID' => $student->id, 'doc_type' => 'birth_certificate', 'status' => 'pending',
        'file_path' => 'documents/birth.pdf', 'date_uploaded' => now(),
    ]);
    $url = route('registrar.students.documents.view', ['student' => $student, 'document' => $document]);
    $response = $this->actingAs($registrar)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe($contents);
    $otherStudent = Student::query()->create(['lrn' => '777777777778', 'first_name' => 'Other', 'last_name' => 'Student', 'status' => 'active']);
    $this->get(route('registrar.students.documents.view', ['student' => $otherStudent, 'document' => $document]))->assertNotFound();
    $registrar->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id, 'change_password' => false]);
    $this->actingAs($registrar->fresh())->get($url)->assertForbidden();
});

test('registrar document viewer reports missing files without a server error', function () {
    Storage::fake('public');
    ['registrar' => $registrar, 'student' => $student] = createRegistrarStudentFixtures();
    $document = StudentDocument::query()->create([
        'student_ID' => $student->id, 'doc_type' => 'birth_certificate', 'status' => 'pending', 'file_path' => 'documents/missing.pdf',
    ]);
    $this->actingAs($registrar)->get(route('registrar.students.documents.view', ['student' => $student, 'document' => $document]))
        ->assertRedirect(route('registrar.students.show', $student))->assertSessionHasErrors('document');
    $this->get(route('registrar.students.show', $student))->assertOk()->assertSee('Document file was not found.');
});

test('registrar cannot select an enrollment belonging to another student', function () {
    ['registrar' => $registrar, 'enrollment' => $enrollment] = createRegistrarStudentFixtures();
    $otherStudent = Student::query()->create(['lrn' => '777777777778', 'first_name' => 'Other', 'last_name' => 'Student', 'status' => 'active']);
    $this->actingAs($registrar)->get(route('registrar.students.show', ['student' => $otherStudent, 'enrollment_id' => $enrollment->enrollment_ID]))->assertNotFound();
});

test('registrar student details respect the enrollment selected from the masterlist', function () {
    ['registrar' => $registrar, 'student' => $student, 'enrollment' => $currentEnrollment] = createRegistrarStudentFixtures();
    $previousYear = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $previousEnrollment = $currentEnrollment->replicate();
    $previousEnrollment->SY_ID = $previousYear->SY_ID;
    $previousEnrollment->save();
    $this->actingAs($registrar)->get(route('registrar.students.show', $student))->assertOk()
        ->assertViewHas('enrollment', fn ($record) => $record->is($currentEnrollment));
    $this->get(route('registrar.students.show', ['student' => $student, 'enrollment_id' => $previousEnrollment->enrollment_ID]))->assertOk()
        ->assertViewHas('enrollment', fn ($record) => $record->is($previousEnrollment));
});
