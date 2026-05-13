<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectionReason;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Support\StudentCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ApplicationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, [10, 20, 30, 50, 100], true)) {
            $perPage = 20;
        }
        $search = trim($request->string('search')->toString());

        $applications = StudentApplication::query()
            ->with(['rejectionReason', 'reviewer'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest('submitted_at')
            ->paginate($perPage)
            ->withQueryString();

        $rejectionReasons = RejectionReason::query()
            ->where('status', true)
            ->orderBy('reason_name')
            ->get();

        return view('users.admin.applications.index', [
            'applications' => $applications,
            'rejectionReasons' => $rejectionReasons,
            'perPage' => $perPage,
        ]);
    }

    public function approve(Request $request, StudentApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->withErrors(['review' => 'Only pending applications can be approved.']);
        }

        $studentRole = Role::query()->firstOrCreate(['role_name' => 'student']);
        $enrollmentYear = (int) now()->year;
        $username = StudentCredentials::usernameFromLrn($application->lrn);
        $defaultPassword = StudentCredentials::defaultPassword(
            $application->first_name,
            $application->last_name,
            $enrollmentYear,
        );
        $email = $application->email ?: $this->generateFallbackEmail($application->lrn);

        if (Student::query()->where('username', $username)->whereKeyNot($application->id)->exists()) {
            return back()->withErrors([
                'review' => "Cannot approve application because the username {$username} is already assigned to another account.",
            ]);
        }

        DB::transaction(function () use ($application, $studentRole, $username, $defaultPassword, $email, $request): void {
            $application->update([
                'role_id' => $studentRole->id,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($defaultPassword),
                'change_password' => true,
                'password_changed_at' => null,
                'status' => 'approved',
                'activated_by' => $request->user()?->getKey(),
                'activated_at' => now(),
                'rejection_reason_id' => null,
            ]);
        });

        return back()->with('status', "Application approved. Username: {$username}. Default password: {$defaultPassword}. The student will be asked to change it after first login.");
    }

    public function reject(Request $request, StudentApplication $application): RedirectResponse
    {
        if ($application->status !== 'pending') {
            return back()->withErrors(['review' => 'Only pending applications can be rejected.']);
        }

        $validated = $request->validate([
            'rejection_reason_id' => ['required', 'exists:rejection_reasons,id'],
        ]);

        $application->update([
            'status' => 'rejected',
            'activated_by' => $request->user()?->getKey(),
            'activated_at' => now(),
            'rejection_reason_id' => $validated['rejection_reason_id'],
        ]);

        return back()->with('status', 'Application rejected successfully.');
    }

    private function generateFallbackEmail(string $lrn): string
    {
        $base = 'student.'.$lrn;
        $candidate = $base.'@anhs.local';
        $counter = 1;

        while (Student::query()->where('email', $candidate)->exists()) {
            $candidate = $base.'.'.$counter.'@anhs.local';
            $counter++;
        }

        return $candidate;
    }
}
