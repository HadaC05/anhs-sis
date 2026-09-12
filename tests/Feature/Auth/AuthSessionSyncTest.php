<?php

use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Support\AuthSessionFingerprint;
use Illuminate\Support\Facades\Hash;

function createAuthSessionSyncAdmin(string $username = 'admin.auth.session.sync'): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Session',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
}

function createAuthSessionSyncStudent(): Student
{
    return Student::query()->create([
        'username' => 'student.auth.session.sync',
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => '109876543211',
        'first_name' => 'Session',
        'last_name' => 'Student',
        'status' => 'active',
    ]);
}

test('auth session fingerprints change when the account or session changes', function () {
    $admin = createAuthSessionSyncAdmin();
    $student = createAuthSessionSyncStudent();

    expect(AuthSessionFingerprint::for(null))->toBe('guest')
        ->and(AuthSessionFingerprint::for($admin, 'session-a'))
        ->toStartWith($admin->getAuthIdentifier().'|')
        ->and(AuthSessionFingerprint::for($admin, 'session-a'))
        ->not->toBe(AuthSessionFingerprint::for($admin, 'session-b'))
        ->and(AuthSessionFingerprint::for($admin, 'session-a'))
        ->not->toBe(AuthSessionFingerprint::for($student, 'session-a'));
});

test('guest pages publish a guest auth session fingerprint and listen for a new login', function () {
    foreach ([route('home'), route('login'), route('password.request'), route('register')] as $url) {
        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('data-test="auth-session-sync"', false);
        $response->assertSee('data-fingerprint="guest"', false);
        $response->assertSee('data-authenticated="0"', false);
        $response->assertSee('data-dashboard-url="'.route('dashboard').'"', false);
        $response->assertSee('data-login-url="'.route('login').'"', false);
        $response->assertSee('window.location.replace(root.dataset.dashboardUrl)', false);
        $response->assertSee('window.location.replace(root.dataset.loginUrl)', false);
        $response->assertSee('BroadcastChannel(channelName)', false);
        $response->assertSee("form.action.endsWith('/logout')", false);
        $response->assertSee('publishGuest()', false);
        $response->assertSee('syncAuthenticatedTab()', false);
        $response->assertSee('if (! event.persisted)', false);
        $response->assertSee("root.dataset.authenticated !== '1'", false);
    }
});

test('authenticated dashboards publish the current account fingerprint', function () {
    $admin = createAuthSessionSyncAdmin();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('data-test="auth-session-sync"', false);
    $response->assertSee('data-fingerprint="'.AuthSessionFingerprint::for($admin).'"', false);
    $response->assertSee('data-authenticated="1"', false);
    $response->assertSee('data-dashboard-url="'.route('dashboard').'"', false);
});

test('student dashboards publish a different fingerprint than staff dashboards', function () {
    $admin = createAuthSessionSyncAdmin();
    $student = createAuthSessionSyncStudent();

    $adminResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
    $adminResponse->assertOk();
    $adminResponse->assertSee('data-fingerprint="'.$admin->getAuthIdentifier().'|', false);

    $studentResponse = $this->actingAs($student)->get(route('student.dashboard'));
    $studentResponse->assertOk();
    $studentResponse->assertSee('data-fingerprint="'.$student->getAuthIdentifier().'|', false);

    expect($admin->getAuthIdentifier())->not->toBe($student->getAuthIdentifier());
});
