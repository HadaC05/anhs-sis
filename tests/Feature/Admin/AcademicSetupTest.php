<?php

use App\Models\Role;
use App\Models\Staff;

test('academic setup preloads both panels and selects the requested tab', function (string $role, string $tab) {
    $staff = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'academic.setup.'.$role,
        'password' => 'password',
        'first_name' => 'School',
        'last_name' => 'Staff',
        'status' => 'active',
        'change_password' => false,
    ]);

    $response = $this->actingAs($staff)->get(route($role.'.'.$tab.'.index'));

    $response->assertOk()
        ->assertViewHas('setupTab', $tab)
        ->assertSee('id="academic-year-config-panel"', false)
        ->assertSee('id="grading-term-config-panel"', false)
        ->assertSee('id="academicYearForm"', false)
        ->assertSee('id="editTermForm"', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@role="tabpanel" and not(@hidden)]')->length)->toBe(1)
        ->and($document->getElementById($tab.'-panel')->hasAttribute('hidden'))->toBeFalse()
        ->and($document->getElementById($tab.'-tab')->getAttribute('aria-selected'))->toBe('true');

    $yearData = $response->viewData('yearData');
    expect($yearData['academicYears']->path())->toBe(route($role.'.academic-year-config.index'));
})->with(['admin', 'principal'])->with(['academic-year-config', 'grading-term-config']);
