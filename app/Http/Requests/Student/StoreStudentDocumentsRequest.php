<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentDocumentsRequest extends FormRequest
{
    public const MAX_FILE_SIZE_KILOBYTES = 15360;

    public const MAX_FILE_SIZE_MEGABYTES = 15;

    public function authorize(): bool
    {
        return $this->user() instanceof Student;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fileRules = ['file', 'max:'.self::MAX_FILE_SIZE_KILOBYTES, 'mimes:pdf,jpg,jpeg,png'];

        return [
            'doc_type' => ['nullable', 'required_with:document', Rule::in(StudentDocument::typeKeys())],
            'document' => ['nullable', 'required_with:doc_type', ...$fileRules],
            'documents' => ['nullable', 'array'],
            'documents.*' => $fileRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeMessage = 'Each document must be '.self::MAX_FILE_SIZE_MEGABYTES.'MB or smaller.';

        return [
            'doc_type.required_with' => 'Select a document type.',
            'doc_type.in' => 'The selected document type is invalid.',
            'document.required_with' => 'Choose a file to upload.',
            'document.mimes' => 'Documents must be a PDF, JPG, or PNG file.',
            'document.max' => $maxSizeMessage,
            'documents.*.mimes' => 'Documents must be a PDF, JPG, or PNG file.',
            'documents.*.max' => $maxSizeMessage,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $uploads = $this->uploads();

            if ($uploads === []) {
                $validator->errors()->add('documents', 'Select at least one document to upload.');

                return;
            }

            foreach (array_keys($uploads) as $docType) {
                if (! in_array($docType, StudentDocument::typeKeys(), true)) {
                    $validator->errors()->add('documents.'.$docType, 'The selected document type is invalid.');
                }
            }
        });
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function uploads(): array
    {
        $uploads = [];

        foreach ($this->file('documents', []) as $docType => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $uploads[(string) $docType] = $file;
            }
        }

        $singleFile = $this->file('document');

        if ($singleFile instanceof UploadedFile && $singleFile->isValid() && is_string($this->input('doc_type'))) {
            $uploads[$this->input('doc_type')] = $singleFile;
        }

        return $uploads;
    }
}
