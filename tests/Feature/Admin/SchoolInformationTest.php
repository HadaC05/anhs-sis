<?php

use App\Models\Role;
use App\Models\SchoolInformation;
use App\Models\Staff;
use App\Support\LearnerPermanentRecordBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function schoolInformationStaff(string $role): Staff
{
    return Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'school.'.$role,
        'password' => 'password',
        'first_name' => 'School',
        'last_name' => 'Manager',
        'status' => 'active',
    ]);
}

function schoolInformationData(): array
{
    return ['name' => 'Example National High School', 'school_id' => '012345', 'region' => 'Region VII', 'division' => 'Example Division', 'district' => 'Example District'];
}

test('both management portals can save and read the same school profile', function () {
    $admin = schoolInformationStaff('admin');
    $principal = schoolInformationStaff('principal');
    $this->actingAs($admin)->get(route('admin.school-information.edit'))->assertOk()->assertSee('School Information');
    $this->put(route('admin.school-information.update'), schoolInformationData())->assertSessionHasNoErrors();
    $this->actingAs($principal)->get(route('principal.school-information.edit'))->assertOk()->assertSee('Example National High School')->assertSee('012345');
    $this->put(route('principal.school-information.update'), array_replace(schoolInformationData(), ['name' => 'Updated School']))->assertSessionHasNoErrors();
    $this->actingAs($admin)->get(route('admin.school-information.edit'))->assertSee('Updated School');
    $this->assertDatabaseCount('school_information', 1);
    expect(LearnerPermanentRecordBuilder::defaultSchoolMeta())->toBe([
        'name' => 'Updated School', 'id' => '012345', 'district' => 'Example District', 'division' => 'Example Division', 'region' => 'Region VII',
    ]);
});

test('school logos can be uploaded retained replaced and removed', function () {
    Storage::fake('public');
    $this->actingAs(schoolInformationStaff('admin'));
    $url = route('admin.school-information.update');
    $this->put($url, schoolInformationData() + ['logo' => schoolInformationLogo()])->assertSessionHasNoErrors();
    $first = SchoolInformation::current()->logo_path;
    Storage::disk('public')->assertExists($first);
    expect(SchoolInformation::current()->logoDataUri())->toStartWith('data:image/png;base64,');
    $this->put($url, schoolInformationData())->assertSessionHasNoErrors();
    expect(SchoolInformation::current()->logo_path)->toBe($first);
    $this->put($url, schoolInformationData() + ['logo' => schoolInformationLogo()])->assertSessionHasNoErrors();
    $second = SchoolInformation::current()->logo_path;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
    $this->put($url, schoolInformationData() + ['remove_logo' => '1'])->assertSessionHasNoErrors();
    expect(SchoolInformation::current()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('invalid school details and unsafe or oversized logos are rejected', function () {
    Storage::fake('public');
    $this->actingAs(schoolInformationStaff('principal'));
    $url = route('principal.school-information.update');
    $this->put($url, ['name' => '', 'school_id' => 'abc'])->assertSessionHasErrors(['name', 'school_id', 'region', 'division', 'district']);
    foreach ([UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'), schoolInformationLogo()->size(2049)] as $logo) {
        $this->put($url, schoolInformationData() + ['logo' => $logo])->assertSessionHasErrors('logo');
    }
    $this->assertDatabaseCount('school_information', 0);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('other staff cannot view or change school information', function (string $role) {
    $this->actingAs(schoolInformationStaff($role));
    foreach (['admin', 'principal'] as $portal) {
        $this->get(route($portal.'.school-information.edit'))->assertForbidden();
        $this->put(route($portal.'.school-information.update'), schoolInformationData())->assertForbidden();
    }
})->with(['teacher', 'registrar', 'guidance counselor']);

test('guests must sign in to manage school information', function () {
    $this->get(route('admin.school-information.edit'))->assertRedirect(route('login'));
    $this->put(route('principal.school-information.update'), schoolInformationData())->assertRedirect(route('login'));
});

test('save feedback renders outside the portal content stacking context', function (string $portal) {
    $this->actingAs(schoolInformationStaff($portal));
    $page = route($portal.'.school-information.edit');
    $this->from($page)->put(route($portal.'.school-information.update'), schoolInformationData())
        ->assertRedirect($page)->assertSessionHas('success');
    $this->get($page)->assertOk()
        ->assertSeeInOrder(['</main>', 'data-toast-type="success"', 'School information saved successfully.'], false);

    $this->from($page)->put(route($portal.'.school-information.update'), ['name' => ''])
        ->assertRedirect($page)->assertSessionHasErrors('name');
    $this->get($page)->assertOk()->assertSee('novalidate', false)
        ->assertSeeInOrder(['</main>', 'data-toast-type="error"'], false);
})->with(['admin', 'principal']);

test('failed saves return an error toast and preserve entered school details', function () {
    $this->actingAs(schoolInformationStaff('admin'));
    $database = \Illuminate\Support\Facades\DB::getFacadeRoot();
    \Illuminate\Support\Facades\Exceptions::fake();
    \Illuminate\Support\Facades\DB::partialMock()->shouldReceive('transaction')->once()
        ->andThrow(new \RuntimeException('Simulated save failure'));

    $page = route('admin.school-information.edit');
    $this->from($page)->put(route('admin.school-information.update'), schoolInformationData())
        ->assertRedirect($page)
        ->assertSessionHas('error')
        ->assertSessionHasInput('name', 'Example National High School');
    \Illuminate\Support\Facades\DB::swap($database);
    $this->get($page)->assertOk()->assertSee('data-toast-type="error"', false)
        ->assertSee('School information could not be saved.');
    $this->assertDatabaseCount('school_information', 0);
});

function schoolInformationLogo(): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aO1sAAAAASUVORK5CYII='));
}

test('generated report cards use saved school information and logo', function (string $grade) {
    Storage::fake('public');
    $this->actingAs(schoolInformationStaff('admin'))->put(route('admin.school-information.update'), schoolInformationData() + ['logo' => schoolInformationLogo()])->assertSessionHasNoErrors();
    $section = new \App\Models\Section(['name' => 'Example Section']);
    $section->setRelation('gradeLevel', \App\Models\GradeLevel::query()->where('grade_label', $grade)->firstOrFail());
    $enrollment = new \App\Models\Enrollment;
    $enrollment->setRelation('student', new \App\Models\Student(['first_name' => 'Test', 'last_name' => 'Learner']));
    $periods = [['key' => 'term_1', 'label' => 'Term 1']];
    $card = \App\Support\Sf9ReportCardBuilder::buildCard($enrollment, $section, collect(), collect(), collect(), $periods, [], 'Principal');
    $html = view('users.teacher.advisory.sf9-print', ['cards' => [$card], 'periods' => $periods, 'observedValueMarkings' => \App\Support\Sf9ReportCardBuilder::observedValueMarkings()])->render();
    foreach (schoolInformationData() as $value) {
        expect($html)->toContain($value);
    }
    expect($html)->toContain('data:image/png;base64,');
})->with(['Grade 7', 'Grade 11']);
