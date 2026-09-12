<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Support\StudentEnrollmentForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        StudentEnrollmentForm::prepare($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return StudentEnrollmentForm::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return StudentEnrollmentForm::messages();
    }

    public function withValidator(Validator $validator): void
    {
        StudentEnrollmentForm::applyAfterValidation(
            $validator,
            AcademicYear::query()->where('status', true)->first(),
        );
    }
}
