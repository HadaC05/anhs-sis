<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

function createPreventBackHistoryAdmin(): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.prevent.back',
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Cache',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
}

function assertBrowserMustNotStore(TestResponse $response): void
{
    $cacheControl = strtolower((string) $response->headers->get('Cache-Control'));

    expect($cacheControl)->toContain('no-store')
        ->and($cacheControl)->toContain('no-cache')
        ->and($cacheControl)->toContain('must-revalidate');

    expect(strtolower((string) $response->headers->get('Pragma')))->toContain('no-cache');
    expect((string) $response->headers->get('Expires'))->toBe('0');
}

test('authenticated pages tell the browser not to store the response', function () {
    $admin = createPreventBackHistoryAdmin();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    assertBrowserMustNotStore($response);
});

test('logout responses tell the browser not to restore the previous page', function () {
    $admin = createPreventBackHistoryAdmin();

    $response = $this->actingAs($admin)->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
    assertBrowserMustNotStore($response);
});

test('guest pages are not forced into no-store cache headers', function () {
    $homeCacheControl = strtolower((string) $this->get(route('home'))->headers->get('Cache-Control'));
    $loginCacheControl = strtolower((string) $this->get(route('login'))->headers->get('Cache-Control'));

    expect($homeCacheControl)->not->toContain('no-store');
    expect($loginCacheControl)->not->toContain('no-store');
});

test('visiting a protected page after logout redirects to login', function () {
    $admin = createPreventBackHistoryAdmin();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
