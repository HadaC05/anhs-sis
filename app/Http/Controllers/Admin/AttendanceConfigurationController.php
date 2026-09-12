<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceConfigurationRequest;
use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Month;
use App\Support\Sf9AttendanceSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('status')
            ->orderByDesc('start_date')
            ->orderByDesc('SY_ID')
            ->get();

        $currentYear = $academicYears->firstWhere('status', true) ?? $academicYears->first();
        $selectedId = (int) $request->integer('sy_id', $currentYear?->SY_ID ?? 0);
        $selectedYear = $academicYears->firstWhere('SY_ID', $selectedId) ?? $currentYear;
        $schoolDays = Sf9AttendanceSummary::schoolDaysForAcademicYear($selectedYear?->SY_ID);
        $configuredMonths = collect($schoolDays)->filter(fn (int $days): bool => $days > 0)->count();

        return view('users.admin.attendance-config', [
            'academicYears' => $academicYears,
            'currentYear' => $currentYear,
            'selectedYear' => $selectedYear,
            'months' => Month::labels(),
            'schoolDays' => $schoolDays,
            'configuredMonths' => $configuredMonths,
            'totalSchoolDays' => array_sum($schoolDays),
        ]);
    }

    public function update(UpdateAttendanceConfigurationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $syId = (int) $validated['SY_ID'];

        DB::transaction(function () use ($validated, $syId): void {
            foreach ($validated['school_days'] as $month => $schoolDays) {
                AcademicYearAttendanceSetting::query()->updateOrCreate(
                    [
                        'SY_ID' => $syId,
                        'month' => (int) $month,
                    ],
                    ['school_days' => (int) $schoolDays]
                );
            }
        });

        return redirect()
            ->route('admin.attendance-config.index', ['sy_id' => $syId])
            ->with('success', 'School days updated successfully.');
    }
}
