<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Month;
use App\Models\Role;
use App\Models\Staff;
use App\Support\Sf9AttendanceSummary;
use Illuminate\Support\Facades\Hash;

function createAttendanceConfigAdmin(string $username): Staff
{
    $role = Role::query()->create(['role_name' => 'admin']);

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

function createAttendanceConfigYear(string $schoolYear = '2026-2027', bool $status = true): AcademicYear
{
    $startYear = (int) substr($schoolYear, 0, 4);

    return AcademicYear::query()->create([
        'school_year' => $schoolYear,
        'start_date' => $startYear.'-06-01',
        'end_date' => ($startYear + 1).'-03-31',
        'status' => $status,
    ]);
}

test('management can save an attendance range independently for each school year', function (string $roleName) {
    $staff = createAttendanceConfigAdmin('attendance.range.'.$roleName);
    $staff->role->update(['role_name' => $roleName]);
    $year = createAttendanceConfigYear();
    $otherYear = createAttendanceConfigYear('2025-2026', false);

    $this->actingAs($staff)->put(route($roleName.'.attendance-config.update'), [
        'SY_ID' => $year->SY_ID,
        'attendance_start_month' => 8,
        'attendance_end_month' => 4,
        'school_days' => [8 => 20],
    ])->assertSessionHasNoErrors()->assertRedirect(route($roleName.'.attendance-config.index', ['sy_id' => $year->SY_ID]));

    expect(array_keys($year->fresh()->attendanceMonths()))->toBe([8, 9, 10, 11, 12, 1, 2, 3, 4])
        ->and($otherYear->fresh()->attendanceStartMonth())->toBe(6)
        ->and($otherYear->fresh()->attendanceEndMonth())->toBe(3);

    $this->actingAs($staff)->get(route($roleName.'.attendance-config.index', ['sy_id' => $year->SY_ID]))
        ->assertOk()->assertSee('August through April');
})->with(['admin', 'principal']);

test('attendance month boundaries reject invalid and incomplete ranges', function (array $range, string $error) {
    $staff = createAttendanceConfigAdmin('attendance.range.invalid');
    $year = createAttendanceConfigYear();

    $this->actingAs($staff)->put(route('admin.attendance-config.update'), [
        'SY_ID' => $year->SY_ID,
        'school_days' => [6 => 20],
        ...$range,
    ])->assertSessionHasErrors($error);

    expect($year->fresh()->attendance_start_month)->toBeNull();
    expect(AcademicYearAttendanceSetting::query()->count())->toBe(0);
})->with([
    [['attendance_start_month' => 0, 'attendance_end_month' => 3], 'attendance_start_month'],
    [['attendance_start_month' => 6, 'attendance_end_month' => 13], 'attendance_end_month'],
    [['attendance_start_month' => 6], 'attendance_end_month'],
]);

test('attendance ranges support same year and single month selections', function () {
    $year = createAttendanceConfigYear();
    $year->fill(['attendance_start_month' => 2, 'attendance_end_month' => 5]);
    expect(array_keys($year->attendanceMonths()))->toBe([2, 3, 4, 5]);
    $year->attendance_end_month = 2;
    expect(array_keys($year->attendanceMonths()))->toBe([2]);
});

test('admin can view the attendance configuration page', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.config');
    $year = createAttendanceConfigYear();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $year->SY_ID,
        'month' => 6,
        'school_days' => 20,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.attendance-config.index'));

    $response->assertOk();
    $response->assertSee('Attendance Configuration');
    $response->assertSee('Monthly School Days');
    $response->assertSee('Total School Days');
    $response->assertSee('Months Configured');
    $response->assertSee('June through March');
    $response->assertSee('2026-2027');
    $response->assertSeeInOrder(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
    $response->assertSee('data-editing="false"', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('id="attendanceEditButton"', false);
    $response->assertSee('startAttendanceEdit', false);
    $response->assertSee('cancelAttendanceEdit', false);
    $response->assertSee('Save Attendance Configuration');
    $response->assertSee('Cancel');
    $response->assertSee('name="school_days[1]"', false);
    $response->assertSee('name="school_days[6]"', false);
    $response->assertSee('maxlength="2"', false);
    $response->assertSee('inputmode="numeric"', false);
    $response->assertSee('pattern="[0-9]{0,2}"', false);
    $response->assertSee('school-days-input', false);
    $response->assertSee('is-viewing', false);
    $response->assertSee('>20</p>', false);
});

test('admin can save school days per month for the selected academic year', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.save');
    $year = createAttendanceConfigYear();

    $schoolDays = [];
    foreach (Month::ids() as $month) {
        $schoolDays[$month] = $month === 1 ? 18 : ($month === 6 ? 22 : 0);
    }

    $response = $this->actingAs($admin)
        ->from(route('admin.attendance-config.index'))
        ->put(route('admin.attendance-config.update'), [
            'SY_ID' => $year->SY_ID,
            'school_days' => $schoolDays,
        ]);

    $response->assertRedirect(route('admin.attendance-config.index', ['sy_id' => $year->SY_ID]));
    $response->assertSessionHas('success');

    expect(AcademicYearAttendanceSetting::query()->where('SY_ID', $year->SY_ID)->where('month', 1)->value('school_days'))->toBe(18)
        ->and(AcademicYearAttendanceSetting::query()->where('SY_ID', $year->SY_ID)->where('month', 6)->value('school_days'))->toBe(22)
        ->and(Sf9AttendanceSummary::schoolDaysForAcademicYear($year->SY_ID)[1])->toBe(18);
});

test('admin can switch academic years when configuring school days', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.switch');
    $currentYear = createAttendanceConfigYear('2026-2027', true);
    $previousYear = createAttendanceConfigYear('2025-2026', false);

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $previousYear->SY_ID,
        'month' => 8,
        'school_days' => 19,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.attendance-config.index', [
        'sy_id' => $previousYear->SY_ID,
    ]));

    $response->assertOk();
    $response->assertSee($currentYear->school_year);
    $response->assertSee($previousYear->school_year);
    $response->assertSee('value="'.$previousYear->SY_ID.'"', false);
    $response->assertSee('>19</p>', false);
    $response->assertSee('data-editing="false"', false);
});

test('saving school days rejects values above 31', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.invalid');
    $year = createAttendanceConfigYear();

    $schoolDays = array_fill_keys(Month::ids(), '0');
    $schoolDays[6] = '32';

    $response = $this->actingAs($admin)
        ->from(route('admin.attendance-config.index'))
        ->put(route('admin.attendance-config.update'), [
            'SY_ID' => $year->SY_ID,
            'school_days' => $schoolDays,
        ]);

    $response->assertRedirect(route('admin.attendance-config.index'));
    $response->assertSessionHasErrors('school_days.6');
    expect(AcademicYearAttendanceSetting::query()->where('SY_ID', $year->SY_ID)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.attendance-config.index'))
        ->assertSee('data-editing="true"', false)
        ->assertSee('title="Edit"', false);
});

test('saving school days rejects non-numeric and 3-digit values', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.digits');
    $year = createAttendanceConfigYear();

    $letters = array_fill_keys(Month::ids(), '0');
    $letters[1] = 'ab';

    $this->actingAs($admin)
        ->from(route('admin.attendance-config.index'))
        ->put(route('admin.attendance-config.update'), [
            'SY_ID' => $year->SY_ID,
            'school_days' => $letters,
        ])
        ->assertRedirect(route('admin.attendance-config.index'))
        ->assertSessionHasErrors('school_days.1');

    $threeDigits = array_fill_keys(Month::ids(), '0');
    $threeDigits[2] = '100';

    $this->actingAs($admin)
        ->from(route('admin.attendance-config.index'))
        ->put(route('admin.attendance-config.update'), [
            'SY_ID' => $year->SY_ID,
            'school_days' => $threeDigits,
        ])
        ->assertRedirect(route('admin.attendance-config.index'))
        ->assertSessionHasErrors('school_days.2');

    expect(AcademicYearAttendanceSetting::query()->where('SY_ID', $year->SY_ID)->exists())->toBeFalse();
});

test('attendance configuration page shows an empty state when no academic year exists', function () {
    $admin = createAttendanceConfigAdmin('admin.attendance.empty');

    $response = $this->actingAs($admin)->get(route('admin.attendance-config.index'));

    $response->assertOk();
    $response->assertSee('Add an academic year first to configure monthly school days.');
    $response->assertDontSee('Save Attendance Configuration');
    $response->assertDontSee('id="attendanceEditButton"', false);
});
