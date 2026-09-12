<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use App\Models\StudentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('suffix') === '') {
            $this->merge(['suffix' => null]);
        }

        if ($this->input('birthdate') === '') {
            $this->merge(['birthdate' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'birthdate' => [
                'required',
                'date',
                'after_or_equal:'.Staff::EARLIEST_BIRTHDATE,
                'before_or_equal:'.Staff::LATEST_BIRTHDATE,
            ],
            'username' => ['required', 'string', 'max:255', 'unique:staffs,username'],
            'email' => ['required', 'email', 'max:255', 'unique:staffs,email'],
            'password' => ['required', 'string', Password::defaults()],
            'password_confirmation' => ['required', 'string', 'same:password'],
            'role' => ['required', Rule::exists('roles', 'role_name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'suffix.in' => 'Please select a valid suffix.',
            'birthdate.required' => 'The birthdate field is required.',
            'birthdate.after_or_equal' => 'The birthdate year must be 1925 or later.',
            'birthdate.before_or_equal' => 'The birthdate year must be 2020 or earlier.',
            'password_confirmation.required' => 'Please confirm the password.',
            'password_confirmation.same' => 'The confirm password must match the password.',
        ];
    }
}
