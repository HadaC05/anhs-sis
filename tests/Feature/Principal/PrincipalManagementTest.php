<?php

use App\Models\AcademicYear;
use App\Models\MovementReason;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;

beforeEach(function () {
    $this->principal = Staff::query()->create([
        'username' => fake()->unique()->userName(), 'password' => 'password',
        'first_name' => 'School', 'last_name' => 'Staff',
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'principal'])->id,
        'status' => 'active',
        'change_password' => false,
    ]);
});

test('principal management pages use principal navigation and actions', function (string $page) {
    $this->actingAs($this->principal)->get(route('principal.'.$page))
        ->assertOk()
        ->assertSee('id="principal-sidebar"', false)
        ->assertSeeInOrder(['>Academic Records</p>', '>Manage</p>', '>Reports</p>'], false)
        ->assertSee(route('principal.users'), false)
        ->assertDontSee('/admin/', false);
})->with([
    'users', 'promotions.index', 'academic-year-config.index', 'attendance-config.index',
    'section-config.index', 'grading-term-config.index', 'movement-reason-config.index',
    'document-return-reason-config.index', 'curriculum-config.index',
    'subject-config.index', 'teacher-assignments.index',
]);

test('principal can list staff and student accounts and edit students', function () {
    $student = Student::query()->create(['first_name' => 'Test', 'last_name' => 'Student', 'password' => 'password', 'lrn' => '123456789012', 'status' => 'active', 'username' => 'student.manage']);

    $this->actingAs($this->principal)->get(route('principal.users'))
        ->assertOk()->assertSee($this->principal->username);
    $this->get(route('principal.users', ['tab' => 'students']))
        ->assertOk()->assertSee('student.manage')->assertDontSee('/admin/', false);
    $this->put(route('principal.users.update', $student->getKey()), [
        'is_student' => true,
        'email' => 'updated@example.com',
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->email)->toBe('updated@example.com');
});

test('principal can create update and delete school configuration', function () {
    $this->actingAs($this->principal)->from(route('principal.movement-reason-config.index'))
        ->post(route('principal.movement-reason-config.store'), ['name' => 'Transfer requested'])
        ->assertSessionHasNoErrors()->assertRedirect(route('principal.movement-reason-config.index'));
    $reason = MovementReason::query()->where('name', 'Transfer requested')->firstOrFail();
    $this->put(route('principal.movement-reason-config.update', $reason), ['name' => 'Family relocation'])
        ->assertSessionHasNoErrors();
    expect($reason->fresh()->name)->toBe('Family relocation');
    $this->delete(route('principal.movement-reason-config.delete', $reason))->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('movement_reasons', ['reason_ID' => $reason->getKey()]);
});

test('principal attendance saves return to the principal portal', function () {
    $year = AcademicYear::query()->create(['school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true]);
    $this->actingAs($this->principal)->put(route('principal.attendance-config.update'), [
        'SY_ID' => $year->SY_ID,
        'school_days' => array_fill(1, 12, 20),
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('principal.attendance-config.index', ['sy_id' => $year->SY_ID]));
    $this->assertDatabaseHas('academic_year_attendance_settings', [
        'SY_ID' => $year->SY_ID, 'month' => 1, 'school_days' => 20,
    ]);
});

test('other roles cannot read or change principal management', function (string $role) {
    $staff = Staff::query()->create([
        'username' => fake()->unique()->userName(), 'password' => 'password',
        'first_name' => 'School', 'last_name' => 'Staff',
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'status' => 'active', 'change_password' => false,
    ]);
    $this->actingAs($staff)->get(route('principal.users'))->assertForbidden();
    $this->post(route('principal.movement-reason-config.store'), ['name' => 'Unauthorized'])
        ->assertForbidden();
    $this->assertDatabaseMissing('movement_reasons', ['name' => 'Unauthorized']);
})->with(['teacher', 'registrar', 'guidance counselor', 'admin']);

test('principal management does not grant access to admin routes', function () {
    $this->actingAs($this->principal)->get(route('admin.users'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertForbidden();
});
