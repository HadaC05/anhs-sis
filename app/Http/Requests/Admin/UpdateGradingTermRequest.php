<?php

namespace App\Http\Requests\Admin;

use App\Models\GradingTerm;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradingTermRequest extends FormRequest
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
        $term = $this->route('term');

        return [
            'label' => [
                'required',
                'string',
                'max:50',
                Rule::unique('grading_terms', 'label')->ignore(
                    $term instanceof GradingTerm ? $term->term_ID : null,
                    'term_ID',
                ),
            ],
            'sort_order' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'The term label is required.',
            'label.unique' => 'This term label already exists.',
            'sort_order.required' => 'The term order is required.',
            'sort_order.min' => 'The term order must be at least 1.',
            'sort_order.max' => 'The term order may not be greater than 99.',
        ];
    }
}
