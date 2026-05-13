<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\View\View;

class PrincipalDashboardController extends Controller
{
    public function index(): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();

        $enrollments = Enrollment::query()
            ->when($activeYear, fn ($query) => $query->where('SY_ID', $activeYear->SY_ID));

        $activeEnrollments = (clone $enrollments)
            ->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);

        $totalStudents = (clone $activeEnrollments)->count();
        $enrolledCount = (clone $enrollments)->where('enrollment_status', 'enrolled')->count();
        $temporaryCount = (clone $enrollments)->where('enrollment_status', 'temporarily_enrolled')->count();
        $pendingCount = (clone $enrollments)->where('enrollment_status', 'pending')->count();

        $sections = Section::query()
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
            }])
            ->when($activeYear, fn ($query) => $query->where('SY_ID', $activeYear->SY_ID))
            ->get();

        $sectionCount = $sections->count();
        $totalCapacity = $sections->sum('capacity');
        $totalEnrolled = $sections->sum('active_enrollments_count');
        $capacityRate = $totalCapacity > 0 ? round(($totalEnrolled / $totalCapacity) * 100) : 0;

        $teacherRoleId = Role::query()->where('role_name', 'teacher')->value('id');
        $teacherCount = $teacherRoleId
            ? Staff::query()->where('role_id', $teacherRoleId)->where('status', 'active')->count()
            : 0;

        $submittedGradesCount = StudentSubjectGrade::query()
            ->where('status', 'submitted')
            ->when($activeYear, function ($query) use ($activeYear): void {
                $query->whereHas('assignment', fn ($assignment) => $assignment->where('SY_ID', $activeYear->SY_ID));
            })
            ->distinct('assignment_ID')
            ->count('assignment_ID');

        $subjectAssignmentCount = TeacherSubjectAssignment::query()
            ->when($activeYear, fn ($query) => $query->where('SY_ID', $activeYear->SY_ID))
            ->count();

        $gradeLevelStats = (clone $activeEnrollments)
            ->selectRaw('grade_ID, COUNT(*) as total')
            ->groupBy('grade_ID')
            ->orderBy('grade_ID')
            ->pluck('total', 'grade_ID');

        $gradeLevelStats = GradeLevel::query()
            ->whereIn('grade_ID', $gradeLevelStats->keys())
            ->get()
            ->mapWithKeys(fn (GradeLevel $grade): array => [
                $grade->value => (int) $gradeLevelStats[$grade->grade_ID],
            ]);

        $recentEnrollments = (clone $enrollments)
            ->with(['student.application', 'section', 'cluster'])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        return view('users.principal.dashboard', [
            'activeYear' => $activeYear,
            'totalStudents' => $totalStudents,
            'enrolledCount' => $enrolledCount,
            'temporaryCount' => $temporaryCount,
            'pendingCount' => $pendingCount,
            'sectionCount' => $sectionCount,
            'totalCapacity' => $totalCapacity,
            'totalEnrolled' => $totalEnrolled,
            'capacityRate' => $capacityRate,
            'teacherCount' => $teacherCount,
            'submittedGradesCount' => $submittedGradesCount,
            'subjectAssignmentCount' => $subjectAssignmentCount,
            'gradeLevelStats' => $gradeLevelStats,
            'recentEnrollments' => $recentEnrollments,
        ]);
    }
}
