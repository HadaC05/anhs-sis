<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $baseQuery = Enrollment::query()
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);

        $totalStudents = (clone $baseQuery)->count();
        $enrolledCount = (clone $baseQuery)->where('enrollment_status', 'enrolled')->count();
        $temporaryCount = (clone $baseQuery)->where('enrollment_status', 'temporarily_enrolled')->count();
        $recentStudents = (clone $baseQuery)
            ->with(['student.application'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('users.registrar.dashboard', [
            'activeYear' => $activeYear,
            'totalStudents' => $totalStudents,
            'enrolledCount' => $enrolledCount,
            'temporaryCount' => $temporaryCount,
            'recentStudents' => $recentStudents,
        ]);
    }

    public function students(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $status = $request->string('status')->toString();
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $gradeId = GradeLevel::idForValue($gradeLevel);

        $students = Enrollment::query()
            ->with(['student.application', 'section.gradeLevel', 'gradeLevel', 'cluster', 'academicYear'])
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled'])
            ->when($status !== '', fn ($q) => $q->where('enrollment_status', $status))
            ->when($gradeId, fn ($q) => $q->where('grade_ID', $gradeId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($appQuery) use ($search): void {
                            $appQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('grade_ID')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.registrar.students', [
            'activeYear' => $activeYear,
            'students' => $students,
            'gradeLevels' => GradeLevel::options(),
        ]);
    }


    public function show(\App\Models\Student $student): \Illuminate\View\View
    {
        $student->load([
            'user',
            'application',
            'profile',
            'guardians',
            'addresses',
            'documents',
            'enrollments' => function ($query) {
                $query->with([
                    'academicYear',
                    'section',
                    'cluster',
                    'grades.assignment.curriculumSubject.subject',
                    'grades.assignment.staff.user',
                ])->latest('created_at');
            },
        ]);

        return view('users.registrar.students-show', [
            'student' => $student,
        ]);
    }


    public function gradeApprovals(Request $request): \Illuminate\View\View
    {
        $assignments = \App\Models\TeacherSubjectAssignment::query()
            ->with([
                'section',
                'curriculumSubject.subject',
                'staff.user',
                'grades' => function ($query): void {
                    $query->where('status', 'submitted');
                },
            ])
            ->whereHas('grades', function ($query): void {
                $query->where('status', 'submitted');
            })
            ->orderBy('section_ID')
            ->get();

        return view('users.registrar.grade-approvals', [
            'assignments' => $assignments,
        ]);
    }

    public function approveGrades(Request $request, \App\Models\TeacherSubjectAssignment $assignment)
    {
        \App\Models\StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->where('status', 'submitted')
            ->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->staff_id,
                'reviewed_at' => now(),
            ]);

        return back()->with('status', 'Grades approved and released to students.');
    }

    public function rejectGrades(Request $request, \App\Models\TeacherSubjectAssignment $assignment)
    {
        \App\Models\StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->where('status', 'submitted')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->staff_id,
                'reviewed_at' => now(),
            ]);

        return back()->with('status', 'Grades returned to teacher for updates.');
    }
}
