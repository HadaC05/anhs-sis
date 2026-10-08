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

        SecondSemesterEnrollment::enroll($student, $request->validated('elective_ids'));

        return redirect()
            ->route('student.second-semester-enrollment.create')
            ->with('status', 'Your Grade 11 second-semester enrollment was completed successfully.');
    }
}
