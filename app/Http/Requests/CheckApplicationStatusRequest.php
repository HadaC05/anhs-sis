<?php

namespace App\Http\Requests;

use App\Models\StudentApplication;
use Illuminate\Foundation\Http\FormRequest;

class CheckApplicationStatusRequest extends FormRequest
{
    protected $errorBag = 'statusCheck';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_lrn' => ['required', 'digits:12'],
            'status_birthdate' => [
                'required',
                'date',
                'after_or_equal:'.StudentApplication::EARLIEST_BIRTHDATE,
                'before_or_equal:'.StudentApplication::LATEST_BIRTHDATE,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_lrn.digits' => 'The LRN must be 12 digits.',
            'status_birthdate.after_or_equal' => 'The birthdate must be on or after January 1, 1950.',
            'status_birthdate.before_or_equal' => 'The birthdate must be on or before December 31, 2016.',
        ];
    }
}
