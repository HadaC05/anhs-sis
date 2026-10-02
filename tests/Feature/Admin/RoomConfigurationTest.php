<?php

use App\Models\Role;
use App\Models\Room;
use App\Models\Staff;

function roomPageStaff(string $role): Staff
{
    return Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'rooms.'.$role,
        'password' => 'password',
        'first_name' => 'Room',
        'last_name' => 'Manager',
        'status' => 'active',
    ]);
}

test('management can add rooms and select them for sections', function (string $role) {
    $this->actingAs(roomPageStaff($role))
        ->get(route($role.'.room-config.index'))->assertOk()->assertSee('Add Room');

    $this->post(route($role.'.room-config.store'), ['name' => '  Science Laboratory  '])
        ->assertRedirect(route($role.'.room-config.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('rooms', ['name' => 'Science Laboratory']);

    $this->get(route($role.'.room-config.index'))->assertOk()
        ->assertSee('data-section-toast', false)->assertSee('Room added successfully.');

    $this->get(route($role.'.section-config.index'))->assertOk()
        ->assertSee('value="Science Laboratory"', false);

    $this->from(route($role.'.room-config.index'))
        ->post(route($role.'.room-config.store'), ['name' => 'Science Laboratory'])
        ->assertSessionHasErrors('name');
    expect(Room::query()->count())->toBe(1);

    foreach (['', '   ', str_repeat('R', 256)] as $invalid) {
        $this->post(route($role.'.room-config.store'), ['name' => $invalid])
            ->assertSessionHasErrors('name');
    }
})->with(['admin', 'principal']);

test('rooms can be searched', function () {
    Room::query()->create(['name' => 'East Wing']);
    Room::query()->create(['name' => 'West Wing']);
    $this->actingAs(roomPageStaff('admin'))
        ->get(route('admin.room-config.index', ['search' => 'East']))
        ->assertOk()->assertSee('East Wing')->assertDontSee('West Wing');
});

test('teachers cannot manage rooms', function (string $portal) {
    $room = Room::query()->create(['name' => 'Original Room']);
    $this->actingAs(roomPageStaff('teacher'))
        ->get(route($portal.'.room-config.index'))->assertForbidden();
    $this->post(route($portal.'.room-config.store'), ['name' => 'Forbidden Room'])->assertForbidden();
    $this->put(route($portal.'.room-config.update', $room), ['name' => 'Forbidden Room'])->assertForbidden();
    $this->assertDatabaseMissing('rooms', ['name' => 'Forbidden Room']);
})->with(['admin', 'principal']);

test('management can edit rooms with validation and success toasts', function (string $role) {
    $room = Room::query()->create(['name' => 'Original Room']);
    Room::query()->create(['name' => 'Other Room']);
    $editUrl = route($role.'.room-config.index', ['edit' => $room->id]);
    $this->actingAs(roomPageStaff($role))->get($editUrl)
        ->assertOk()->assertSee('Edit Room')->assertSee('value="Original Room"', false);

    foreach (['', 'Other Room', str_repeat('R', 256)] as $invalid) {
        $this->from($editUrl)->put(route($role.'.room-config.update', $room), ['name' => $invalid])
            ->assertRedirect($editUrl)->assertSessionHasErrors('name');
        expect($room->fresh()->name)->toBe('Original Room');
    }

    $this->put(route($role.'.room-config.update', $room), ['name' => 'Original Room'])->assertSessionHasNoErrors();
    $this->put(route($role.'.room-config.update', $room), ['name' => 'Renamed Room'])
        ->assertRedirect(route($role.'.room-config.index'))->assertSessionHasNoErrors();
    $this->get(route($role.'.room-config.index'))->assertOk()
        ->assertSee('data-section-toast', false)->assertSee('Room updated successfully.')->assertSee('Renamed Room');
    expect($room->fresh()->name)->toBe('Renamed Room');
})->with(['admin', 'principal']);
