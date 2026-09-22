<?php

namespace App\Http\Controllers\Guidance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guidance\UpdateEnrollmentRequest;
use App\Http\Requests\StoreStudentEnrollmentRequest;
use App\Models\Enrollment;
use App\Support\StudentEnrollmentForm;
use App\Support\StudentEnrollmentRegistrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuidanceEnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        return view('users.student.enrollment', StudentEnrollmentForm::viewData([
            'layout' => 'users.guidance.layout',
            'pageTitle' => 'Register Student',
            'heading' => 'Register Student',
            'subheading' => 'Enroll a student from the guidance office. The student will be temporarily enrolled and can sign in to upload remaining documents.',
            'enrollmentStoreRoute' => 'guidance.enrollments.store',
            'checkLrnRoute' => route('guidance.enrollments.check-lrn'),
            'checkEmailRoute' => route('guidance.enrollments.check-email'),
            'cancelUrl' => route('guidance.enrollments.index'),
            'fromSectionId' => $request->input('from_section'),
        ]));
    }

    public function store(StoreStudentEnrollmentRequest $request): RedirectResponse
    {
        $enrollment = StudentEnrollmentRegistrar::register($request->validated());

        return $this->redirectToShow($enrollment, $request, [
            'status' => 'Student registered and temporarily enrolled. Login details were sent to the student email.',
        ]);
    }

    public function edit(Request $request, Enrollment $enrollment): View
    {
        $enrollment->load([
            'student.profile',
            'student.guardians',
            'student.addresses',
            'gradeLevel',
            'academicYear',
        ]);

        $student = $enrollment->student;

        return view('users.student.enrollment', StudentEnrollmentForm::viewData([
            'layout' => 'users.guidance.layout',
            'pageTitle' => 'Edit Enrollment',
            'heading' => 'Edit Enrollment Details',
            'subheading' => 'Update the student\'s enrollment, personal, address, and family information.',
            'enrollmentStoreUrl' => route('guidance.enrollments.update', $enrollment),
            'enrollmentMethod' => 'PUT',
            'checkLrnRoute' => route('guidance.enrollments.check-lrn'),
            'checkEmailRoute' => route('guidance.enrollments.check-email'),
            'cancelUrl' => route('guidance.enrollments.show', $this->showParameters($enrollment, $request)),
            'submitLabel' => 'Save Changes',
            'student' => $student,
            'application' => $student,
            'formDefaults' => StudentEnrollmentForm::defaults($enrollment),
            'ignoreStudentId' => $student?->id,
            'fromSectionId' => $request->input('from_section'),
            'currentEnrollment' => $enrollment,
        ]));
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $enrollment = StudentEnrollmentRegistrar::update($enrollment, $request->validated());

        return $this->redirectToShow($enrollment, $request, [
            'toast_success' => 'Enrollment details were updated.',
        ]);
    }

    public function checkLrn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'LRN' => ['required', 'digits:12'],
            'ignore_student' => ['nullable', 'integer'],
        ]);

        return response()->json(StudentEnrollmentForm::lrnAvailability(
            $validated['LRN'],
            isset($validated['ignore_student']) ? (int) $validated['ignore_student'] : null,
        ));
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'ignore_student' => ['nullable', 'integer'],
        ]);

        return response()->json(StudentEnrollmentForm::emailAvailability(
            $validated['email'],
            isset($validated['ignore_student']) ? (int) $validated['ignore_student'] : null,
        ));
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function redirectToShow(Enrollment $enrollment, Request $request, array $session = []): RedirectResponse
    {
        return redirect()
            ->route('guidance.enrollments.show', $this->showParameters($enrollment, $request))
            ->with($session);
    }

    /**
     * @return array<string, mixed>
     */
    private function showParameters(Enrollment $enrollment, Request $request): array
    {
        $parameters = ['enrollment' => $enrollment];

        if ($request->filled('from_section')) {
            $parameters['from_section'] = $request->input('from_section');
        }

        return $parameters;
    }
}
