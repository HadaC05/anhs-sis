<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    protected function prepareForValidation(): void
    {
        $startDate = $this->parsedDate('start_date');
        $endDate = $this->parsedDate('end_date');

        $this->merge([
            'school_year' => ($startDate && $endDate)
                ? AcademicYear::labelFromDates($startDate, $endDate)
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        return [
            'start_date' => [
                'required',
                'date',
                'after_or_equal:'.AcademicYear::EARLIEST_DATE,
                'before_or_equal:'.AcademicYear::LATEST_DATE,
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                'after_or_equal:'.AcademicYear::EARLIEST_DATE,
            ],
            'school_year' => [
                Rule::requiredIf(fn (): bool => $this->parsedDate('start_date') !== null
                    && $this->parsedDate('end_date') !== null),
                'nullable',
                'string',
                'max:255',
                Rule::unique('academic_years', 'school_year')->ignore(
                    $academicYear instanceof AcademicYear ? $academicYear->SY_ID : null,
                    'SY_ID',
                ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startDate = $this->parsedDate('start_date');
            $endDate = $this->parsedDate('end_date');

            if ($startDate === null || $endDate === null) {
                return;
            }

            if ($endDate->gt($startDate->copy()->addYear())) {
                $validator->errors()->add('end_date', 'The start and end dates cannot be more than one year apart.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'The start date cannot be earlier than the year 2000.',
            'start_date.before_or_equal' => 'The start date cannot be later than the year 2100.',
            'end_date.after' => 'The end date must be after the start date.',
            'end_date.after_or_equal' => 'The end date cannot be earlier than the year 2000.',
            'school_year.unique' => 'This academic year already exists.',
        ];
    }

    private function parsedDate(string $key): ?Carbon
    {
        $value = $this->input($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', substr($value, 0, 10));
        } catch (\Throwable) {
            return null;
        }

        return $date instanceof Carbon ? $date->startOfDay() : null;
    }
}
