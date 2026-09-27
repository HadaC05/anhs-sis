<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    use PasswordValidationRules;

    private function staff(Request $request): Staff
    {
        abort_unless($request->user() instanceof Staff, 403);

        return $request->user();
    }

    public function edit(Request $request): View
    {
        $staff = $this->staff($request);
        $role = match ($staff->roleName()) {
            'guidance counselor' => 'guidance',
            'admin', 'principal', 'registrar', 'teacher' => $staff->roleName(),
            default => abort(403),
        };

        return view('users.staff.account', ['staff' => $staff, 'layout' => "users.{$role}.layout"]);
    }

    public function update(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthdate' => ['nullable', 'date', 'after_or_equal:'.Staff::EARLIEST_BIRTHDATE, 'before_or_equal:'.Staff::LATEST_BIRTHDATE],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('staffs', 'email')->ignore($staff->getKey(), 'staff_id'), Rule::unique('students', 'email')],
            'mobile_no' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
        unset($data['photo']);
        $oldPhoto = $staff->photo_path;
        $newPhoto = null;

        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('staff_photos', 'public');
            abort_unless($newPhoto, 500, 'Unable to save the profile photo.');
            $data['photo_path'] = $newPhoto;
        }

        try {
            $staff->update($data);
        } catch (\Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }
            throw $exception;
        }

        if ($newPhoto && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return to_route('staff.account')->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);
        $staff->forceFill([
            'password' => $data['password'],
            'change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
        $request->session()->regenerate();

        return to_route('staff.account')->with('status', 'Password updated.');
    }
}
