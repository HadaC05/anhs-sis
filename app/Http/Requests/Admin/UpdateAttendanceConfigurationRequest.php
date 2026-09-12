<?php

namespace App\Http\Requests\Admin;

use App\Models\Month;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    protected function prepareForValidation(): void
    {
        $schoolDays = $this->input('school_days');

        if (! is_array($schoolDays)) {
            return;
        }

        $normalized = [];

        foreach (Month::ids() as $month) {
            $value = $schoolDays[$month] ?? $schoolDays[(string) $month] ?? null;
            $normalized[$month] = ($value === '' || $value === null) ? '0' : (string) $value;
        }

        $this->merge([
            'school_days' => $normalized,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'SY_ID' => ['required', 'integer', 'exists:academic_years,SY_ID'],
            'school_days' => ['required', 'array'],
            'school_days.*' => ['required', 'regex:/^\d{1,2}$/', 'numeric', 'min:0', 'max:31'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'SY_ID.required' => 'Please select an academic year.',
            'SY_ID.exists' => 'The selected academic year is invalid.',
            'school_days.required' => 'School days are required for each month.',
            'school_days.*.required' => 'Enter the number of school days for each month.',
            'school_days.*.regex' => 'School days must be a 1 or 2 digit number.',
            'school_days.*.numeric' => 'School days must be a whole number.',
            'school_days.*.min' => 'School days cannot be less than 0.',
            'school_days.*.max' => 'School days cannot be more than 31.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (Month::labels() as $month => $label) {
            $attributes['school_days.'.$month] = $label.' school days';
        }

        return $attributes;
    }
}
