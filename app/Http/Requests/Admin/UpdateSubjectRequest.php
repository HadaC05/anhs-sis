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

    protected function prepareForValidation(): void
    {
        $clusterId = $this->input('cluster_ID');

        $this->merge([
            'cluster_ID' => ($clusterId === '' || $clusterId === null) ? null : $clusterId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'code')->ignore($subject instanceof Subject ? $subject->subject_ID : null, 'subject_ID'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['core', 'applied', 'specialized'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cluster_ID.exists' => 'Selected cluster is invalid.',
        ];
    }
}
