<?php

namespace App\Http\Requests\Guidance;

use App\Models\EnrollmentStatus;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(EnrollmentStatus::slugs())],
            'from_section' => ['nullable', 'integer'],
            'step' => ['nullable', 'string', Rule::in(['enrollment', 'personal', 'addresses', 'parents', 'documents'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select an enrollment status.',
            'status.in' => 'The selected enrollment status is invalid.',
        ];
    }
}
