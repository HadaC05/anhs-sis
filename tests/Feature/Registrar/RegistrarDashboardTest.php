<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

test('registrar can view the enrollment dashboard', function () {
    $role = Role::query()->create(['role_name' => 'registrar']);
    $registrar = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'registrar.dashboard',
        'password' => Hash::make('password'),
        'first_name' => 'Reg',
        'last_name' => 'istrar',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($registrar)->get(route('registrar.dashboard'));

    $response->assertOk();
    $response->assertSee('Registrar Dashboard');
    $response->assertSee('Total Enrollees');
    $response->assertSee('Officially Enrolled');
    $response->assertSee('Temporarily Enrolled');
    $response->assertSee('Transferees');
    $response->assertSee('Balik Aral');
    $response->assertSee('Gender Ratio');
    $response->assertSee('Cluster Distribution');
    $response->assertSee('Enrollment by Grade Level');
    $response->assertSee('Age Alignment');
    $response->assertDontSee('Recent Applications');
    $response->assertDontSee('pending review');
    $response->assertDontSee('marked for placement test');
});
