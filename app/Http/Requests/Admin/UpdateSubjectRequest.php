<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
        $subject = $this->route('subject');

        return [
            'school_level' => ['required', Rule::in(['Junior High School', 'Senior High School'])],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'code')->ignore($subject instanceof Subject ? $subject->subject_ID : null, 'subject_ID'),
            ],
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
