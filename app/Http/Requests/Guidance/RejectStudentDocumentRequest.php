<?php

namespace App\Http\Requests\Guidance;

use App\Models\Enrollment;
use App\Models\Staff;
use App\Models\StudentDocument;
use Illuminate\Foundation\Http\FormRequest;

class RejectStudentDocumentRequest extends FormRequest
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
            'return_reason_ID' => ['required', 'integer', 'exists:document_return_reasons,reason_ID'],
            'from_section' => ['nullable', 'integer'],
            'returning_document_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'return_reason_ID.required' => 'Please select a return reason.',
            'return_reason_ID.exists' => 'The selected return reason is invalid.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $document = $this->route('document');

        if ($document instanceof StudentDocument) {
            $enrollment = Enrollment::query()
                ->where('student_ID', $document->student_ID)
                ->latest('created_at')
                ->first();

            if ($enrollment) {
                return route('guidance.enrollments.show', array_filter([
                    'enrollment' => $enrollment,
                    'step' => 'documents',
                    'from_section' => $this->input('from_section'),
                ]));
            }
        }

        return parent::getRedirectUrl();
    }
}
