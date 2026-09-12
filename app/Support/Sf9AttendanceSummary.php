<?php

namespace App\Support;

use App\Models\AcademicYearAttendanceSetting;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\Month;
use App\Models\Section;

class Sf9AttendanceSummary
{
    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return Month::labels();
    }

    /**
     * @return list<int>
     */
    public static function monthKeys(): array
    {
        return Month::ids();
    }

    /**
     * @return array<int, int>
     */
    public static function schoolDaysForAcademicYear(?int $syId): array
    {
        $defaults = array_fill_keys(self::monthKeys(), 0);

        if ($syId === null || $syId < 1) {
            return $defaults;
        }

        $records = AcademicYearAttendanceSetting::query()
            ->where('SY_ID', $syId)
            ->get()
            ->keyBy('month');

        foreach (self::monthKeys() as $month) {
            $defaults[$month] = (int) ($records->get($month)?->school_days ?? 0);
        }

        return $defaults;
    }

    /**
     * @return array<int, int>
     */
    public static function schoolDaysForSection(Section $section): array
    {
        return self::schoolDaysForAcademicYear((int) $section->SY_ID);
    }

    /**
     * @param  array<int, int>  $schoolDaysByMonth
     * @return array<string, mixed>
     */
    public static function forEnrollment(int $enrollmentId, array $schoolDaysByMonth): array
    {
        $records = EnrollmentMonthlyAttendance::query()
            ->where('enrollment_ID', $enrollmentId)
            ->get()
            ->keyBy('month');

        $daysPresent = [];
        $daysAbsent = [];
        $totalSchoolDays = 0;
        $totalPresent = 0;
        $totalAbsent = 0;

        foreach (self::monthKeys() as $month) {
            $schoolDays = $schoolDaysByMonth[$month] ?? 0;
            $record = $records->get($month);
            $present = (int) ($record?->days_present ?? 0);
            $absent = (int) ($record?->days_absent ?? 0);

            $daysPresent[$month] = $present > 0 ? $present : '';
            $daysAbsent[$month] = $absent > 0 ? $absent : '';

            $totalSchoolDays += $schoolDays;
            $totalPresent += $present;
            $totalAbsent += $absent;
        }

        return [
            'school_days' => $schoolDaysByMonth,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'total_school_days' => $totalSchoolDays > 0 ? $totalSchoolDays : '',
            'total_present' => $totalPresent > 0 ? $totalPresent : '',
            'total_absent' => $totalAbsent > 0 ? $totalAbsent : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        $emptyMonths = array_fill_keys(self::monthKeys(), '');

        return [
            'school_days' => $emptyMonths,
            'days_present' => $emptyMonths,
            'days_absent' => $emptyMonths,
            'total_school_days' => '',
            'total_present' => '',
            'total_absent' => '',
        ];
    }
}
