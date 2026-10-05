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
    $this->actingAs($admin)->get(route('admin.sf9-configuration.edit'))->assertOk()->assertSee('SF9 Form Configuration');
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
