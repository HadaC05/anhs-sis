<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSecondSemesterEnrollmentRequest;
use App\Models\Student;
use App\Support\SecondSemesterEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecondSemesterEnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        /** @var Student $student */
        $student = $request->user();
        abort_unless($student->isSeniorHighStudent(), 404);

        return view('users.student.second-semester-enrollment', [
            'student' => $student,
            ...SecondSemesterEnrollment::context($student),
        ]);
    }

    public function store(StoreSecondSemesterEnrollmentRequest $request): RedirectResponse
    {
        /** @var Student $student */
        $student = $request->user();
        abort_unless($student->isSeniorHighStudent(), 404);

        $context = SecondSemesterEnrollment::context($student);

        if ($context['canComplete']) {
            SecondSemesterEnrollment::complete($student);

            return redirect()
                ->route('student.second-semester-enrollment.create')
                ->with('status', 'Congratulations! Your Grade 12 and Senior High School completion has been recorded.');
        }

        $enrollment = SecondSemesterEnrollment::enroll($student, $request->validated('elective_ids', []));
        $grade = $enrollment->getRelation('gradeLevel')?->grade_label ?? 'Senior High School';
        $semester = SecondSemesterEnrollment::semesterLabel($enrollment->gradingSemester?->key);

        return redirect()
            ->route('student.second-semester-enrollment.create')
            ->with('status', "Your {$grade} {$semester} enrollment was completed successfully.");
    }
}
