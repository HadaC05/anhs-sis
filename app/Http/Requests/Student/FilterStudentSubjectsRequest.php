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
            'grade_ID' => $this->filled('grade_ID') ? $this->input('grade_ID') : null,
            'semester_ID' => $this->filled('semester_ID') ? $this->input('semester_ID') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grade_ID' => ['nullable', 'integer', Rule::exists('grade_level', 'grade_ID')],
            'semester_ID' => ['nullable', 'integer', Rule::exists('grading_semesters', 'semester_ID')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grade_ID.exists' => 'The selected grade level is invalid.',
            'semester_ID.exists' => 'The selected semester is invalid.',
        ];
    }
}
