<?php

namespace App\Http\Requests\Admin;

use App\Models\GradingTerm;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingTermSettingsRequest extends FormRequest
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
            'max_terms' => ['required', 'integer', 'regex:/^[0-9]+$/', 'min:2', 'max:'.min(12, GradingTerm::query()->juniorHigh()->count())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_terms.required' => 'The maximum number of terms is required.',
            'max_terms.min' => 'The maximum number of terms must be at least 2.',
            'max_terms.max' => 'The maximum number of terms cannot exceed the existing Junior High terms (up to 12).',
        ];
    }
}
