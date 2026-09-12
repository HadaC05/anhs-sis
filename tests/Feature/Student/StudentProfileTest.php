<?php

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use Illuminate\Support\Facades\Hash;

function createStudentProfileUser(array $overrides = []): Student
{
    return Student::query()->create(array_merge([
        'username' => 'student.profile',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'middle_name' => 'Cruz',
        'last_name' => 'Santos',
        'suffix' => 'Jr.',
        'email' => 'ana.santos@example.com',
        'contact_no' => '+639123456789',
        'sex' => 'female',
        'birthdate' => '2012-05-01',
        'birthplace' => 'Butuan City',
        'mother_tongue' => 'Cebuano',
        'religion' => 'Catholic',
        'status' => 'active',
    ], $overrides));
}

function studentProfilePayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'ana.updated@example.com',
        'contact_no' => '+639987654321',
        'gender' => 'Female',
        'birthdate' => '2012-05-01',
        'birthplace' => 'Cabadbaran City',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'ip_community' => 'No',
        'four_ps_beneficiary' => 'No',
        'pwd' => 'No',
        'curr_barangay' => 'Doongan',
        'curr_municipality_city' => 'Butuan City',
        'curr_province' => 'Agusan del Norte',
        'curr_country' => 'Philippines',
        'curr_zip_code' => '8600',
        'perm_barangay' => 'Doongan',
        'perm_municipality_city' => 'Butuan City',
        'perm_province' => 'Agusan del Norte',
        'perm_country' => 'Philippines',
        'perm_zip_code' => '8600',
        'same_address' => '1',
        'father_lname' => 'Santos',
        'father_fname' => 'Pedro',
        'mother_lname' => 'Cruz',
        'mother_fname' => 'Maria',
    ], $overrides);
}

test('student can view student profile with grades page design', function () {
    $student = createStudentProfileUser();

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($student)->get(route('student.profile'));

    $response->assertOk();
    $response->assertSee('Student Profile');
    $response->assertDontSee('My Information');
    $response->assertSee('Save Changes');
    $response->assertSee('You can update your contact and family details. Your name cannot be changed here.');
    $response->assertSee('readonly', false);
    $response->assertSee('name="contact_no"', false);
    $response->assertSee('id="curr_province_select"', false);
    $response->assertSee('id="curr_municipality_city_select"', false);
    $response->assertSee('id="curr_barangay_select"', false);
    $response->assertSee('id="perm_province_select"', false);
    $response->assertSee('id="perm_municipality_city_select"', false);
    $response->assertSee('id="perm_barangay_select"', false);
    $response->assertSee('addresspinas', false);
    $response->assertDontSee('<input type="text" name="curr_province"', false);
    $response->assertDontSee('<input type="text" name="curr_municipality_city"', false);
    $response->assertDontSee('<input type="text" name="curr_barangay"', false);
    $response->assertDontSee('name="first_name"', false);
    $response->assertDontSee('name="last_name"', false);
});

test('incomplete student profiles show a completion prompt and cannot open other portal areas', function () {
    $student = createStudentProfileUser([
        'username' => 'student.incomplete.profile',
        'lrn' => '123456789099',
        'contact_no' => null,
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Complete your student profile')
        ->assertSee('Update Student Profile');

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertRedirect(route('student.profile'))
        ->assertSessionHas('profile_completion_required');
});

test('a saved complete student profile unlocks the rest of the portal', function () {
    $student = createStudentProfileUser([
        'username' => 'student.complete.profile',
        'lrn' => '123456789098',
    ]);

    $this->actingAs($student)->put(route('student.profile.update'), studentProfilePayload())
        ->assertSessionHas('status', 'Student profile updated.');

    expect($student->fresh()->hasCompleteProfile())->toBeTrue();

    $this->actingAs($student)
        ->get(route('student.documents'))
        ->assertOk();
});

test('student profile prefills cascading address fields from saved addresses', function () {
    $student = createStudentProfileUser();

    foreach (['current', 'permanent'] as $type) {
        StudentAddress::query()->create([
            'student_ID' => $student->id,
            'address_type' => $type,
            'house_no' => '12',
            'street_name' => 'Rizal Street',
            'barangay' => 'Doongan',
            'municipality' => 'Butuan City',
            'province' => 'Agusan del Norte',
            'country' => 'Philippines',
            'zip_code' => '8600',
        ]);
    }

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $response = $this->actingAs($student)->get(route('student.profile'));

    $response->assertOk();
    $response->assertSee('name="curr_province" value="Agusan del Norte"', false);
    $response->assertSee('name="curr_municipality_city" value="Butuan City"', false);
    $response->assertSee('name="curr_barangay" value="Doongan"', false);
    $response->assertSee('name="perm_province" value="Agusan del Norte"', false);
    $response->assertSee('id="same_address" type="checkbox" name="same_address" value="1" class="h-4 w-4 accent-[#296374]" checked', false);
});

test('student enrollment page is removed in favor of student profile', function () {
    $student = createStudentProfileUser([
        'username' => 'student.dashboard',
        'lrn' => '123456789013',
        'first_name' => 'Ben',
        'last_name' => 'Cruz',
        'email' => 'ben.cruz@example.com',
    ]);

    AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $this->actingAs($student)->get('/student/enrollment')->assertNotFound();

    $dashboard = $this->actingAs($student)->get(route('student.dashboard'));
    $dashboard->assertOk();
    $dashboard->assertDontSee('>Enrollment</span>', false);
    $dashboard->assertSee('Student Profile');
    $dashboard->assertSee(route('student.profile'), false);
});

test('student can update profile details except name', function () {
    $student = createStudentProfileUser();

    $response = $this->actingAs($student)->put(route('student.profile.update'), studentProfilePayload([
        'first_name' => 'Hacked',
        'last_name' => 'Name',
        'religion' => 'Islam',
        'father_suffix' => 'Jr.',
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Student profile updated.');

    $student->refresh();

    expect($student->first_name)->toBe('Ana');
    expect($student->last_name)->toBe('Santos');
    expect($student->contact_no)->toBe('+639987654321');
    expect($student->birthplace)->toBe('Cabadbaran City');
    expect($student->religion)->toBe('Islam');
    expect($student->email)->toBe('ana.updated@example.com');
    expect($student->mother_tongue)->toBe('Cebuano');

    $address = StudentAddress::query()->where('student_ID', $student->id)->where('address_type', 'current')->first();
    expect($address?->barangay)->toBe('Doongan');

    $father = StudentGuardian::query()->where('student_ID', $student->id)->where('relationship', 'father')->first();
    expect($father?->first_name)->toBe('Pedro');
    expect($father?->last_name)->toBe('Santos');
    expect($father?->suffix)->toBe('Jr.');
});

test('student profile update rejects text that contains angle brackets', function () {
    $student = createStudentProfileUser();

    $response = $this->actingAs($student)->from(route('student.profile'))->put(route('student.profile.update'), studentProfilePayload([
        'birthplace' => 'Cabadbaran <City>',
        'mother_tongue' => 'Cebuano',
    ]));

    $response->assertRedirect(route('student.profile'));
    $response->assertSessionHasErrors(['birthplace']);
});

test('student can mark a parent as deceased from profile', function () {
    $student = createStudentProfileUser();

    $response = $this->actingAs($student)->put(route('student.profile.update'), studentProfilePayload([
        'father_is_deceased' => '1',
        'father_contact_no' => '+639111111111',
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Student profile updated.');

    $father = StudentGuardian::query()->where('student_ID', $student->id)->where('relationship', 'father')->first();

    expect($father?->is_deceased)->toBeTrue()
        ->and($father?->contact_no)->toBeNull();
});

test('student profile does not show deceased status in the contact number field', function () {
    $student = createStudentProfileUser();

    StudentGuardian::query()->create([
        'student_ID' => $student->id,
        'relationship' => 'father',
        'first_name' => 'Pedro',
        'last_name' => 'Santos',
        'contact_no' => 'Deceased',
        'is_deceased' => true,
    ]);

    $response = $this->actingAs($student)->get(route('student.profile'));

    $response->assertOk();
    $response->assertSee('name="father_is_deceased"', false);
    $response->assertDontSee('value="Deceased"', false);
});

test('student profile update rejects a 4ps household id that is too short', function () {
    $student = createStudentProfileUser();

    $response = $this->actingAs($student)->from(route('student.profile'))->put(route('student.profile.update'), studentProfilePayload([
        'four_ps_beneficiary' => 'Yes',
        'four_ps_details' => str_repeat('1', 16),
    ]));

    $response->assertRedirect(route('student.profile'));
    $response->assertSessionHasErrors(['four_ps_details']);
});
