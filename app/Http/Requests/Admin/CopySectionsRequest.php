<?php

namespace App\Http\Requests\Admin;

use App\Models\GradeLevel;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopySectionsRequest extends FormRequest
{
    protected $errorBag = 'copySections';

    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    public function rules(): array
    {
        return [
            'source_SY_ID' => ['required', 'integer', Rule::exists('academic_years', 'SY_ID')],
            'target_SY_ID' => ['required', 'integer', 'different:source_SY_ID', Rule::exists('academic_years', 'SY_ID')],
            'copy_grade_level' => ['required', Rule::in(array_merge(['all'], array_column(GradeLevel::options(), 'value')))],
            'adviser_mode' => ['required', Rule::in(['keep', 'empty'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'source_SY_ID' => 'source school year',
            'target_SY_ID' => 'destination school year',
            'copy_grade_level' => 'grade level',
            'adviser_mode' => 'adviser option',
        ];
    }
}
