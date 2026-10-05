<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\SchoolInformation;
use App\Models\Section;
use App\Models\SectionAttendanceSetting;
use App\Support\Sf9AttendanceSummary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'grade_id' => ['nullable', 'integer', Rule::exists(GradeLevel::class, 'grade_ID')],
            'download' => ['nullable', Rule::in(['csv'])],
        ]);
        $years = AcademicYear::query()->orderByDesc('SY_ID')->get();
        $year = isset($filters['academic_year_id']) ? $years->firstWhere('SY_ID', $filters['academic_year_id']) : ($years->firstWhere('status', true) ?? $years->first());
        $months = $year?->attendanceMonths() ?? [];
        $month = (int) ($filters['month'] ?? (isset($months[now()->month]) ? now()->month : array_key_first($months)));
        if ($year) {
            validator(['month' => $month], ['month' => [Rule::in(array_keys($months))]])->validate();
        }
        $filters = ['academic_year_id' => $year?->SY_ID, 'month' => $month ?: null, 'grade_id' => $filters['grade_id'] ?? null];
        $sections = Section::query()->where('SY_ID', $year?->SY_ID ?? 0)
            ->when($filters['grade_id'], fn ($query, $id) => $query->where('grade_ID', $id))
            ->with(['gradeLevel', 'adviser', 'enrollments' => fn ($query) => $query->where('SY_ID', $year?->SY_ID ?? 0)
                ->with(['student', 'enrollmentStatus', 'learnerType'])])
            ->orderBy('grade_ID')->orderBy('name')->get();
        $attendance = EnrollmentMonthlyAttendance::query()->where('month', $month)
            ->whereIn('enrollment_ID', $sections->flatMap(fn ($section) => $section->enrollments->pluck('enrollment_ID')))
            ->get()->keyBy('enrollment_ID');
        $defaultDays = Sf9AttendanceSummary::schoolDaysForAcademicYear($year?->SY_ID)[$month] ?? 0;
        $sectionDays = SectionAttendanceSetting::query()->where('SY_ID', $year?->SY_ID ?? 0)
            ->where('month', $month)->whereNotNull('source_sf2_upload_id')->pluck('school_days', 'section_ID');
        $rows = collect();
        foreach ($sections as $section) {
            $learners = $section->enrollments->filter(fn ($enrollment) => in_array($enrollment->enrollment_status, [
                ...EnrollmentStatus::activeSlugs(), EnrollmentStatus::TRANSFERRED_OUT, EnrollmentStatus::DROPPED_OUT,
                EnrollmentStatus::WITHDRAWN,
            ], true) || $attendance->has($enrollment->enrollment_ID));
            $groups = ['Male' => $learners->filter(fn ($e) => in_array(strtolower(trim($e->student?->sex ?? '')), ['male', 'm'], true)),
                'Female' => $learners->filter(fn ($e) => in_array(strtolower(trim($e->student?->sex ?? '')), ['female', 'f'], true))];
            $unknown = $learners->diff($groups['Male']->merge($groups['Female']));
            if ($unknown->isNotEmpty()) {
                $groups['Unspecified'] = $unknown;
            }
            $groups['Total'] = $learners;
            foreach ($groups as $sex => $group) {
                $records = $attendance->only($group->pluck('enrollment_ID')->all());
                $present = (int) $records->sum('days_present');
                $absent = (int) $records->sum('days_absent');
                $days = (int) ($sectionDays[$section->section_ID] ?? $defaultDays);
                $rows->push([
                    'grade' => $section->gradeLevel?->grade_label ?? 'Unassigned grade',
                    'section' => $section->name,
                    'adviser' => trim(($section->adviser?->first_name ?? '').' '.($section->adviser?->last_name ?? '')) ?: 'Unassigned',
                    'sex' => $sex,
                    'registered' => $group->filter(fn ($e) => in_array($e->enrollment_status, EnrollmentStatus::activeSlugs(), true))->count(),
                    'learners' => $group->count(), 'recorded' => $records->count(), 'school_days' => $days,
                    'present' => $records->isEmpty() ? null : $present,
                    'absent' => $records->isEmpty() ? null : $absent,
                    'average' => $records->isNotEmpty() && $days > 0 ? $present / $days : null,
                    'rate' => $present + $absent > 0 ? $present / ($present + $absent) * 100 : null,
                    'transferees' => $group->where('learner_type', LearnerType::TRANSFEREE)->count(),
                    'transferred_out' => $group->where('enrollment_status', EnrollmentStatus::TRANSFERRED_OUT)->count(),
                    'dropped_out' => $group->where('enrollment_status', EnrollmentStatus::DROPPED_OUT)->count(),
                    'withdrawn' => $group->where('enrollment_status', EnrollmentStatus::WITHDRAWN)->count(),
                ]);
            }
        }
        $totals = $rows->where('sex', 'Total');
        $summary = collect(['registered', 'learners', 'recorded', 'transferees', 'transferred_out', 'dropped_out', 'withdrawn'])
            ->mapWithKeys(fn ($key) => [$key => $totals->sum($key)])->all();
        $summary['rate'] = $totals->sum('present') + $totals->sum('absent') > 0
            ? $totals->sum('present') / ($totals->sum('present') + $totals->sum('absent')) * 100 : null;
        $summary['present'] = $summary['recorded'] > 0 ? $totals->sum('present') : null;
        $summary['absent'] = $summary['recorded'] > 0 ? $totals->sum('absent') : null;
        $summary['average'] = $summary['recorded'] > 0 && ! $totals->contains(fn ($row) => $row['recorded'] > 0 && $row['school_days'] === 0)
            ? $totals->sum('average') : null;
        $summary['school_days'] = null;
        $school = SchoolInformation::query()->first();
        if ($request->input('download') === 'csv') {
            return response()->streamDownload(function () use ($rows, $year, $months, $month) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                $write = function (array $values) use ($file) {
                    fputcsv($file, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value, $values), ',', '"', '');
                };
                $write(['Attendance and Learner Movement', $year?->school_year, $months[$month] ?? '']);
                $write(['Movement columns are current status snapshots, not monthly movements. Attendance rate uses recorded present and absent days.']);
                $write(['Grade', 'Section', 'Adviser', 'Sex', 'Currently registered', 'Learners in scope', 'Attendance records', 'School days', 'Days present', 'Days absent', 'Recorded daily average', 'Recorded attendance %', 'Transferee learners', 'Transferred out', 'Dropped out', 'Withdrawn']);
                foreach ($rows as $row) {
                    $write(array_values(array_map(fn ($value) => is_float($value) ? round($value, 2) : $value, $row)));
                }
                fclose($file);
            }, 'attendance-movement-'.($year?->SY_ID ?? 'none').'-'.$month.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('users.principal.attendance-report', compact('years', 'year', 'months', 'month', 'filters', 'rows', 'summary', 'school') + [
            'grades' => GradeLevel::query()->orderBy('grade_ID')->get(),
        ]);
    }
}
