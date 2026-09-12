<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guidance counselor can view the enrollment dashboard', function () {
    $role = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $role->id,
        'username' => 'guidance.dashboard',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($user)->get(route('guidance.dashboard'));

    $response->assertOk();
    $response->assertSee('Guidance Dashboard');
    $response->assertSee('Total Enrollees');
    $response->assertSee('Officially Enrolled');
    $response->assertSee('Temporarily Enrolled');
    $response->assertSee('Transferees');
    $response->assertSee('Balik Aral');
    $response->assertSee('Gender Ratio');
    $response->assertSee('Cluster Distribution');
    $response->assertSee('Enrollment by Grade Level');
    $response->assertSee('Age Alignment');
    $response->assertSee('Open full report');
});
