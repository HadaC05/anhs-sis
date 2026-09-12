<?php

namespace App\Http\Requests\Admin;

use App\Models\Section;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    protected function prepareForValidation(): void
    {
        $capacity = $this->input('capacity');
        if (is_string($capacity) || is_int($capacity)) {
            $digits = preg_replace('/\D+/', '', (string) $capacity) ?? '';
            $this->merge(['capacity' => $digits === '' ? null : $digits]);
        }

        $gradeLevel = (string) $this->input('grade_level');
        $isSeniorHigh = in_array($gradeLevel, ['grade_11', 'grade_12'], true);
        $clusterId = $this->input('cluster_ID');
        $staffId = $this->input('staff_ID');
        $room = $this->input('room');

        $this->merge([
            'cluster_ID' => ($isSeniorHigh && $clusterId !== '' && $clusterId !== null) ? $clusterId : null,
            'staff_ID' => ($staffId === '' || $staffId === null) ? null : $staffId,
            'room' => ($room === '' || $room === null) ? null : $room,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $syId = $this->integer('SY_ID');
        $section = $this->route('section');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')
                    ->where(fn ($query) => $query->where('SY_ID', $syId))
                    ->ignore($section instanceof Section ? $section->section_ID : null, 'section_ID'),
            ],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])],
            'staff_ID' => ['nullable', 'integer', Rule::exists('staffs', 'staff_id')],
            'SY_ID' => ['required', 'integer', Rule::exists('academic_years', 'SY_ID')],
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')],
            'room' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'digits_between:1,3', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $gradeLevel = (string) $this->input('grade_level');
            $isSeniorHigh = in_array($gradeLevel, ['grade_11', 'grade_12'], true);

            if ($isSeniorHigh && empty($this->input('cluster_ID'))) {
                $validator->errors()->add('cluster_ID', 'Cluster is required for senior high school sections.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'capacity.integer' => 'Capacity must be a number.',
            'capacity.digits_between' => 'Capacity can be at most 3 digits.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot be more than 100.',
        ];
    }
}
