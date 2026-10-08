<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeStatus;
use App\Models\Section;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrarDashboardData
{
    public static function forRequest(Request $request): array
    {
        $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists(AcademicYear::class, 'SY_ID')],
        ]);
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();
        $selectedYear = $request->filled('academic_year_id')
            ? $academicYears->firstWhere('SY_ID', $request->integer('academic_year_id'))
            : ($academicYears->firstWhere('status', true) ?? $academicYears->first());
        $yearId = $selectedYear?->SY_ID;
        $assignments = TeacherSubjectAssignment::query()->where('SY_ID', $yearId);
        $pending = (clone $assignments)->whereHas('grades', fn ($query) => $query->whereStatus(GradeStatus::SUBMITTED));
        $counts = StudentSubjectGrade::query()
            ->whereHas('assignment', fn ($query) => $query->where('SY_ID', $yearId))
            ->select('grade_status_ID')->selectRaw('COUNT(*) as total')
            ->groupBy('grade_status_ID')->pluck('total', 'grade_status_ID');
        $gradeCounts = [];
        foreach (GradeStatus::slugs() as $status) {
            $gradeCounts[$status] = (int) $counts->get(GradeStatus::idFor($status), 0);
        }

        return [
            'academicYears' => $academicYears,
            'selectedYear' => $selectedYear,
            'gradeCounts' => $gradeCounts,
            'pendingSubjects' => (clone $pending)->count(),
            'subjectCount' => (clone $assignments)->count(),
            'sectionCount' => Section::query()->where('SY_ID', $yearId)->count(),
            'studentCount' => Enrollment::query()->where('SY_ID', $yearId)
                ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
                ->distinct()->count('student_ID'),
            'reviewQueue' => $pending
                ->with(['section.gradeLevel', 'subject', 'staff'])
                ->withCount(['grades as pending_grades_count' => fn ($query) => $query->whereStatus(GradeStatus::SUBMITTED)])
                ->withMin(['grades as oldest_submission' => fn ($query) => $query->whereStatus(GradeStatus::SUBMITTED)], 'submitted_at')
                ->orderBy('oldest_submission')->orderBy('assignment_ID')->limit(6)->get(),
        ];
    }
}
