<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminUserRequest extends FormRequest
{
    private Staff|Student|null $resolvedAccount = null;

    public function authorize(): bool
    {
        return $this->user() instanceof Staff;
    }

    public function account(): Staff|Student
    {
        return $this->resolvedAccount ??= $this->boolean('is_student')
            ? Student::query()->findOrFail($this->route('user'))
            : Staff::query()->findOrFail($this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $account = $this->account();
        $table = $account instanceof Student ? 'students' : 'staffs';
        $keyName = $account->getKeyName();
        $key = $account->getKey();

        return [
            'username' => [
                $account instanceof Student ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique($table, 'username')->ignore($key, $keyName),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique($table, 'email')->ignore($key, $keyName)],
            'role' => ['nullable', Rule::exists('roles', 'role_name')],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
