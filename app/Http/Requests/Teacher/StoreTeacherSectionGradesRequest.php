<?php

namespace App\Http\Requests\Teacher;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherSectionGradesRequest extends FormRequest
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
            'grades' => ['nullable', 'array'],
            'grades.*.*.grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grades.*.*.grade.numeric' => 'Grades must be numeric.',
            'grades.*.*.grade.min' => 'Grades must be between 0 and 100.',
            'grades.*.*.grade.max' => 'Grades must be between 0 and 100.',
        ];
    }
}
