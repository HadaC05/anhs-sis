<?php

namespace App\Http\Requests\Guidance;

use App\Models\EnrollmentStatus;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateEnrollmentStatusRequest extends FormRequest
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
            'enrollment_ids' => ['required', 'array'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,enrollment_ID'],
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
            'enrollment_ids.required' => 'Select at least one enrollment first.',
        ];
    }
}
