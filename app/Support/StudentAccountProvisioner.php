<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentAccountProvisioner
{
    /**
     * @return array{student_name: string, username: string, password: string}|null
     */
    public static function ensure(Enrollment $enrollment, ?Authenticatable $actor = null): ?array
    {
        $enrollment->loadMissing(['student', 'academicYear']);
        $student = $enrollment->student;

        if (! $student || $student->username || $student->password) {
            return null;
        }

        $enrollmentYear = StudentCredentials::enrollmentYear($enrollment);
        $username = StudentCredentials::usernameFromLrn((string) $student->lrn);
        $defaultPassword = StudentCredentials::defaultPassword(
            (string) $student->first_name,
            (string) $student->last_name,
            $enrollmentYear,
        );

        if (Student::query()->where('username', $username)->whereKeyNot($student->id)->exists()) {
            throw ValidationException::withMessages([
                'status' => "Cannot generate account because the username {$username} is already assigned to another student.",
            ]);
        }

        $student->update([
            'username' => $username,
            'password' => Hash::make($defaultPassword),
            'change_password' => true,
            'password_changed_at' => null,
            'status' => 'approved',
            'activated_by' => $actor?->getKey(),
            'activated_at' => now(),
            'rejection_reason_id' => null,
        ]);

        return [
            'student_name' => trim($student->last_name.', '.$student->first_name) ?: 'Student',
            'username' => $username,
            'password' => $defaultPassword,
        ];
    }
}
