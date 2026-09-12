<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthSessionFingerprint
{
    public static function current(): string
    {
        return self::for(Auth::user());
    }

    public static function for(?Authenticatable $user, ?string $sessionId = null): string
    {
        if ($user === null) {
            return 'guest';
        }

        return $user->getAuthIdentifier().'|'.hash('sha256', (string) ($sessionId ?? Session::getId()));
    }
}
