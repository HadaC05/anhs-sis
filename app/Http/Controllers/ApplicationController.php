<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckApplicationStatusRequest;
use App\Http\Requests\StoreStudentEnrollmentRequest;
use App\Models\EnrollmentStatus;
use App\Models\StudentApplication;
use App\Support\StudentCredentials;
use App\Support\StudentEnrollmentForm;
use App\Support\StudentEnrollmentRegistrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function create(): View
    {
        return view('users.student.enrollment', StudentEnrollmentForm::viewData());
    }

    public function checkLrn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'LRN' => ['required', 'digits:12'],
        ]);

        return response()->json(StudentEnrollmentForm::lrnAvailability($validated['LRN']));
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return response()->json(StudentEnrollmentForm::emailAvailability($validated['email']));
    }

    public function store(StoreStudentEnrollmentRequest $request): RedirectResponse
    {
        StudentEnrollmentRegistrar::register($request->validated());

        return redirect()->route('register')
            ->with('registration_submitted', true)
            ->with('status', 'Your enrollment has been submitted successfully.');
    }

    public function checkStatus(CheckApplicationStatusRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $application = StudentApplication::query()
            ->where('lrn', $validated['status_lrn'])
            ->whereDate('birthdate', $validated['status_birthdate'])
            ->latest('submitted_at')
            ->first();

        if (! $application) {
            return back()->withErrors([
                'status_lookup' => 'No application found for the provided details.',
            ], 'statusCheck');
        }

        $status = strtolower((string) $application->status);
        $enrollment = $application->enrollments()
            ->with('academicYear')
            ->latest('created_at')
            ->first();
        $enrollmentStatus = $enrollment?->enrollment_status;

        $message = match (true) {
            $enrollmentStatus === EnrollmentStatus::TEMPORARILY_ENROLLED => 'You are temporarily enrolled. Sign in to upload your required documents, then change your password after your first sign in.',
            $enrollmentStatus === EnrollmentStatus::ENROLLED => 'You are enrolled. Use the login details below and change your password after your first sign in.',
            $status === 'pending' => 'Your application is pending. Please wait for verification.',
            $status === 'approved' => 'Your application is approved. Use the login details below and change your password after your first sign in.',
            $status === 'rejected' => 'Your application was rejected. Please contact the school for guidance on re-application.',
            default => 'Your application status is currently under review.',
        };

        $showLogin = in_array($enrollmentStatus, [
            EnrollmentStatus::TEMPORARILY_ENROLLED,
            EnrollmentStatus::ENROLLED,
        ], true) || $status === 'approved';

        if (! $showLogin) {
            return back()->with('application_status_message', $message);
        }

        $enrollmentYear = StudentCredentials::enrollmentYear($enrollment);

        return back()->with([
            'application_status_message' => $message,
            'application_status_enrollment_year' => $enrollmentYear,
        ]);
    }
}
