<?php

namespace App\Http\Requests\Admin;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
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
        $subjectIds = array_values(array_filter(
            Arr::wrap($this->input('subject_ID')),
            fn ($subjectId): bool => $subjectId !== null && $subjectId !== '',
        ));
        // A curriculum grade-level offering already defines the grade, semester,
        // and cluster.  Keep those details server-derived rather than asking the
        // administrator to enter the same information again.
        $offering = Curriculum::query()->find($this->input('curriculum_ID'));
        $gradeId = $offering?->grade_ID;
        $semesterId = $offering?->semester_ID;
        $clusterId = $offering?->cluster_ID;

        $this->merge([
            'subject_ID' => $subjectIds,
            'cluster_ID' => ($clusterId === '' || $clusterId === null) ? null : $clusterId,
            'grade_ID' => $gradeId,
            'semester_ID' => $semesterId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum_grade_levels', 'curriculum_ID')],
            'subject_ID' => ['required', 'array', 'min:1'],
            'subject_ID.*' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_ID' => ['required', 'integer', Rule::exists('grade_level', 'grade_ID')],
            'semester_ID' => ['required', 'integer', Rule::exists('grading_semesters', 'semester_ID')],
            'edit_mode' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $submittedClusterId = $this->input('cluster_ID');
            $normalizedSubmitted = $submittedClusterId !== null ? (int) $submittedClusterId : null;
            $offering = Curriculum::query()->with('dataStatus')->find($this->integer('curriculum_ID'));
            $grade = GradeLevel::query()->find($this->integer('grade_ID'));
            $semester = GradingSemester::query()->find($this->integer('semester_ID'));
            $isSeniorHigh = $grade?->category === 'Senior High School';

            if (! $offering || $offering->dataStatus?->key !== 'active') {
                $validator->errors()->add('curriculum_ID', 'Select an active curriculum grade level.');

                return;
            }

            if ((int) $offering->grade_ID !== $this->integer('grade_ID') ||
                (int) $offering->semester_ID !== $this->integer('semester_ID') ||
                (($offering->cluster_ID ?: null) !== $normalizedSubmitted)) {
                $validator->errors()->add('curriculum_ID', 'The selected curriculum grade level does not match the chosen grade, semester, and cluster.');

                return;
            }

            if ($isSeniorHigh && $normalizedSubmitted === null) {
                $validator->errors()->add('cluster_ID', 'Select a cluster for Grades 11 and 12.');
            }

            if (! $isSeniorHigh && $normalizedSubmitted !== null) {
                $validator->errors()->add('cluster_ID', 'Cluster is only applicable to Grades 11 and 12.');
            }

            if ($isSeniorHigh && ! in_array($semester?->key, [GradingSemester::FIRST, GradingSemester::SECOND], true)) {
                $validator->errors()->add('semester_ID', 'Select First or Second Semester for Grades 11 and 12.');
            }

            $curriculumSubject = $this->route('curriculumSubject');

            $subjects = Subject::query()
                ->whereIn('subject_ID', $this->input('subject_ID', []))
                ->pluck('school_level', 'subject_ID');

            $requiredSchoolLevel = $isSeniorHigh ? 'Senior High School' : 'Junior High School';

            foreach ($this->input('subject_ID', []) as $subjectId) {
                $subjectSchoolLevel = $subjects[$subjectId] ?? null;

                if ($subjectSchoolLevel !== $requiredSchoolLevel) {
                    $validator->errors()->add('subject_ID', 'Every selected subject must match the selected school level.');
                    break;
                }

                $duplicate = CurriculumSubject::query()
                    ->where('curriculum_grade_level_ID', $this->integer('curriculum_ID'))
                    ->where('subject_ID', $subjectId)
                    ->when(
                        $curriculumSubject instanceof CurriculumSubject,
                        fn ($query) => $query->where('curr_subj_ID', '!=', $curriculumSubject->curr_subj_ID),
                    )
                    ->exists();

                if ($duplicate && ! $this->boolean('edit_mode')) {
                    $validator->errors()->add('subject_ID', 'One or more selected subjects are already assigned to this curriculum, grade level, and semester.');
                    break;
                }
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
