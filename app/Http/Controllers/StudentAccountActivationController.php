<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\UserActivationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StudentAccountActivationController extends Controller
{
    public function create(): View
    {
        return view('applications.claim-account');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'lrn' => ['required', 'digits:12'],
            'birthdate' => ['required', 'date'],
            'activation_code' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Student::query()
            ->where('username', $validated['username'])
            ->whereHas('role', fn ($query) => $query->where('role_name', 'student'))
            ->first();

        if (! $user) {
            return back()->withErrors([
                'claim' => 'Invalid activation details.',
            ])->withInput();
        }

        $application = $user;

        $isIdentityMatch = $application->status === 'approved'
            && $application->lrn === $validated['lrn']
            && optional($application->birthdate)->format('Y-m-d') === $validated['birthdate'];

        if (! $isIdentityMatch) {
            return back()->withErrors([
                'claim' => 'Invalid activation details.',
            ])->withInput();
        }

        $tokenHash = hash('sha256', strtoupper(trim($validated['activation_code'])));

        $activationToken = UserActivationToken::query()
            ->where('student_id', $user->id)
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $activationToken) {
            return back()->withErrors([
                'claim' => 'Invalid or expired activation code.',
            ])->withInput();
        }

        DB::transaction(function () use ($user, $activationToken, $validated): void {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'status' => 'active',
                'change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $activationToken->update([
                'used_at' => now(),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Account activated successfully.');
    }
}
