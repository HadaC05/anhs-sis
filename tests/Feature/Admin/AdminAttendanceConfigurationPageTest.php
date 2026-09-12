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
    $response->assertSee('January through December');
    $response->assertSee('2026-2027');
    $response->assertSeeInOrder(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
    $response->assertSee('data-editing="false"', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('id="attendanceEditButton"', false);
    $response->assertSee('startAttendanceEdit', false);
    $response->assertSee('cancelAttendanceEdit', false);
    $response->assertSee('Save School Days');
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
    $response->assertDontSee('Save School Days');
    $response->assertDontSee('id="attendanceEditButton"', false);
});
