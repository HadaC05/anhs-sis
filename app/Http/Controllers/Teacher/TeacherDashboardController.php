<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeStatus;
use App\Models\Section;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $staff = $request->user();
        $activeYear = AcademicYear::query()->where('status', true)->first();

        // Never present historical assignments as current work without an active year.
        $assignments = $activeYear
            ? TeacherSubjectAssignment::query()
                ->withoutMapehParents()
                ->where('staff_ID', $staff->staff_id)
                ->where('SY_ID', $activeYear->SY_ID)
                ->with(['section', 'curriculumSubject.subject'])
                ->withGradeStatusCounts()
                ->orderBy('section_ID')->orderBy('assignment_ID')->get()
            : collect();

        $advisorySections = $activeYear
            ? Section::query()
                ->where('staff_ID', $staff->staff_id)
                ->where('SY_ID', $activeYear->SY_ID)
                ->with('gradeLevel')
                ->withCount(['enrollments as active_enrollments_count' => fn ($query) => $query
                    ->where('SY_ID', $activeYear->SY_ID)
                    ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())])
                ->orderBy('name')->get()
            : collect();

        $sectionIds = $advisorySections->pluck('section_ID')->merge($assignments->pluck('section_ID'))->unique();
        $totalStudents = $activeYear
            ? Enrollment::query()->where('SY_ID', $activeYear->SY_ID)
                ->whereIn('section_ID', $sectionIds)
                ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
                ->distinct()->count('student_ID')
            : 0;

        $returnedAssignments = $assignments->filter(fn ($assignment) => $assignment->rejected_grades_count > 0);
        $draftAssignments = $assignments->filter(fn ($assignment) => $assignment->draft_grades_count > 0);
        $ungradedAssignments = $assignments->filter(fn ($assignment) => (int) $assignment->grades_count === 0);
        $gradeTotals = collect(GradeStatus::slugs())
            ->mapWithKeys(fn ($status) => [$status => (int) $assignments->sum("{$status}_grades_count")]);

        return view('users.teacher.dashboard', compact(
            'staff', 'activeYear', 'assignments', 'advisorySections', 'totalStudents',
            'returnedAssignments', 'draftAssignments', 'ungradedAssignments', 'gradeTotals',
        ));
    }
}
