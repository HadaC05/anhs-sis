<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentAccountRequest extends FormRequest
{
    public const MAX_PHOTO_SIZE_KILOBYTES = 2048;

    public function authorize(): bool
    {
        return $this->user() instanceof Student;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:'.self::MAX_PHOTO_SIZE_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.mimes' => 'The profile photo must be a JPG or PNG file.',
            'photo.max' => 'The profile photo must be 2MB or smaller.',
        ];
    }
}
