<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Models\StudentApplication;
use App\Support\CapitalizesFormText;
use App\Support\RejectsUnsafeFormCharacters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Student;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(CapitalizesFormText::apply($this->all()));
        $this->merge([
            'father_is_deceased' => $this->boolean('father_is_deceased'),
            'mother_is_deceased' => $this->boolean('mother_is_deceased'),
            'guardian_is_deceased' => $this->boolean('guardian_is_deceased'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        RejectsUnsafeFormCharacters::apply($validator);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->ignore($this->user()->id)],
            'contact_no' => ['required', 'regex:/^\+63\d{10}$/'],
            'gender' => ['required', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'after_or_equal:'.StudentApplication::EARLIEST_BIRTHDATE],
            'birthplace' => ['required', 'string', 'max:255'],
            'religion' => ['required', 'string', Rule::exists('religions', 'name')],
            'mother_tongue' => ['required', 'string', 'max:255'],
            'ip_community' => ['required', 'in:No,Yes'],
            'ip_details' => [
                Rule::requiredIf($this->input('ip_community') === 'Yes'),
                'nullable',
                'string',
                'max:255',
            ],
            'four_ps_beneficiary' => ['required', 'in:No,Yes'],
            'four_ps_details' => [
                Rule::requiredIf($this->input('four_ps_beneficiary') === 'Yes'),
                'nullable',
                'string',
                'min:17',
                'max:21',
            ],
            'pwd' => ['required', 'in:No,Yes'],
            'pwd_details' => [
                Rule::requiredIf($this->input('pwd') === 'Yes'),
                'nullable',
                'string',
                'max:255',
            ],
            'curr_house_no' => ['nullable', 'string', 'max:255'],
            'curr_street_name' => ['nullable', 'string', 'max:255'],
            'curr_barangay' => ['required', 'string', 'max:255'],
            'curr_municipality_city' => ['required', 'string', 'max:255'],
            'curr_province' => ['required', 'string', 'max:255'],
            'curr_country' => ['nullable', 'string', 'max:255'],
            'curr_zip_code' => ['required', 'string', 'max:255'],
            'perm_house_no' => ['nullable', 'string', 'max:255'],
            'perm_street_name' => ['nullable', 'string', 'max:255'],
            'perm_barangay' => ['required', 'string', 'max:255'],
            'perm_municipality_city' => ['required', 'string', 'max:255'],
            'perm_province' => ['required', 'string', 'max:255'],
            'perm_country' => ['nullable', 'string', 'max:255'],
            'perm_zip_code' => ['required', 'string', 'max:255'],
            'same_address' => ['nullable', 'boolean'],
            'father_lname' => ['required', 'string', 'max:255'],
            'father_fname' => ['required', 'string', 'max:255'],
            'father_mname' => ['nullable', 'string', 'max:255'],
            'father_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'father_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'mother_lname' => ['required', 'string', 'max:255'],
            'mother_fname' => ['required', 'string', 'max:255'],
            'mother_mname' => ['nullable', 'string', 'max:255'],
            'mother_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'mother_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'guardian_lname' => ['nullable', 'string', 'max:255'],
            'guardian_fname' => ['nullable', 'string', 'max:255'],
            'guardian_mname' => ['nullable', 'string', 'max:255'],
            'guardian_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'guardian_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'father_is_deceased' => ['required', 'boolean'],
            'mother_is_deceased' => ['required', 'boolean'],
            'guardian_is_deceased' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birthdate.after_or_equal' => 'The birthdate must be on or after January 1, 1950.',
            'four_ps_details.required' => 'Please provide the 4Ps Household ID Number.',
            'four_ps_details.min' => 'The 4Ps Household ID Number must be at least 17 characters.',
            'four_ps_details.max' => 'The 4Ps Household ID Number must not be greater than 21 characters.',
            'ip_details.required' => 'Please specify the IP/Community.',
            'pwd_details.required' => 'Please specify the disability.',
        ];
    }
}
