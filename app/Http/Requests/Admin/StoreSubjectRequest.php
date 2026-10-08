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

    protected function prepareForValidation(): void
    {
        $schoolLevel = $this->input('school_level');
        $type = $schoolLevel === 'Junior High School' ? 'general' : $this->input('type');

        $this->merge([
            'type' => $type,
            'cluster_ID' => $schoolLevel === 'Senior High School' && $type === 'elective'
                ? $this->input('cluster_ID')
                : null,
        ]);
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
            'type' => [
                'required',
                'string',
                Rule::exists('subject_types', 'key'),
                Rule::in($this->input('school_level') === 'Junior High School'
                    ? ['general']
                    : ['core', 'elective']),
            ],
            'cluster_ID' => [
                Rule::requiredIf($this->input('school_level') === 'Senior High School' && $this->input('type') === 'elective'),
                'nullable',
                'integer',
                Rule::exists('clusters', 'cluster_ID'),
            ],
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
