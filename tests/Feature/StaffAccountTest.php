<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function accountStaff(string $role = 'principal'): Staff
{
    return Staff::create([
        'role_id' => Role::firstOrCreate(['role_name' => $role])->id,
        'username' => 'staff.profile',
        'first_name' => 'Ramon',
        'last_name' => 'Santos',
        'password' => 'OldPassword12!',
        'change_password' => false,
        'status' => 'active',
        'employee_no' => 'EMP-123',
    ]);
}

function staffAccountPhoto(): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII='));
}

test('all staff roles can access their profile from the menu', function (string $role) {
    $this->actingAs(accountStaff($role))->get(route('staff.account'))
        ->assertOk()->assertSee('View / Edit Profile')->assertSee('EMP-123')
        ->assertSee('staffPasswordDialog')->assertSee('Change Password');
})->with(['admin', 'teacher', 'principal', 'registrar', 'guidance counselor']);

test('staff can update personal details and replace their photo without changing protected fields', function () {
    Storage::fake('public');
    $staff = accountStaff();
    $other = Staff::create(['username' => 'other', 'first_name' => 'Other', 'last_name' => 'Staff', 'password' => 'password']);
    $oldPhoto = staffAccountPhoto()->store('staff_photos', 'public');
    $staff->update(['photo_path' => $oldPhoto]);

    $this->actingAs($staff)->put(route('staff.account.update'), [
        'first_name' => 'Updated', 'last_name' => 'Santos', 'email' => 'updated@example.com',
        'mobile_no' => '+639123456789', 'photo' => staffAccountPhoto(),
        'username' => 'hacked', 'role_id' => null, 'employee_no' => 'hacked',
        'staff_id' => $other->getKey(), 'password' => 'hacked', 'status' => 'inactive',
    ])->assertRedirect(route('staff.account'))->assertSessionHasNoErrors();

    $staff->refresh();
    expect($staff->first_name)->toBe('Updated')
        ->and($staff->email)->toBe('updated@example.com')
        ->and($staff->username)->toBe('staff.profile')
        ->and($staff->employee_no)->toBe('EMP-123')
        ->and($staff->roleName())->toBe('principal')
        ->and($staff->status)->toBe('active')
        ->and(Hash::check('OldPassword12!', $staff->password))->toBeTrue()
        ->and($other->fresh()->first_name)->toBe('Other');
    Storage::disk('public')->assertExists($staff->photo_path);
    Storage::disk('public')->assertMissing($oldPhoto);
    $this->get(route('staff.account'))->assertSee($staff->photoUrl(), false);
});

test('staff profile rejects invalid uploads and duplicate email', function () {
    Storage::fake('public');
    $staff = accountStaff();
    Staff::create(['username' => 'other', 'first_name' => 'Other', 'last_name' => 'Staff', 'password' => 'password', 'email' => 'other@example.com']);
    $this->actingAs($staff)->put(route('staff.account.update'), [
        'first_name' => 'Ramon', 'last_name' => 'Santos', 'email' => 'other@example.com',
        'photo' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'),
    ])->assertSessionHasErrors(['photo', 'email']);
    $this->put(route('staff.account.update'), [
        'first_name' => 'Ramon', 'last_name' => 'Santos',
        'photo' => staffAccountPhoto()->size(2049),
    ])->assertSessionHasErrors('photo');
    expect($staff->fresh()->photo_path)->toBeNull();
});

test('staff password changes require current password and a confirmed strong password', function () {
    $staff = accountStaff();
    $this->actingAs($staff)->put(route('staff.account.password'), [
        'current_password' => 'wrong', 'password' => 'NewPassword12!', 'password_confirmation' => 'NewPassword12!',
    ])->assertSessionHasErrors('current_password');
    $this->put(route('staff.account.password'), [
        'current_password' => 'OldPassword12!', 'password' => 'short', 'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
    expect(Hash::check('OldPassword12!', $staff->fresh()->password))->toBeTrue();
    $this->put(route('staff.account.password'), [
        'current_password' => 'OldPassword12!', 'password' => 'NewPassword12!', 'password_confirmation' => 'NewPassword12!',
    ])->assertRedirect(route('staff.account'))->assertSessionHasNoErrors();
    expect(Hash::check('NewPassword12!', $staff->fresh()->password))->toBeTrue()
        ->and($staff->fresh()->password_changed_at)->not->toBeNull();
});

test('guests and students cannot access staff account endpoints', function () {
    $this->get(route('staff.account'))->assertRedirect(route('login'));
    $student = Student::create([
        'username' => 'student.profile', 'password' => 'password', 'change_password' => false,
        'first_name' => 'Student', 'last_name' => 'Test', 'lrn' => '123456789012', 'status' => 'active',
    ]);
    $this->actingAs($student)->get(route('staff.account'))->assertForbidden();
    $this->put(route('staff.account.update'), [])->assertForbidden();
    $this->put(route('staff.account.password'), [])->assertForbidden();
});
