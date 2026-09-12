<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterStudentSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Student;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'SY_ID' => $this->filled('SY_ID') ? $this->input('SY_ID') : null,
            'semester_ID' => $this->filled('semester_ID') ? $this->input('semester_ID') : null,
            'term_ID' => $this->filled('term_ID') ? $this->input('term_ID') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'SY_ID' => ['nullable', 'integer', Rule::exists('academic_years', 'SY_ID')],
            'semester_ID' => ['nullable', 'integer', Rule::exists('grading_semesters', 'semester_ID')],
            'term_ID' => ['nullable', 'integer', Rule::exists('grading_terms', 'term_ID')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'SY_ID.exists' => 'The selected school year is invalid.',
            'semester_ID.exists' => 'The selected semester is invalid.',
            'term_ID.exists' => 'The selected term is invalid.',
        ];
    }
}
