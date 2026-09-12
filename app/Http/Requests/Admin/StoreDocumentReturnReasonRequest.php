<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReturnReasonRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:document_return_reasons,name'],
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
