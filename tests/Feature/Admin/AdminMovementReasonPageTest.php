<?php

use App\Models\MovementReason;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

function createMovementReasonPageAdmin(string $username): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'email' => $username.'@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
}

test('admin can view the restyled movement reasons page', function () {
    $admin = createMovementReasonPageAdmin('admin.movement.reasons.page');

    MovementReason::query()->create([
        'name' => 'Family problems',
        'description' => 'Domestic-Related Factors (a.4)',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.movement-reason-config.index'));

    $response->assertOk();
    $response->assertSee('Movement Reasons');
    $response->assertSee('Add Reason');
    $response->assertSee('Manage reasons for student movement and dropping out.');
    $response->assertSee('Family problems');
    $response->assertSee('Domestic-Related Factors (a.4)');
    $response->assertSee('>Movement Reasons</span>', false);
    $response->assertSee('id="movementReasonModal"', false);
    $response->assertSee('openMovementReasonModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Delete"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertSee('M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', false);
    $response->assertDontSee('Masterfile');
    $response->assertDontSee('>Edit</button>', false);
    $response->assertDontSee('>Delete</button>', false);
});

test('admin can search movement reasons', function () {
    $admin = createMovementReasonPageAdmin('admin.movement.reasons.search');

    MovementReason::query()->create([
        'name' => 'Illness',
        'description' => 'Individual-Related Factors (b.1)',
    ]);
    MovementReason::query()->create([
        'name' => 'Peer influence',
        'description' => 'School-Related Factors (c.3)',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.movement-reason-config.index', ['search' => 'Illness']));

    $response->assertOk();
    $response->assertSee('Illness');
    $response->assertDontSee('Peer influence');
});

test('admin can create a movement reason', function () {
    $admin = createMovementReasonPageAdmin('admin.movement.reasons.create');

    $this->actingAs($admin)
        ->from(route('admin.movement-reason-config.index'))
        ->post(route('admin.movement-reason-config.store'), [
            'name' => 'Transferred to another school',
            'description' => 'School-Related Factors',
        ])
        ->assertRedirect(route('admin.movement-reason-config.index'))
        ->assertSessionHasNoErrors();

    expect(MovementReason::query()->where('name', 'Transferred to another school')->exists())->toBeTrue();
});

test('admin can delete a movement reason', function () {
    $admin = createMovementReasonPageAdmin('admin.movement.reasons.delete');
    $reason = MovementReason::query()->create([
        'name' => 'Temporary reason',
        'description' => 'For testing',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.movement-reason-config.index'))
        ->delete(route('admin.movement-reason-config.delete', $reason))
        ->assertRedirect(route('admin.movement-reason-config.index'));

    expect(MovementReason::query()->whereKey($reason->reason_ID)->exists())->toBeFalse();
});
