<?php

use App\Models\Role;
use App\Models\Specialization;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

test('managers can select and update teacher specializations', function (string $managerRole) {
    $manager = Staff::query()->create([
        'role_id' => Role::query()->create(['role_name' => $managerRole])->id,
        'username' => 'manager', 'password' => 'Password123!',
        'first_name' => 'School', 'last_name' => 'Manager', 'status' => 'active',
    ]);
    Role::query()->create(['role_name' => 'teacher']);
    $math = Specialization::query()->where('name', 'Mathematics')->firstOrFail();
    $science = Specialization::query()->where('name', 'Science')->firstOrFail();
    $this->actingAs($manager)->get(route($managerRole.'.users'))
        ->assertOk()->assertSee('add_specialization_id')->assertSee('edit_specialization_id')->assertSee('Mathematics');

    $payload = [
        'first_name' => 'Sample', 'last_name' => 'Teacher', 'birthdate' => '1990-05-20',
        'username' => 'teacher', 'email' => 'teacher@example.com', 'role' => 'teacher',
        'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        'specialization_id' => $math->id,
    ];
    $this->post(route($managerRole.'.users.store'), $payload)->assertSessionHasNoErrors();
    $teacher = Staff::query()->where('username', 'teacher')->firstOrFail();
    expect($teacher->specialization->name)->toBe('Mathematics')
        ->and($teacher->major_specialization)->toBe('Mathematics');

    $this->put(route($managerRole.'.users.update', $teacher), array_merge($payload, [
        'specialization_id' => $science->id,
    ]))->assertSessionHasNoErrors();
    expect($teacher->fresh()->specialization->name)->toBe('Science');

    $this->put(route($managerRole.'.users.update', $teacher), array_merge($payload, [
        'specialization_id' => 999999,
    ]))->assertSessionHasErrors('specialization_id');
    expect($teacher->fresh()->specialization->name)->toBe('Science');

    $this->post(route($managerRole.'.users.store'), array_merge($payload, [
        'username' => 'invalid.teacher', 'email' => 'invalid@example.com', 'specialization_id' => 999999,
    ]))->assertSessionHasErrors('specialization_id');

    $this->post(route($managerRole.'.users.store'), array_merge($payload, [
        'username' => 'other.staff', 'email' => 'other@example.com', 'role' => $managerRole,
    ]))->assertSessionHasNoErrors();
    expect(Staff::query()->where('username', 'other.staff')->firstOrFail()->specialization_id)->toBeNull();

    $this->put(route($managerRole.'.users.update', $teacher), array_merge($payload, [
        'specialization_id' => null,
    ]))->assertSessionHasNoErrors();
    expect($teacher->fresh()->specialization_id)->toBeNull()
        ->and($teacher->fresh()->major_specialization)->toBeNull();
})->with(['admin', 'principal']);

test('specialization migration preserves and links existing staff values', function () {
    $migration = require database_path('migrations/2026_10_03_000001_create_specializations_table.php');
    $migration->down();
    $id = DB::table('staffs')->insertGetId([
        'username' => 'legacy.teacher', 'password' => 'unused',
        'first_name' => 'Legacy', 'last_name' => 'Teacher',
        'major_specialization' => '  Custom Major  ',
    ], 'staff_id');
    $migration->up();
    $teacher = Staff::query()->findOrFail($id);
    expect($teacher->major_specialization)->toBe('  Custom Major  ')
        ->and($teacher->specialization->name)->toBe('Custom Major');
});
