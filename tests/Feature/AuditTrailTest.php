<?php

use App\Models\AuditLog;
use App\Models\MovementReason;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\Route;

function auditStaff(string $role = 'admin'): Staff
{
    return Staff::create([
        'username' => fake()->unique()->userName(), 'password' => 'password',
        'first_name' => 'Audit', 'last_name' => 'Reviewer',
        'role_id' => Role::firstOrCreate(['role_name' => $role])->id,
        'status' => 'active', 'change_password' => false,
    ]);
}

test('admin and principal can view the shared read only audit trail', function (string $role) {
    $staff = auditStaff($role);
    AuditTrail::record(AuditTrail::actor($staff), 'Updated', 'Grades', 'Changed a grade.', 'grade:123');

    $this->actingAs($staff)->get(route($role.'.audit-trail.index'))
        ->assertOk()->assertSee('Audit Trail')->assertSee('grade:123')->assertSee('Changed a grade.')
        ->assertSee(route($role.'.audit-trail.index'), false);
    expect(AuditLog::count())->toBe(1);
    $this->put(route($role.'.audit-trail.index'), ['description' => 'tampered'])->assertStatus(405);
    $this->delete(route($role.'.audit-trail.index'))->assertStatus(405);
})->with(['admin', 'principal']);

test('other roles cannot access either audit portal', function (string $role) {
    $user = $role === 'student'
        ? Student::create(['lrn' => '123456789012', 'username' => 'audit.student', 'first_name' => 'Student', 'last_name' => 'User', 'password' => 'password', 'status' => 'active', 'change_password' => false])
        : auditStaff($role);
    $this->actingAs($user)->get(route('admin.audit-trail.index'))->assertForbidden();
    $this->get(route('principal.audit-trail.index'))->assertForbidden();
})->with(['teacher', 'registrar', 'guidance counselor', 'student']);

test('guests must sign in to view audit entries', function () {
    $this->get(route('admin.audit-trail.index'))->assertRedirect(route('login'));
    $this->get(route('principal.audit-trail.index'))->assertRedirect(route('login'));
});

test('successful creates and deletes record the actor and database assigned reference without request secrets', function () {
    $admin = auditStaff();
    $this->actingAs($admin)->post(route('admin.movement-reason-config.store'), [
        'name' => 'Audit transfer', 'password' => 'DO-NOT-LOG', 'token' => 'SECRET-TOKEN',
    ])->assertSessionHasNoErrors();
    $reason = MovementReason::where('name', 'Audit transfer')->firstOrFail();
    $log = AuditLog::sole();
    expect($log->user_id)->toBe('staff:'.$admin->staff_id)
        ->and($log->user_name)->toBe('Audit Reviewer')
        ->and($log->role)->toBe('admin')
        ->and($log->action)->toBe('Created')
        ->and($log->status)->toBe('Success')
        ->and($log->reference)->toContain('MovementReason:'.$reason->getKey())
        ->and($log->toJson())->not->toContain('DO-NOT-LOG', 'SECRET-TOKEN');

    $this->delete(route('admin.movement-reason-config.delete', $reason))->assertRedirect();
    expect(AuditLog::orderByDesc('audit_id')->first()->action)->toBe('Deleted');
});

test('validation failures and denied actions are failed entries without sensitive error text', function () {
    $this->actingAs(auditStaff())->post(route('admin.movement-reason-config.store'), ['password' => 'SECRET'])->assertSessionHasErrors('name');
    expect(AuditLog::sole()->status)->toBe('Failed')
        ->and(AuditLog::sole()->toJson())->not->toContain('SECRET');

    $this->actingAs(auditStaff('teacher'))->post(route('admin.movement-reason-config.store'), ['name' => 'Denied'])->assertForbidden();
    expect(AuditLog::count())->toBe(2)
        ->and(AuditLog::orderByDesc('audit_id')->first()->status)->toBe('Failed');
});

test('forced password redirects are not logged as successful mutations', function () {
    $staff = auditStaff();
    $staff->update(['change_password' => true]);
    $this->actingAs($staff)->post(route('admin.movement-reason-config.store'), ['name' => 'Never created'])
        ->assertRedirect(route('force-password.edit'));
    expect(AuditLog::sole()->status)->toBe('Failed');
});

test('ordinary navigation and enrollment status lookups are excluded', function () {
    $this->actingAs(auditStaff())->get(route('admin.movement-reason-config.index'))->assertOk();
    $this->get(route('admin.audit-trail.index'))->assertOk();
    $this->post(route('applications.status'), [])->assertSessionHasErrors();
    expect(AuditLog::count())->toBe(0);
});

test('audit records retain identity snapshots and cannot be edited or deleted through the model', function () {
    $staff = auditStaff();
    $log = AuditTrail::record(AuditTrail::actor($staff), 'Updated', 'Grades', 'Updated grades.');
    $staff->update(['first_name' => 'Renamed', 'role_id' => Role::firstOrCreate(['role_name' => 'teacher'])->id]);
    expect($log->fresh()->user_name)->toBe('Audit Reviewer')->and($log->fresh()->role)->toBe('admin');
    expect(fn () => $log->update(['description' => 'Tampered']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
});

test('audit filters combine dates user role module action and status and preserve pagination', function () {
    $staff = auditStaff();
    for ($i = 0; $i < 12; $i++) {
        AuditLog::create([...AuditTrail::actor($staff), 'timestamp' => '2026-09-28 15:59:59', 'action' => 'Updated', 'module' => 'Grades', 'description' => 'Matching grade change '.$i, 'reference' => 'grade:'.$i, 'status' => 'Success']);
    }
    AuditLog::create([...AuditTrail::actor($staff), 'timestamp' => '2026-09-28 16:00:00', 'action' => 'Updated', 'module' => 'Grades', 'description' => 'Outside date range', 'status' => 'Success']);
    $url = route('admin.audit-trail.index', ['search' => 'grade', 'user' => 'Audit Reviewer', 'role' => 'admin', 'module' => 'Grades', 'action' => 'Updated', 'status' => 'Success', 'from' => '2026-09-28', 'to' => '2026-09-28', 'per_page' => 10]);
    $this->actingAs($staff)->get($url)->assertOk()->assertDontSee('Outside date range')->assertViewHas('logs', fn ($logs) => $logs->total() === 12 && $logs->count() === 10 && str_contains($logs->nextPageUrl(), 'module=Grades'));
    $this->get(route('admin.audit-trail.index', ['to' => '2026-09-28']))->assertOk();
});

test('a handled rollback is excluded from a successful workflow description', function () {
    $staff = auditStaff();
    Route::middleware('web')->put('/audit-test-rollback', function () use ($staff) {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($staff) {
                $staff->update(['status' => 'inactive']);
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            $staff->refresh();
        }
        MovementReason::create(['name' => 'Committed reason']);

        return response('saved');
    })->name('audit.test.rollback');
    config(['audit.actions' => [...config('audit.actions'), 'audit.test.rollback' => ['Movement Reasons', 'Created', 'Created reason.']]]);
    $this->actingAs($staff)->put('/audit-test-rollback')->assertOk();
    expect(AuditLog::sole()->description)->not->toContain('inactive')
        ->and(AuditLog::sole()->reference)->toContain('MovementReason:')->not->toContain('Staff:');
});

test('partial bulk outcomes record counts without exposing generated account credentials', function () {
    Route::middleware('web')->post('/audit-test-bulk', fn () => back()->with('enrollment_result', [
        'updated' => 2, 'failed' => 1, 'skipped' => 0, 'accounts' => [['password' => 'NEVER-LOG-THIS']],
    ]))->name('audit.test.bulk');
    config(['audit.actions' => [...config('audit.actions'), 'audit.test.bulk' => ['Enrollment', 'Updated', 'Processed enrollment statuses.']]]);
    $this->actingAs(auditStaff('guidance counselor'))->post('/audit-test-bulk')->assertRedirect();
    expect(AuditLog::sole()->status)->toBe('Partial')
        ->and(AuditLog::sole()->description)->toContain('Updated: 2.', 'Failed: 1.')
        ->and(AuditLog::sole()->toJson())->not->toContain('NEVER-LOG-THIS');
});

test('operational changes include before and after while credentials remain excluded', function () {
    $staff = auditStaff();
    Route::middleware('web')->put('/audit-test-change', function () use ($staff) {
        $staff->update(['status' => 'inactive', 'password' => 'SensitivePassword123!']);

        return response('saved');
    })->name('audit.test.change');
    config(['audit.actions' => [...config('audit.actions'), 'audit.test.change' => ['User Management', 'Updated', 'Updated account.']]]);
    $this->actingAs($staff)->put('/audit-test-change')->assertOk();
    $log = AuditLog::sole();
    expect($log->description)->toContain('active → inactive')
        ->and($log->toJson())->not->toContain('SensitivePassword123!', $staff->password);
});

test('rolled back workflows and server exceptions never record success or exception secrets', function () {
    Route::middleware('web')->post('/audit-test-failure', function () {
        \Illuminate\Support\Facades\DB::transaction(function () {
            MovementReason::create(['name' => 'Rolled back']);
            throw new RuntimeException('SECRET EXCEPTION PAYLOAD');
        });
    })->name('audit.test.failure');
    config(['audit.actions' => [...config('audit.actions'), 'audit.test.failure' => ['Movement Reasons', 'Created', 'Created movement reason.']]]);
    $this->actingAs(auditStaff())->post('/audit-test-failure')->assertStatus(500);
    expect(MovementReason::where('name', 'Rolled back')->exists())->toBeFalse()
        ->and(AuditLog::sole()->status)->toBe('Failed')
        ->and(AuditLog::sole()->toJson())->not->toContain('SECRET EXCEPTION PAYLOAD');
});
