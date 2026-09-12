<?php

namespace App\Http\Requests\Student;

use App\Concerns\PasswordValidationRules;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentAccountPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user() instanceof Student;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Enter your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'Enter a new password.',
            'password.confirmed' => 'The confirm password must match the new password.',
        ];
    }
}
