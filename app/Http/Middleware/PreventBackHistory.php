<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $wasAuthenticated = $request->user() !== null;

        $response = $next($request);

        if ($wasAuthenticated) {
            $this->disableBrowserCache($response);
        }

        return $response;
    }

    private function disableBrowserCache(Response $response): void
    {
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}
