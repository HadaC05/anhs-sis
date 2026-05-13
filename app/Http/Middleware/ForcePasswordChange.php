<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->change_password && ! $request->routeIs('force-password.*') && ! $request->routeIs('logout')) {
            return redirect()->route('force-password.edit');
        }

        return $next($request);
    }
}
