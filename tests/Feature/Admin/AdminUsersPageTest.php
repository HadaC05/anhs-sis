<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

test('admin can view the restyled staff users page without table avatars', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.users',
        'email' => 'admin.users@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users'));

    $response->assertOk();
    $response->assertSee('Users');
    $response->assertSee('Add New Staff');
    $response->assertSee('Add new staff');
    $response->assertSee('Personal information');
    $response->assertSee('Account access');
    $response->assertSee('Create staff account');
    $response->assertSee('Use at least 12 characters, with uppercase and lowercase letters, a number, and a symbol.');
    $response->assertSee('Staff Users');
    $response->assertSee('Jr.');
    $response->assertSee('min="'.Staff::EARLIEST_BIRTHDATE.'"', false);
    $response->assertSee('max="'.Staff::LATEST_BIRTHDATE.'"', false);
    $response->assertSee('Admin, System');
    $response->assertSee('admin.users');
    $response->assertSee('admin.users@anhs.local');
    $response->assertDontSee('Delete User');
    $response->assertDontSee('rounded-full flex items-center justify-center font-bold text-white', false);
});

test('admin can view student accounts on the users page', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.students',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    Student::query()->create([
        'username' => 'student.portal',
        'email' => 'ana.santos@anhs.local',
        'password' => Hash::make('password'),
        'lrn' => '123456789099',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users', ['tab' => 'students']));

    $response->assertOk();
    $response->assertSee('Student Accounts');
    $response->assertSee('Santos, Ana');
    $response->assertSee('student.portal');
    $response->assertSee('123456789099');
    $response->assertDontSee('rounded-full flex items-center justify-center font-bold text-white', false);
});

test('admin can search student accounts by lrn', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.search',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    Student::query()->create([
        'username' => 'student.match',
        'password' => Hash::make('password'),
        'lrn' => '555555555555',
        'first_name' => 'Match',
        'last_name' => 'Learner',
        'status' => 'active',
    ]);

    Student::query()->create([
        'username' => 'student.other',
        'password' => Hash::make('password'),
        'lrn' => '999999999999',
        'first_name' => 'Other',
        'last_name' => 'Student',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users', [
        'tab' => 'students',
        'search' => '555555555555',
    ]));

    $response->assertOk();
    $response->assertSee('Learner, Match');
    $response->assertDontSee('Student, Other');
});

test('failed staff creation reopens the add staff modal with validation errors', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.users.modal',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.users'))
        ->followingRedirects()
        ->post(route('admin.users.store'), [
            '_form' => 'add_staff',
            'first_name' => 'Maria',
        ]);

    $response->assertOk();
    $response->assertSee('Please fix the highlighted fields before creating this account.');
    $response->assertSee('data-open="true"', false);
    $response->assertSee('value="Maria"', false);
});

test('admin can create a staff user with a suffix and a birthdate in the allowed years', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.create',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        '_form' => 'add_staff',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'suffix' => 'Jr.',
        'birthdate' => '1990-05-20',
        'username' => 'maria.reyes',
        'email' => 'maria.reyes@anhs.local',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'role' => 'teacher',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $staff = Staff::query()->where('username', 'maria.reyes')->first();

    expect($staff)->not->toBeNull()
        ->and($staff->email)->toBe('maria.reyes@anhs.local')
        ->and($staff->role_id)->toBe($teacherRole->id)
        ->and($staff->suffix)->toBe('Jr.')
        ->and($staff->birthdate?->format('Y-m-d'))->toBe('1990-05-20')
        ->and(Hash::check('NewPassword123!', $staff->password))->toBeTrue();
});

test('staff birthdates outside 1925 to 2020 are rejected', function (string $birthdate) {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.birthdate',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.users'))->post(route('admin.users.store'), [
        '_form' => 'add_staff',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'birthdate' => $birthdate,
        'username' => 'maria.reyes.birthdate',
        'email' => 'maria.reyes.birthdate@anhs.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHasErrors('birthdate');
})->with([
    'too early' => '1924-12-31',
    'too late' => '2021-01-01',
]);

test('inactive users are hidden unless the inactive status filter is selected', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.status',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'present.teacher',
        'password' => Hash::make('password'),
        'first_name' => 'Present',
        'last_name' => 'Faculty',
        'status' => 'active',
    ]);

    Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'removed.teacher',
        'password' => Hash::make('password'),
        'first_name' => 'Removed',
        'last_name' => 'Faculty',
        'status' => 'inactive',
    ]);

    $defaultList = $this->actingAs($admin)->get(route('admin.users'));
    $defaultList->assertOk();
    $defaultList->assertSee('Faculty, Present');
    $defaultList->assertSee('present.teacher');
    $defaultList->assertDontSee('Faculty, Removed');
    $defaultList->assertDontSee('removed.teacher');

    $inactiveList = $this->actingAs($admin)->get(route('admin.users', ['status' => 'inactive']));
    $inactiveList->assertOk();
    $inactiveList->assertSee('Faculty, Removed');
    $inactiveList->assertSee('removed.teacher');
    $inactiveList->assertDontSee('Faculty, Present');
    $inactiveList->assertDontSee('present.teacher');
});

test('staff creation requires a birthdate', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.birthdate.required',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.users'))->post(route('admin.users.store'), [
        '_form' => 'add_staff',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'username' => 'maria.reyes.required',
        'email' => 'maria.reyes.required@anhs.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHasErrors('birthdate');
    $this->assertDatabaseMissing('staffs', ['username' => 'maria.reyes.required']);
});

test('staff creation is rejected when the password confirmation does not match', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.password.mismatch',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.users'))
        ->followingRedirects()
        ->post(route('admin.users.store'), [
            '_form' => 'add_staff',
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'birthdate' => '1990-05-20',
            'username' => 'maria.reyes.mismatch',
            'email' => 'maria.reyes.mismatch@anhs.local',
            'password' => 'password123',
            'password_confirmation' => 'password456',
            'role' => 'teacher',
        ]);

    $response->assertOk();
    $response->assertSee('The confirm password must match the password.');
    $response->assertSee('data-open="true"', false);
    $this->assertDatabaseMissing('staffs', ['username' => 'maria.reyes.mismatch']);
});

test('admin cannot delete users from the users page', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.users.delete.removed',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)->delete('/admin/users/'.$admin->staff_id)->assertMethodNotAllowed();
});

test('staff creation is rejected when the password does not meet the strong password policy', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.password.weak.create',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.users'))->post(route('admin.users.store'), [
        '_form' => 'add_staff',
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'birthdate' => '1990-05-20',
        'username' => 'maria.reyes.weak',
        'email' => 'maria.reyes.weak@anhs.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHasErrors('password');
    $this->assertDatabaseMissing('staffs', ['username' => 'maria.reyes.weak']);
});

test('admin can update a staff user password that meets the strong password policy', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.password.update',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.password.update',
        'email' => 'teacher.password.update@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'Present',
        'last_name' => 'Faculty',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $teacher), [
        'username' => $teacher->username,
        'email' => $teacher->email,
        'role' => 'teacher',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Hash::check('NewPassword123!', $teacher->fresh()->password))->toBeTrue();
});

test('staff password updates are rejected when the password does not meet the strong password policy', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.password.weak.update',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.password.weak',
        'email' => 'teacher.password.weak@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'Present',
        'last_name' => 'Faculty',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.users'))->put(route('admin.users.update', $teacher), [
        'username' => $teacher->username,
        'email' => $teacher->email,
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHasErrors('password');
    expect(Hash::check('password', $teacher->fresh()->password))->toBeTrue();
});

test('admin can update a student password that meets the strong password policy', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.student.password.update',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
    $student = Student::query()->create([
        'username' => 'student.password.update',
        'email' => 'student.password.update@anhs.local',
        'password' => Hash::make('password'),
        'lrn' => '123456789088',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $student), [
        'is_student' => '1',
        'email' => $student->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Hash::check('NewPassword123!', $student->fresh()->password))->toBeTrue();
});

test('student password updates are rejected when the password does not meet the strong password policy', function () {
    $adminRole = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $adminRole->id,
        'username' => 'admin.users.student.password.weak',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
    $student = Student::query()->create([
        'username' => 'student.password.weak',
        'email' => 'student.password.weak@anhs.local',
        'password' => Hash::make('password'),
        'lrn' => '123456789077',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.users'))->put(route('admin.users.update', $student), [
        'is_student' => '1',
        'email' => $student->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHasErrors('password');
    expect(Hash::check('password', $student->fresh()->password))->toBeTrue();
});
