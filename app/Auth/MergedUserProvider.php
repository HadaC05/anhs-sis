<?php

namespace App\Auth;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class MergedUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        if (is_string($identifier) && str_contains($identifier, ':')) {
            [$type, $id] = explode(':', $identifier, 2);

            return match ($type) {
                'staff' => $this->activeAccount(Staff::query()->find($id)),
                'student' => $this->activeAccount(Student::query()->find($id)),
                default => null,
            };
        }

        return $this->activeAccount(Staff::query()->find($identifier));
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        //
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        $login = $credentials['username'] ?? $credentials['email'] ?? null;

        if (! is_string($login) || $login === '') {
            return null;
        }

        return Staff::query()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first()
            ?? Student::query()
                ->where('username', $login)
                ->orWhere('email', $login)
                ->first();
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || ! Hash::check($plain, $user->getAuthPassword())) {
            return false;
        }

        if (method_exists($user, 'isAccountActive') && ! $user->isAccountActive()) {
            throw ValidationException::withMessages([
                Fortify::username() => __('auth.inactive'),
            ]);
        }

        return true;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || ! Hash::needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $user->forceFill([
            'password' => Hash::make($plain),
        ])->save();
    }

    private function activeAccount(?Authenticatable $user): ?Authenticatable
    {
        if ($user && method_exists($user, 'isAccountActive') && ! $user->isAccountActive()) {
            return null;
        }

        return $user;
    }
}
