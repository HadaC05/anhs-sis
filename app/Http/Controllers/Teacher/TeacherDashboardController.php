<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $staff = $request->user();
        $staff?->load('sections');
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $sectionsCount = 0;
        $studentsCount = 0;
        $subjectsCount = 0;

        if ($staff) {
            $sections = Section::query()
                ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                    $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
                }])
                ->where('staff_ID', $staff->staff_id)
                ->when($activeYear, function ($query) use ($activeYear): void {
                    $query->where('SY_ID', $activeYear->SY_ID);
                })
                ->get();

            $sectionsCount = $sections->count();
            $studentsCount = $sections->sum('active_enrollments_count');
            $subjectsCount = \App\Models\TeacherSubjectAssignment::query()
                ->where('staff_ID', $staff->staff_id)
                ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
                ->count();
        }

        return view('users.teacher.dashboard', [
            'staff' => $staff,
            'activeYear' => $activeYear,
            'sectionsCount' => $sectionsCount,
            'studentsCount' => $studentsCount,
            'subjectsCount' => $subjectsCount,
        ]);
    }
}
