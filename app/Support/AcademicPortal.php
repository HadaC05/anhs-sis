<?php

namespace App\Support;

class AcademicPortal
{
    public static function prefix(string $fallback): string
    {
        foreach (['principal', 'admin'] as $role) {
            if (request()->routeIs($role.'.*')) {
                return $role;
            }
        }

        return $fallback;
    }

    public static function routeName(string $name): string
    {
        [$role, $suffix] = explode('.', $name, 2);

        return self::prefix($role).'.'.$suffix;
    }

    public static function layout(string $fallback): string
    {
        return 'users.'.self::prefix($fallback).'.layout';
    }
}
