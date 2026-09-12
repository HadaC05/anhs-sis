<?php

use App\Models\Student;
use App\Models\StudentApplication;

test('application status form limits LRN to numbers and birthdate from 1950 through 2016', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('id="status_lrn"', false)
        ->assertSee('inputmode="numeric"', false)
        ->assertSee('pattern="\d{12}"', false)
        ->assertSee('maxlength="12"', false)
        ->assertSee("this.value.replace(/\\D/g, '').slice(0, 12)", false)
        ->assertSee('min="'.StudentApplication::EARLIEST_BIRTHDATE.'"', false)
        ->assertSee('max="'.StudentApplication::LATEST_BIRTHDATE.'"', false);
})->with([
    'login page' => 'login',
    'home page' => 'home',
]);

test('application status rejects a non-numeric or non-12-digit LRN', function (string $lrn) {
    $response = $this->from(route('login'))->post(route('applications.status'), [
        'status_lrn' => $lrn,
        'status_birthdate' => '2012-05-01',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrorsIn('statusCheck', [
        'status_lrn' => 'The LRN must be 12 digits.',
    ]);
})->with([
    'letters' => 'abcdefghijkl',
    'mixed' => '12345678901a',
    'too short' => '12345678901',
    'too long' => '1234567890123',
]);

test('application status rejects a birthdate outside 1950 through 2016', function (string $birthdate, string $message) {
    $response = $this->from(route('login'))->post(route('applications.status'), [
        'status_lrn' => '123456789012',
        'status_birthdate' => $birthdate,
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrorsIn('statusCheck', [
        'status_birthdate' => $message,
    ]);
})->with([
    'before 1950' => ['1949-12-31', 'The birthdate must be on or after January 1, 1950.'],
    'after 2016' => ['2017-01-01', 'The birthdate must be on or before December 31, 2016.'],
]);

test('application status accepts a numeric LRN and a birthdate within 1950 through 2016', function (string $birthdate) {
    $response = $this->from(route('login'))->post(route('applications.status'), [
        'status_lrn' => '123456789012',
        'status_birthdate' => $birthdate,
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionDoesntHaveErrors(['status_lrn', 'status_birthdate'], errorBag: 'statusCheck');
    $response->assertSessionHasErrorsIn('statusCheck', [
        'status_lookup' => 'No application found for the provided details.',
    ]);
})->with([
    'earliest' => StudentApplication::EARLIEST_BIRTHDATE,
    'latest' => StudentApplication::LATEST_BIRTHDATE,
    'typical' => '2012-05-01',
]);

test('application status returns the matching application message', function () {
    Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'birthdate' => '2012-05-01',
        'status' => 'pending',
    ]);

    $response = $this->from(route('login'))->post(route('applications.status'), [
        'status_lrn' => '123456789012',
        'status_birthdate' => '2012-05-01',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('application_status_message', 'Your application is pending. Please wait for verification.');
    $response->assertSessionDoesntHaveErrors(errorBag: 'statusCheck');
});

test('application status returns temporarily enrolled login instructions', function () {
    $academicYear = \App\Models\AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $gradeLevel = \App\Models\GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $student = Student::query()->create([
        'lrn' => '123456789014',
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'birthdate' => '2012-05-01',
        'email' => 'juan.status@example.com',
        'status' => 'approved',
    ]);

    \App\Models\Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $response = $this->from(route('login'))->post(route('applications.status'), [
        'status_lrn' => '123456789014',
        'status_birthdate' => '2012-05-01',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('application_status_message', 'You are temporarily enrolled. Sign in to upload your required documents, then change your password after your first sign in.');
    $response->assertSessionHas('application_status_enrollment_year', 2026);
    $response->assertSessionDoesntHaveErrors(errorBag: 'statusCheck');
});
