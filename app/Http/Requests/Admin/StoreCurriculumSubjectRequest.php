<?php

namespace App\Http\Requests\Admin;

use App\Models\CurriculumSubject;
use App\Models\GradeLevel;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCurriculumSubjectRequest extends FormRequest
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
        return [
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')->where('status', true)],
            'subject_ID' => ['required', 'integer', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(collect(GradeLevel::options())->pluck('value')->all())],
            'semester' => ['required', Rule::in(['first', 'second'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $subjectClusterId = Subject::query()
                ->where('subject_ID', $this->integer('subject_ID'))
                ->value('cluster_ID');

            $submittedClusterId = $this->input('cluster_ID');
            $normalizedSubject = $subjectClusterId !== null ? (int) $subjectClusterId : null;
            $normalizedSubmitted = $submittedClusterId !== null ? (int) $submittedClusterId : null;

            if ($normalizedSubject !== $normalizedSubmitted) {
                $validator->errors()->add('cluster_ID', 'Selected cluster does not match the selected subject.');
            }

            $curriculumSubject = $this->route('curriculumSubject');

            $duplicate = CurriculumSubject::query()
                ->where('curriculum_ID', $this->integer('curriculum_ID'))
                ->where('subject_ID', $this->integer('subject_ID'))
                ->where('grade_level', $this->string('grade_level')->toString())
                ->where('semester', $this->string('semester')->toString())
                ->when(
                    $curriculumSubject instanceof CurriculumSubject,
                    fn ($query) => $query->where('curr_subj_ID', '!=', $curriculumSubject->curr_subj_ID),
                )
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('subject_ID', 'This subject is already assigned to the selected curriculum, grade level, and semester.');
            }
        });
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
