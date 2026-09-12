<?php

namespace App\Http\Requests\Guidance;

use App\Models\PlacementStatus;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlacementStatusRequest extends FormRequest
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
            'placement_status' => ['required', 'string', Rule::in(PlacementStatus::slugs())],
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
            'placement_status.required' => 'Please select a placement test status.',
            'placement_status.in' => 'The selected placement test status is invalid.',
        ];
    }
}
