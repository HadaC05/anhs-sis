<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\StudentGuardian;

function createStudentRegistrationFixtures(): array
{
    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    return compact('academicYear', 'gradeLevel');
}

function studentRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'grade_level' => '7',
        'LRN' => '123456789012',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan Elementary School',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'birthdate' => '2012-05-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Male',
        'contact_no' => '+639123456789',
        'email' => 'juan.cruz@example.com',
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
        'father_lname' => 'Cruz',
        'father_fname' => 'Pedro',
        'mother_lname' => 'Santos',
        'mother_fname' => 'Maria',
    ], $overrides);
}

test('registration form uses educational background for last school attended', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('Student Registration');
    $response->assertSee('Educational Background');
    $response->assertSee('name="last_school_attended"', false);
    expect(substr_count($response->getContent(), 'name="last_school_attended"'))->toBe(1);
});

test('registration form uses a suffix dropdown in personal information', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('<select name="suffix"', false);
    $response->assertSee('<option value="">None</option>', false);

    foreach (StudentApplication::suffixOptions() as $suffix) {
        $response->assertSee('<option value="'.$suffix.'"', false);
    }
});

test('registration accepts a selected suffix', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '444444444444',
        'suffix' => 'Jr.',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '444444444444')->first();
    expect($student?->suffix)->toBe('Jr.');
});

test('registration rejects an invalid suffix', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'suffix' => 'PhD',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['suffix']);
});

test('registration form restricts birthdate from 1950 onward', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('min="'.StudentApplication::EARLIEST_BIRTHDATE.'"', false);
});

test('registration rejects a birthdate before 1950', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'birthdate' => '1111-01-01',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['birthdate']);
});

test('registration accepts a birthdate on January 1 1950', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '555555555555',
        'birthdate' => StudentApplication::EARLIEST_BIRTHDATE,
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '555555555555')->first();
    expect($student?->birthdate?->format('Y-m-d'))->toBe(StudentApplication::EARLIEST_BIRTHDATE);
});

test('lrn availability endpoint reports available and taken lrns', function () {
    Student::query()->create([
        'lrn' => '999999999999',
        'first_name' => 'Existing',
        'last_name' => 'Learner',
        'status' => 'pending',
    ]);

    $this->getJson(route('register.check-lrn', ['LRN' => '111111111111']))
        ->assertOk()
        ->assertJson([
            'available' => true,
            'message' => null,
        ]);

    $this->getJson(route('register.check-lrn', ['LRN' => '999999999999']))
        ->assertOk()
        ->assertJson([
            'available' => false,
            'message' => 'This LRN is already registered.',
        ]);
});

test('lrn availability endpoint requires twelve digits', function () {
    $this->getJson(route('register.check-lrn', ['LRN' => '12345']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['LRN']);
});

test('email availability endpoint reports available and taken emails', function () {
    Student::query()->create([
        'lrn' => '999999999998',
        'first_name' => 'Existing',
        'last_name' => 'Learner',
        'email' => 'taken@example.com',
        'status' => 'pending',
    ]);

    $this->getJson(route('register.check-email', ['email' => 'available@example.com']))
        ->assertOk()
        ->assertJson([
            'available' => true,
            'message' => null,
        ]);

    $this->getJson(route('register.check-email', ['email' => 'taken@example.com']))
        ->assertOk()
        ->assertJson([
            'available' => false,
            'message' => 'This email is already registered.',
        ]);
});

test('email availability endpoint requires a valid email', function () {
    $this->getJson(route('register.check-email', ['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration requires educational background last school attended', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'last_school_attended' => '',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['last_school_attended']);
});

test('registration accepts last school attended from educational background for transferees', function () {
    createStudentRegistrationFixtures();
    $gradeEight = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 8'],
        ['category' => 'Junior High School']
    );

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'grade_level' => '8',
        'learner_type' => 'transferee',
        'last_grade_level_completed' => '7',
        'last_school_year_completed_start' => '2025',
        'last_school_year_completed_end' => '2026',
        'school_id_from_previous_school' => '123456',
        'last_school_attended' => 'Previous High School',
        'LRN' => '222222222222',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '222222222222')->first();
    expect($student)->not->toBeNull();

    $enrollment = Enrollment::query()->where('student_ID', $student->id)->first();
    expect($enrollment)->not->toBeNull();
    expect($enrollment->last_school_attended)->toBe('Previous High School');
    expect($enrollment->learner_type)->toBe('transferee');
    expect($enrollment->last_grade_level_completed)->toBe('7');
    expect($enrollment->grade_ID)->toBe($gradeEight->grade_ID);
});

test('registration form includes grade 6 in last grade level completed options only for grade 7', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('<option value="6" data-show-for-grade="7"', false);
    $response->assertSee('Grade 6');
});

test('registration rejects last grade level completed that is not lower than the enrollment grade', function (string $learnerType, string $gradeLevel, string $lastCompleted) {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'grade_level' => $gradeLevel,
        'learner_type' => $learnerType,
        'last_grade_level_completed' => $lastCompleted,
        'last_school_year_completed_start' => '2025',
        'last_school_year_completed_end' => '2026',
        'school_id_from_previous_school' => '123456',
        'LRN' => '232323232323',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['last_grade_level_completed']);
})->with([
    'transferee same grade' => ['transferee', '8', '8'],
    'returnee same grade' => ['returnee', '7', '7'],
    'balik aral same grade' => ['balik_aral', '7', '7'],
    'transferee higher grade' => ['transferee', '8', '9'],
    'transferee grade 6 for grade 8' => ['transferee', '8', '6'],
    'returnee grade 6 for grade 9' => ['returnee', '9', '6'],
    'balik aral grade 6 for grade 9' => ['balik_aral', '9', '6'],
]);

test('registration accepts grade 6 as last grade completed for grade 7 transferees', function () {
    ['gradeLevel' => $gradeLevel] = createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'grade_level' => '7',
        'learner_type' => 'transferee',
        'last_grade_level_completed' => '6',
        'last_school_year_completed_start' => '2025',
        'last_school_year_completed_end' => '2026',
        'school_id_from_previous_school' => '123456',
        'LRN' => '242424242424',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '242424242424')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student->id)->first();

    expect($enrollment->last_grade_level_completed)->toBe('6');
    expect($enrollment->grade_ID)->toBe($gradeLevel->grade_ID);
});

test('registration stores returnee applications as balik aral', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'grade_level' => '7',
        'learner_type' => 'returnee',
        'last_grade_level_completed' => '6',
        'last_school_year_completed_start' => '2025',
        'last_school_year_completed_end' => '2026',
        'school_id_from_previous_school' => '123456',
        'LRN' => '262626262626',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '262626262626')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student->id)->first();

    expect($enrollment->learner_type)->toBe('balik_aral')
        ->and($enrollment->learner_type_label)->toBe('Balik Aral')
        ->and($enrollment->last_grade_level_completed)->toBe('6');
});

test('registration accepts balik aral learner type', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'grade_level' => '7',
        'learner_type' => 'balik_aral',
        'last_grade_level_completed' => '6',
        'last_school_year_completed_start' => '2025',
        'last_school_year_completed_end' => '2026',
        'school_id_from_previous_school' => '123456',
        'LRN' => '272727272727',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '272727272727')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student->id)->first();

    expect($enrollment->learner_type)->toBe('balik_aral');
});

test('registration form shows an email input beside contact number in personal information', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="contact_no"', false);
    $response->assertSee('name="email"', false);
    $response->assertSee('type="email"', false);
    $response->assertSee('autocomplete="email"', false);
});

test('registration form keeps parent and guardian contact numbers optional', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));
    $content = $response->getContent();

    $response->assertOk();
    expect($content)
        ->toMatch('/<input type="tel" name="contact_no"[^>]*\srequired/')
        ->toMatch('/<input type="tel" name="father_contact_no"[^>]*>/')
        ->not->toMatch('/<input type="tel" name="father_contact_no"[^>]*\srequired/')
        ->not->toMatch('/<input type="tel" name="mother_contact_no"[^>]*\srequired/')
        ->not->toMatch('/<input type="tel" name="guardian_contact_no"[^>]*\srequired/')
        ->not->toMatch('/<input type="tel" name="father_contact_no"[^>]*pattern=/')
        ->not->toMatch('/<input type="tel" name="mother_contact_no"[^>]*pattern=/')
        ->not->toMatch('/<input type="tel" name="guardian_contact_no"[^>]*pattern=/');
});

test('registration requires an email address', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'email' => '',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['email']);
});

test('registration keeps birthplace sex and mother tongue after a failed submission', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'email' => '',
        'birthplace' => 'Nasipit Agusan',
        'gender' => 'Female',
        'mother_tongue' => 'Surigaonon',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['email']);
    $response->assertSessionHasInput([
        'birthplace' => 'Nasipit Agusan',
        'gender' => 'Female',
        'mother_tongue' => 'Surigaonon',
    ]);

    $this->followRedirects($response)
        ->assertOk()
        ->assertSee('value="Nasipit Agusan"', false)
        ->assertSee('value="Surigaonon"', false)
        ->assertSee('name="gender" value="Female"', false)
        ->assertSee('value="Female" class="mr-2 accent-[#296374]" checked', false);
});

test('registration accepts an email address', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '121212121212',
        'email' => 'juan.cruz@example.com',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '121212121212')->first();
    expect($student?->email)->toBe('juan.cruz@example.com');
});

test('registration capitalizes each word in student parent guardian names birthplace mother tongue and last school', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '252525252525',
        'first_name' => 'juan miguel',
        'middle_name' => 'dela',
        'last_name' => 'cruz santos',
        'birthplace' => 'butuan city',
        'mother_tongue' => 'cebuano',
        'last_school_attended' => 'agusan elementary school',
        'father_lname' => 'cruz',
        'father_fname' => 'pedro jose',
        'father_mname' => 'santos',
        'mother_lname' => 'reyes',
        'mother_fname' => 'maria clara',
        'mother_mname' => 'lopez',
        'guardian_lname' => 'garcia',
        'guardian_fname' => 'jose',
        'guardian_mname' => 'tan',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '252525252525')->first();
    $enrollment = Enrollment::query()->where('student_ID', $student->id)->first();
    $guardians = StudentGuardian::query()->where('student_ID', $student->id)->get()->keyBy('relationship');

    expect($student->first_name)->toBe('Juan Miguel')
        ->and($student->middle_name)->toBe('Dela')
        ->and($student->last_name)->toBe('Cruz Santos')
        ->and($student->birthplace)->toBe('Butuan City')
        ->and($student->mother_tongue)->toBe('Cebuano')
        ->and($enrollment->last_school_attended)->toBe('Agusan Elementary School')
        ->and($guardians['father']->last_name)->toBe('Cruz')
        ->and($guardians['father']->first_name)->toBe('Pedro Jose')
        ->and($guardians['father']->middle_name)->toBe('Santos')
        ->and($guardians['mother']->last_name)->toBe('Reyes')
        ->and($guardians['mother']->first_name)->toBe('Maria Clara')
        ->and($guardians['mother']->middle_name)->toBe('Lopez')
        ->and($guardians['guardian']->last_name)->toBe('Garcia')
        ->and($guardians['guardian']->first_name)->toBe('Jose')
        ->and($guardians['guardian']->middle_name)->toBe('Tan');
});

test('registration rejects an invalid email address', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'email' => 'not-an-email',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['email']);
});

test('registration rejects a duplicate email address', function () {
    createStudentRegistrationFixtures();

    Student::query()->create([
        'lrn' => '101010101010',
        'first_name' => 'Existing',
        'last_name' => 'Learner',
        'email' => 'taken@example.com',
        'status' => 'pending',
    ]);

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'email' => 'taken@example.com',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['email']);
});

test('registration form uses a religion dropdown in personal information', function () {
    createStudentRegistrationFixtures();

    expect(Religion::options()->all())->toBe(Religion::names());

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('<select name="religion"', false);
    $response->assertSee('<option value="">Select Religion</option>', false);

    foreach (Religion::names() as $religion) {
        $response->assertSee($religion);
    }
});

test('registration accepts a selected religion', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '666666666666',
        'religion' => 'Iglesia ni Cristo',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '666666666666')->first();
    expect($student?->religion)->toBe('Iglesia ni Cristo');
});

test('registration rejects an invalid religion', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'religion' => 'Roman Catholic',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['religion']);
});

test('successful registration shows confirmation modal with home and login options', function () {
    createStudentRegistrationFixtures();

    $response = $this->followingRedirects()->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '333333333333',
    ]));

    $response->assertOk();
    $response->assertSee('Enrollment Application Submitted');
    $response->assertSee('Back to Home');
    $response->assertSee('Go to Login');
    $response->assertSee(route('home'), false);
    $response->assertSee(route('login'), false);
});

test('registration form uses suffix dropdowns for parents and guardian', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee("Parent's / Guardian's Information", false);
    $response->assertSee('Enter <span class="font-semibold">N/A</span> in the name fields if not applicable.', false);
    $response->assertSee('<select name="father_suffix"', false);
    $response->assertSee('<select name="mother_suffix"', false);
    $response->assertSee('<select name="guardian_suffix"', false);
});

test('registration form marks optional fields as optional', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));
    $content = $response->getContent();

    $response->assertOk();
    $response->assertSee('<span class="optional-field">(Optional)</span>', false);
    expect($content)
        ->toContain('Middle Name <span class="optional-field">(Optional)</span>')
        ->toContain('Suffix <span class="optional-field">(Optional)</span>')
        ->toContain('House No. <span class="optional-field">(Optional)</span>')
        ->toContain('Street/Sitio <span class="optional-field">(Optional)</span>')
        ->toContain('Guardian\'s Full Name <span class="optional-field">(Optional)</span>')
        ->toContain('Contact No. <span class="optional-field">(Optional)</span>');
});

test('registration requires father and mother names', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'father_lname' => '',
        'father_fname' => '',
        'mother_lname' => '',
        'mother_fname' => '',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['father_lname', 'father_fname', 'mother_lname', 'mother_fname']);
});

test('registration accepts N/A for father and mother names', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '777777777777',
        'father_lname' => 'N/A',
        'father_fname' => 'N/A',
        'mother_lname' => 'N/A',
        'mother_fname' => 'N/A',
        'father_suffix' => 'Jr.',
        'guardian_suffix' => 'III',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '777777777777')->first();
    $guardians = StudentGuardian::query()->where('student_ID', $student->id)->get()->keyBy('relationship');

    expect($guardians['father']->last_name)->toBe('N/A');
    expect($guardians['father']->first_name)->toBe('N/A');
    expect($guardians['father']->suffix)->toBe('Jr.');
    expect($guardians['mother']->last_name)->toBe('N/A');
    expect($guardians['mother']->first_name)->toBe('N/A');
    expect($guardians['guardian']->suffix)->toBe('III');
});

test('registration rejects an invalid parent suffix', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'father_suffix' => 'PhD',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['father_suffix']);
});

test('registration form limits the 4ps household id to 17 through 21 characters', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="four_ps_details"', false);
    $response->assertSee('minlength="17"', false);
    $response->assertSee('maxlength="21"', false);
    $response->assertSee('Must be 17 to 21 characters.');
});

test('registration rejects a 4ps household id outside 17 to 21 characters', function (string $householdId) {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'four_ps_beneficiary' => 'Yes',
        'four_ps_details' => $householdId,
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['four_ps_details']);
})->with([
    'too short' => str_repeat('1', 16),
    'too long' => str_repeat('1', 22),
    'missing' => '',
]);

test('registration accepts a 4ps household id of 17 to 21 characters', function (string $householdId, string $lrn) {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => $lrn,
        'four_ps_beneficiary' => 'Yes',
        'four_ps_details' => $householdId,
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', $lrn)->first();
    expect($student?->profile?->four_ps_household_id)->toBe($householdId);
})->with([
    'minimum' => [str_repeat('A', 17), '888888888817'],
    'maximum' => [str_repeat('B', 21), '888888888821'],
]);

test('registration form shows a readonly age field beside birthdate', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('id="birthdateInput"', false);
    $response->assertSee('id="ageDisplay"', false);
    $response->assertSee('placeholder="Auto-computed"', false);
    $response->assertSee('id="ageDisplay" value="" placeholder="Auto-computed" readonly', false);
});

test('registration form includes deceased checkboxes for parents and guardian', function () {
    createStudentRegistrationFixtures();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="father_is_deceased"', false);
    $response->assertSee('name="mother_is_deceased"', false);
    $response->assertSee('name="guardian_is_deceased"', false);
    $response->assertSee('data-restrict-unsafe-input', false);
});

test('registration rejects text that contains angle brackets', function () {
    createStudentRegistrationFixtures();

    $response = $this->from(route('register'))->post(route('register.store'), studentRegistrationPayload([
        'first_name' => 'Juan<script>',
        'birthplace' => 'Butuan City>',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['first_name', 'birthplace']);
});

test('registration stores deceased parents and clears their contact numbers', function () {
    createStudentRegistrationFixtures();

    $response = $this->post(route('register.store'), studentRegistrationPayload([
        'LRN' => '999999999901',
        'email' => 'deceased.parents@example.com',
        'father_is_deceased' => '1',
        'mother_is_deceased' => '1',
        'father_contact_no' => '+639111111111',
        'mother_contact_no' => '+639222222222',
    ]));

    $response->assertRedirect(route('register'));
    $response->assertSessionHas('registration_submitted', true);

    $student = Student::query()->where('lrn', '999999999901')->first();
    $guardians = StudentGuardian::query()->where('student_ID', $student->id)->get()->keyBy('relationship');

    expect($guardians['father']->is_deceased)->toBeTrue()
        ->and($guardians['father']->contact_no)->toBeNull()
        ->and($guardians['mother']->is_deceased)->toBeTrue()
        ->and($guardians['mother']->contact_no)->toBeNull()
        ->and($guardians['guardian']->is_deceased)->toBeFalse();
});
