<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->is('login')) {
            return $next($request);
        }

        $expected = $request->session()->get('login_captcha_answer');

        if ($expected === null) {
            throw ValidationException::withMessages([
                'captcha_answer' => 'Security check expired. Please refresh and try again.',
            ]);
        }

        $provided = is_scalar($request->input('captcha_answer'))
            ? (string) $request->input('captcha_answer')
            : '';

        if ($provided === '' || preg_match('/^\d{1,3}$/', $provided) !== 1) {
            throw ValidationException::withMessages([
                'captcha_answer' => 'Enter a number with up to 3 digits.',
            ]);
        }

        if ((int) $provided !== (int) $expected) {
            throw ValidationException::withMessages([
                'captcha_answer' => 'Incorrect answer to the security check.',
            ]);
        }

        $request->session()->forget('login_captcha_answer');

        return $next($request);
    }
}
