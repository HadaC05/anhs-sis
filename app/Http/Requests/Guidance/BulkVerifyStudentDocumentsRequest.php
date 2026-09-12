<?php

namespace App\Http\Requests\Guidance;

use App\Models\Enrollment;
use App\Models\Staff;
use App\Models\StudentDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkVerifyStudentDocumentsRequest extends FormRequest
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
            'enrollment_ID' => ['required', 'integer', 'exists:enrollments,enrollment_ID'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:student_documents,doc_ID'],
            'from_section' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_ids.required' => 'Select at least one document to verify.',
            'document_ids.min' => 'Select at least one document to verify.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $enrollment = Enrollment::query()->find($this->integer('enrollment_ID'));

            if (! $enrollment) {
                return;
            }

            $invalid = StudentDocument::query()
                ->whereIn('doc_ID', $this->input('document_ids', []))
                ->where('student_ID', '!=', $enrollment->student_ID)
                ->exists();

            if ($invalid) {
                $validator->errors()->add('document_ids', 'Selected documents do not belong to this enrollment.');
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        $enrollment = Enrollment::query()->find($this->integer('enrollment_ID'));

        if ($enrollment) {
            return route('guidance.enrollments.show', array_filter([
                'enrollment' => $enrollment,
                'step' => 'documents',
                'from_section' => $this->input('from_section'),
            ]));
        }

        return parent::getRedirectUrl();
    }
}
