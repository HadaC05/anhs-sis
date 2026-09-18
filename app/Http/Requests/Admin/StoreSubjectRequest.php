<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
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
            'school_level' => ['required', Rule::in(['Junior High School', 'Senior High School'])],
            'code' => ['required', 'string', 'max:255', 'unique:subjects,code'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::exists('subject_types', 'key')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
