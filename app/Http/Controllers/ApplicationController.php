<?php

namespace App\Http\Controllers;

use App\Models\StudentApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function create(): View
    {
        return view('applications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lrn' => ['required', 'digits:12'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['required', 'date'],
        ]);

        $existingPending = StudentApplication::query()
            ->where('lrn', $validated['lrn'])
            ->whereDate('birthdate', $validated['birthdate'])
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()->withErrors([
                'application' => 'An active application already exists for this LRN and birthdate.',
            ])->withInput();
        }

        StudentApplication::create([
            ...$validated,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('login')->with('status', 'Application submitted. Please wait for admin verification before login credentials are issued.');
    }

    public function checkStatus(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'status_lrn' => ['required', 'digits:12'],
            'status_birthdate' => ['required', 'date'],
        ])->validateWithBag('statusCheck');

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

        $message = match ($status) {
            'pending' => 'Your application is pending. Please wait for verification.',
            'approved' => 'Your application is approved. Use the login details below and change your password after your first sign in.',
            'rejected' => 'Your application was rejected. Please contact the school for guidance on re-application.',
            default => 'Your application status is currently under review.',
        };

        if ($status !== 'approved') {
            return back()->with('application_status_message', $message);
        }

        $enrollmentYear = (int) ($application->activated_at?->year ?? now()->year);

        return back()->with([
            'application_status_message' => $message,
            'application_status_enrollment_year' => $enrollmentYear,
        ]);
    }
}
