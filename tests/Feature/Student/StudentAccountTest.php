<?php

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createAccountStudent(array $overrides = []): Student
{
    $key = (string) ($overrides['key'] ?? uniqid());
    unset($overrides['key']);

    return Student::query()->create(array_merge([
        'username' => 'student.account.'.$key,
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'email' => 'ana.account.'.$key.'@example.com',
        'status' => 'active',
    ], $overrides));
}

test('student can view the account profile page from the header menu', function () {
    $student = createAccountStudent(['key' => 'view']);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="student-account-link"', false)
        ->assertSee(route('student.account'), false)
        ->assertSee('data-test="student-profile-photo-placeholder"', false)
        ->assertSee('data-test="student-logout-button"', false)
        ->assertSee('text-red-600', false);

    $this->actingAs($student)
        ->get(route('student.account'))
        ->assertOk()
        ->assertSee('Account Profile')
        ->assertSee('Login Username')
        ->assertSee('student.account.view')
        ->assertSee('ana.account.view@example.com')
        ->assertSee('readonly', false)
        ->assertSee('Change Password')
        ->assertSee('data-test="student-account-password-edit"', false)
        ->assertSee('data-test="student-account-password-modal"', false)
        ->assertSee('Password Checklist')
        ->assertSee('data-test="student-account-photo-placeholder"', false)
        ->assertDontSee('Leave these fields blank');
});

test('student cannot change username or email from the account profile page', function () {
    $student = createAccountStudent(['key' => 'locked']);

    $this->actingAs($student)
        ->from(route('student.account'))
        ->put(route('student.account.update'), [
            'username' => 'student.account.hacked',
            'email' => 'hacked@example.com',
        ])
        ->assertRedirect(route('student.account'))
        ->assertSessionHas('status', 'Account profile updated.');

    $student->refresh();

    expect($student->username)->toBe('student.account.locked')
        ->and($student->email)->toBe('ana.account.locked@example.com');
});

test('student can upload a profile photo that appears in the header menu', function () {
    Storage::fake('public');

    $student = createAccountStudent(['key' => 'photo']);

    $this->actingAs($student)
        ->put(route('student.account.update'), [
            'photo' => UploadedFile::fake()->create('id-photo.jpg', 120, 'image/jpeg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Account profile updated.');

    $student->refresh();
    $student->unsetRelation('photoDocument');

    $photo = $student->photoDocument;

    expect($photo)->not->toBeNull()
        ->and($photo->doc_type)->toBe('id_photo')
        ->and($photo->file_path)->toStartWith('student_documents/')
        ->and(Storage::disk('public')->exists($photo->file_path))->toBeTrue();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('data-test="student-profile-photo"', false)
        ->assertDontSee('data-test="student-profile-photo-placeholder"', false)
        ->assertSee($student->photoUrl(), false);
});

test('student can change password from the account profile modal', function () {
    $student = createAccountStudent(['key' => 'password']);

    $this->actingAs($student)
        ->put(route('student.account.password'), [
            'current_password' => 'password',
            'password' => 'NewPassword1!ab',
            'password_confirmation' => 'NewPassword1!ab',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Password updated.');

    $student->refresh();

    expect(Hash::check('NewPassword1!ab', $student->password))->toBeTrue()
        ->and($student->change_password)->toBeFalse()
        ->and($student->password_changed_at)->not->toBeNull();
});

test('student cannot change password with an incorrect current password', function () {
    $student = createAccountStudent(['key' => 'wrongpass']);

    $this->actingAs($student)
        ->from(route('student.account'))
        ->put(route('student.account.password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword1!ab',
            'password_confirmation' => 'NewPassword1!ab',
        ])
        ->assertRedirect(route('student.account'))
        ->assertSessionHasErrors('current_password');

    $student->refresh();

    expect(Hash::check('password', $student->password))->toBeTrue();
});

test('student cannot change password without the required fields', function () {
    $student = createAccountStudent(['key' => 'required']);

    $this->actingAs($student)
        ->from(route('student.account'))
        ->put(route('student.account.password'), [])
        ->assertRedirect(route('student.account'))
        ->assertSessionHasErrors(['current_password', 'password']);
});

test('student cannot change password with a weak password', function () {
    $student = createAccountStudent(['key' => 'weak']);

    $this->actingAs($student)
        ->from(route('student.account'))
        ->put(route('student.account.password'), [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])
        ->assertRedirect(route('student.account'))
        ->assertSessionHasErrors('password');

    $student->refresh();

    expect(Hash::check('password', $student->password))->toBeTrue();
});

test('student cannot change password when confirmation does not match', function () {
    $student = createAccountStudent(['key' => 'mismatch']);

    $this->actingAs($student)
        ->from(route('student.account'))
        ->put(route('student.account.password'), [
            'current_password' => 'password',
            'password' => 'NewPassword1!ab',
            'password_confirmation' => 'NewPassword2!ab',
        ])
        ->assertRedirect(route('student.account'))
        ->assertSessionHasErrors('password');
});

test('student documents page no longer offers 2x2 id photo uploads', function () {
    $student = createAccountStudent(['key' => 'docs']);

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk()
        ->assertDontSee('2x2 Photo')
        ->assertDontSee('2x2 ID Photo')
        ->assertSee('Good Moral Certificate');

    expect(StudentDocument::typeKeys())->not->toContain('id_photo');

    $this->actingAs($student)
        ->from(route('student.documents'))
        ->post(route('student.documents.upload'), [
            'documents' => [
                'id_photo' => UploadedFile::fake()->create('photo.jpg', 80, 'image/jpeg'),
            ],
        ])
        ->assertRedirect(route('student.documents'))
        ->assertSessionHasErrors('documents.id_photo');
});
