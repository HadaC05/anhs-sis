<?php

namespace App\Http\Requests\Admin;

use App\Models\GradingTerm;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeniorHighGradingTermRequest extends FormRequest
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
        $maxTerm = max(1, count(GradingTerm::seniorHighTerms()));

        return [
            'semester' => ['required', 'string', Rule::in(['first', 'second'])],
            'term' => ['required', 'integer', 'min:1', 'max:'.$maxTerm],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'semester.required' => 'Please select a semester.',
            'semester.in' => 'The selected semester is invalid.',
            'term.required' => 'Please select a term.',
            'term.min' => 'The selected term is invalid.',
            'term.max' => 'The selected term is invalid.',
        ];
    }
}
