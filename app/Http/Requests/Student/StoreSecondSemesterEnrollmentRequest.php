<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecondSemesterEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'elective_ids' => ['required', 'array', 'min:1', 'max:2'],
            'elective_ids.*' => ['required', 'integer', 'distinct', 'exists:subjects,subject_ID'],
        ];
    }
}
