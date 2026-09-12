<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

test('admin can view the restyled academic year page', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.year',
        'email' => 'admin.academic.year@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    AcademicYear::query()->create([
        'school_year' => '2025-2026',
        'start_date' => '2025-06-02',
        'end_date' => '2026-03-31',
        'status' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.academic-year-config.index'));

    $response->assertOk();
    $response->assertSee('Academic Year');
    $response->assertSee('Add Academic Year');
    $response->assertSee('Current Year');
    $response->assertSee('School Years');
    $response->assertSee('2026-2027');
    $response->assertSee('2025-2026');
    $response->assertSee('Current school year');
    $response->assertSee('>Academic Year</span>', false);
    $response->assertSee('min="'.AcademicYear::EARLIEST_DATE.'"', false);
    $response->assertSee('max="'.AcademicYear::LATEST_DATE.'"', false);
    $response->assertSee('id="academicYearModal"', false);
    $response->assertSee('openAcademicYearModal', false);
    $response->assertSee('id="start_year"', false);
    $response->assertSee('id="end_year"', false);
    $response->assertSee('readonly', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertDontSee('name="school_year"', false);
    $response->assertDontSee('Academic Year Config');
    $response->assertDontSee('Academic Year Configuration');
});

test('admin can filter academic years by search and status', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.filter',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    AcademicYear::query()->create([
        'school_year' => '2024-2025',
        'start_date' => '2024-06-03',
        'end_date' => '2025-03-31',
        'status' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.academic-year-config.index', [
        'search' => '2024',
        'status' => 'inactive',
    ]));

    $response->assertOk();
    $response->assertSee('2024-2025');
    $response->assertDontSee('Current school year');
});

test('admin can create an academic year from coverage dates', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.create',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.academic-year-config.index'))->post(route('admin.academic-year-config.store'), [
        'start_date' => '2028-06-05',
        'end_date' => '2029-03-31',
    ]);

    $response->assertRedirect(route('admin.academic-year-config.index'));

    $academicYear = AcademicYear::query()->where('school_year', '2028-2029')->first();

    expect($academicYear)->not->toBeNull()
        ->and($academicYear->start_date?->toDateString())->toBe('2028-06-05')
        ->and($academicYear->end_date?->toDateString())->toBe('2029-03-31')
        ->and($academicYear->status)->toBeFalse();
});

test('academic year dates must be a sensible coverage period', function (array $payload, array $errors) {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.invalid',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->from(route('admin.academic-year-config.index'))->post(route('admin.academic-year-config.store'), $payload);

    $response->assertRedirect(route('admin.academic-year-config.index'));
    $response->assertSessionHasErrors($errors);
})->with([
    'end before start' => [
        ['start_date' => '2026-06-01', 'end_date' => '2026-05-01'],
        ['end_date'],
    ],
    'more than a year apart' => [
        ['start_date' => '2026-06-01', 'end_date' => '2027-06-02'],
        ['end_date'],
    ],
    'before year 2000' => [
        ['start_date' => '1999-06-01', 'end_date' => '2000-03-31'],
        ['start_date'],
    ],
]);

test('creating an academic year does not activate it or change the current year', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.create.inactive',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $currentYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.academic-year-config.store'), [
        'start_date' => '2028-06-05',
        'end_date' => '2029-03-31',
    ])->assertRedirect();

    expect(AcademicYear::query()->where('school_year', '2028-2029')->first()?->status)->toBeFalse()
        ->and($currentYear->fresh()->status)->toBeTrue()
        ->and(AcademicYear::query()->where('status', true)->count())->toBe(1);
});

test('activating an academic year archives the previously active year', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.activate.one',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $currentYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $nextYear = AcademicYear::query()->create([
        'school_year' => '2028-2029',
        'start_date' => '2028-06-05',
        'end_date' => '2029-03-31',
        'status' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.academic-year-config.index'))
        ->patch(route('admin.academic-year-config.toggle-status', $nextYear))
        ->assertRedirect(route('admin.academic-year-config.index'));

    expect($nextYear->fresh()->status)->toBeTrue()
        ->and($currentYear->fresh()->status)->toBeFalse()
        ->and(AcademicYear::query()->where('status', true)->count())->toBe(1);
});

test('the active academic year is listed first in the table', function () {
    $role = Role::query()->create(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.academic.active.first',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2025-2026',
        'start_date' => '2025-06-02',
        'end_date' => '2026-03-31',
        'status' => true,
    ]);

    AcademicYear::query()->create([
        'school_year' => '2028-2029',
        'start_date' => '2028-06-05',
        'end_date' => '2029-03-31',
        'status' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.academic-year-config.index'))
        ->assertOk()
        ->assertSeeInOrder([
            '>2025-2026</p>',
            '>2028-2029</p>',
        ], false);
});
