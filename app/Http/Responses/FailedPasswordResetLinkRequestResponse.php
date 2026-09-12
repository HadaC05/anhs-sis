<?php

namespace App\Http\Responses;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

class FailedPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    public function __construct(protected string $status) {}

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($this->status)],
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($this->status)]);
    }
}
