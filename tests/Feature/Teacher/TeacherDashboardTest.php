<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

test('teacher can view the student overview dashboard', function () {
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.dashboard',
        'password' => Hash::make('password'),
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

    $response->assertOk();
    $response->assertSee('Teacher Dashboard');
    $response->assertSee('Total Students');
    $response->assertSee('Officially Enrolled');
    $response->assertSee('Temporarily Enrolled');
    $response->assertSee('My Sections');
    $response->assertSee('Gender Ratio');
    $response->assertSee('Students by Section');
    $response->assertSee('Students by Grade Level');
    $response->assertSee('Recent Students');
    $response->assertSeeInOrder([
        '<span class="font-semibold">Advisory</span>',
        '<span class="font-semibold">Subjects</span>',
    ], false);
});
