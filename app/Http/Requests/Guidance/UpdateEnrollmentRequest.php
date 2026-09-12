<?php

namespace App\Http\Requests\Guidance;

use App\Models\Enrollment;
use App\Models\Staff;
use App\Support\StudentEnrollmentForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
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
        return StudentEnrollmentForm::rules($this->studentId());
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
        $enrollment = $this->enrollment();

        StudentEnrollmentForm::applyAfterValidation(
            $validator,
            $enrollment?->academicYear,
            false,
        );
    }

    private function enrollment(): ?Enrollment
    {
        $enrollment = $this->route('enrollment');

        return $enrollment instanceof Enrollment ? $enrollment : null;
    }

    private function studentId(): ?int
    {
        return $this->enrollment()?->student_ID;
    }
}
