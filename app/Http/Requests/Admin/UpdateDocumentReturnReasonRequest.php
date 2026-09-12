<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentReturnReason;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentReturnReasonRequest extends FormRequest
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
        $reason = $this->route('documentReturnReason');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('document_return_reasons', 'name')
                    ->ignore($reason instanceof DocumentReturnReason ? $reason->reason_ID : null, 'reason_ID'),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A return reason is required.',
            'name.unique' => 'This return reason already exists.',
        ];
    }
}
