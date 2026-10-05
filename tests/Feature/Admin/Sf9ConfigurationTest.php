<?php

use App\Models\Role;
use App\Models\Sf9Configuration;
use App\Models\Staff;

function sf9Manager(string $role): Staff
{
    return Staff::create(['role_id' => Role::firstOrCreate(['role_name' => $role])->id, 'username' => 'sf9.'.$role, 'password' => 'password', 'first_name' => 'Form', 'last_name' => 'Manager', 'status' => 'active']);
}

test('managers share SF9 selections and can restore the original format', function () {
    $admin = sf9Manager('admin');
    $principal = sf9Manager('principal');
    $this->actingAs($admin)->get(route('admin.sf9-configuration.edit'))->assertOk()->assertSee('School Forms');
    $this->put(route('admin.sf9-configuration.update'), ['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current'])->assertSessionHasNoErrors();
    expect(Sf9Configuration::current()->junior_high)->toBe('jhs_2026');
    $this->actingAs($principal)->get(route('principal.sf9-configuration.edit'))->assertOk()->assertSee('jhs_2026');
    $this->put(route('principal.sf9-configuration.update'), ['junior_high' => 'jhs_legacy', 'senior_high' => 'shs_current'])->assertSessionHasNoErrors();
    expect(Sf9Configuration::current()->junior_high)->toBe('jhs_legacy');
    $this->assertDatabaseCount('sf9_configurations', 1);
});

test('SF9 settings reject unknown and cross level formats without saving', function () {
    $this->actingAs(sf9Manager('admin'))->put(route('admin.sf9-configuration.update'), ['junior_high' => 'shs_current', 'senior_high' => 'jhs_2026'])->assertSessionHasErrors(['junior_high', 'senior_high']);
    $this->assertDatabaseCount('sf9_configurations', 0);
});

test('other staff cannot configure or preview SF9 formats', function (string $role) {
    $this->actingAs(sf9Manager($role));
    foreach (['admin', 'principal'] as $portal) {
        $this->get(route($portal.'.sf9-configuration.edit'))->assertForbidden();
        $this->put(route($portal.'.sf9-configuration.update'), ['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current'])->assertForbidden();
        $this->get(route($portal.'.sf9-configuration.preview', 'jhs_2026'))->assertForbidden();
    }
})->with(['teacher', 'registrar']);

test('every registered SF9 format has a preview that does not change selections', function (string $format) {
    $this->actingAs(sf9Manager('principal'))->get(route('principal.sf9-configuration.preview', $format))->assertOk()->assertSee('Print SF9');
    $this->assertDatabaseCount('sf9_configurations', 0);
})->with(['jhs_legacy', 'jhs_2026', 'shs_current']);

test('updated SF9 labels retain configured paired MAPEH grades', function () {
    $rows = [['label' => 'Music & Arts', 'child' => true, 'quarters' => ['term_1' => 88], 'final' => 88, 'remarks' => 'Passed'], ['label' => 'Physical Education & Health', 'child' => true, 'quarters' => ['term_1' => 90], 'final' => 90, 'remarks' => 'Passed']];
    $updated = \App\Support\Sf9ReportCardBuilder::updatedJuniorHighRows($rows);
    expect($updated[0]['label'])->toBe('Music and Arts')->and($updated[1]['label'])->toBe('Physical Education and Health');
    foreach ($updated as $index => $row) {
        expect(collect($row)->except('label')->all())->toBe(collect($rows[$index])->except('label')->all());
    }
    expect(\App\Support\Sf9ReportCardBuilder::juniorHighSubjectSlot('GMRC / Values Education'))->toBe('esp');
});

test('SF9 previews use the saved school profile logo and active school year', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aO1sAAAAASUVORK5CYII=');
    \Illuminate\Support\Facades\Storage::disk('public')->put('school-logos/current.png', $png);
    $school = \App\Models\SchoolInformation::create(['name' => 'Updated National High School', 'school_id' => '123456', 'region' => 'Region VII', 'division' => 'Schools Division of Example City', 'district' => 'Example District', 'logo_path' => 'school-logos/current.png']);
    \App\Models\AcademicYear::create(['school_year' => '2027-2028', 'start_date' => '2027-06-01', 'end_date' => '2028-03-31', 'status' => true]);
    $url = route('admin.sf9-configuration.preview', 'jhs_2026');
    $this->actingAs(sf9Manager('admin'))->get($url)->assertOk()
        ->assertSee('Updated National High School')->assertSee('123456')->assertSee('Region VII')
        ->assertSee('Schools Division of Example City')->assertSee('Example District')
        ->assertSee('School Year 2027-2028')->assertSee($school->logoDataUri(), false)
        ->assertDontSee('SCHOOLS DIVISION OF Schools Division');
    $school->update(['division' => 'New City', 'name' => 'Renamed School', 'logo_path' => null]);
    $this->get($url)->assertOk()->assertSee('Renamed School')->assertSee('SCHOOLS DIVISION OF New City')
        ->assertDontSee('Updated National High School')->assertDontSee('alt="School logo"', false);
});

test('school forms tabs share SF2 selection without changing SF9 settings', function () {
    $admin = sf9Manager('admin');
    $principal = sf9Manager('principal');
    Sf9Configuration::create(['junior_high' => 'jhs_2026', 'senior_high' => 'shs_current']);
    $this->actingAs($admin)->get(route('admin.school-forms.edit'))->assertOk()
        ->assertSee('School Forms')->assertSee('Save SF9 Formats')->assertDontSee('Save SF2 Format');
    $this->get(route('admin.school-forms.edit', ['tab' => 'sf2']))->assertOk()
        ->assertSee('Save SF2 Format')->assertSee('Old SF2 form')->assertSee('New SF2 form')
        ->assertDontSee('Save SF9 Formats');
    $this->put(route('admin.school-forms.sf2.update'), ['format' => 'lis'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.school-forms.edit', ['tab' => 'sf2']));
    expect(\App\Models\Sf2Configuration::current()->format)->toBe('lis')
        ->and(Sf9Configuration::current()->junior_high)->toBe('jhs_2026');
    $this->actingAs($principal)->get(route('principal.school-forms.edit', ['tab' => 'sf2']))
        ->assertOk()->assertViewHas('sf2Configuration', fn ($config) => $config->format === 'lis');
    $this->put(route('principal.sf9-configuration.update'), ['junior_high' => 'jhs_legacy', 'senior_high' => 'shs_current'])->assertSessionHasNoErrors();
    expect(\App\Models\Sf2Configuration::current()->format)->toBe('lis');
    $this->put(route('principal.school-forms.sf2.update'), ['format' => 'legacy'])->assertSessionHasNoErrors();
    expect(\App\Models\Sf2Configuration::current()->format)->toBe('legacy');
    $this->assertDatabaseCount('sf2_configurations', 1);
});

test('school forms rejects an unknown SF2 format', function () {
    $this->actingAs(sf9Manager('admin'))->put(route('admin.school-forms.sf2.update'), ['format' => 'unknown'])->assertSessionHasErrors('format');
    $this->assertDatabaseCount('sf2_configurations', 0);
});

test('other staff cannot change school forms', function (string $role) {
    $this->actingAs(sf9Manager($role));
    foreach (['admin', 'principal'] as $portal) {
        $this->get(route($portal.'.school-forms.edit', ['tab' => 'sf2']))->assertForbidden();
        $this->put(route($portal.'.school-forms.sf2.update'), ['format' => 'lis'])->assertForbidden();
    }
    $this->assertDatabaseCount('sf2_configurations', 0);
})->with(['teacher', 'registrar']);
