<?php

use App\Models\DocumentReturnReason;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

function createDocumentReturnReasonPageAdmin(string $username): Staff
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

test('admin can view the document return reasons page', function () {
    $admin = createDocumentReturnReasonPageAdmin('admin.document.return.reasons.page');

    DocumentReturnReason::query()->create([
        'name' => 'Missing signature',
        'description' => 'The document is unsigned.',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.document-return-reason-config.index'));

    $response->assertOk();
    $response->assertSee('Document Return Reasons');
    $response->assertSee('Add Reason');
    $response->assertSee('Manage reasons used when returning student documents for resubmission.');
    $response->assertSee('Missing signature');
    $response->assertSee('The document is unsigned.');
    $response->assertSee('>Document Return Reasons</span>', false);
    $response->assertSee('id="documentReturnReasonModal"', false);
    $response->assertSee('openDocumentReturnReasonModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Delete"', false);
});

test('admin can search document return reasons', function () {
    $admin = createDocumentReturnReasonPageAdmin('admin.document.return.reasons.search');

    DocumentReturnReason::query()->create([
        'name' => 'Cropped scan',
        'description' => 'Edges of the page are missing.',
    ]);
    DocumentReturnReason::query()->create([
        'name' => 'Wrong school year',
        'description' => 'The report card is for a different year.',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.document-return-reason-config.index', ['search' => 'Cropped']));

    $response->assertOk();
    $response->assertSee('Cropped scan');
    $response->assertDontSee('Wrong school year');
});

test('admin can create a document return reason', function () {
    $admin = createDocumentReturnReasonPageAdmin('admin.document.return.reasons.create');

    $this->actingAs($admin)
        ->from(route('admin.document-return-reason-config.index'))
        ->post(route('admin.document-return-reason-config.store'), [
            'name' => 'Altered document',
            'description' => 'The file appears to have been edited.',
        ])
        ->assertRedirect(route('admin.document-return-reason-config.index'))
        ->assertSessionHasNoErrors();

    expect(DocumentReturnReason::query()->where('name', 'Altered document')->exists())->toBeTrue();
});

test('admin can delete a document return reason', function () {
    $admin = createDocumentReturnReasonPageAdmin('admin.document.return.reasons.delete');
    $reason = DocumentReturnReason::query()->create([
        'name' => 'Temporary return reason',
        'description' => 'For testing',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.document-return-reason-config.index'))
        ->delete(route('admin.document-return-reason-config.delete', $reason))
        ->assertRedirect(route('admin.document-return-reason-config.index'));

    expect(DocumentReturnReason::query()->whereKey($reason->reason_ID)->exists())->toBeFalse();
});
