<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Student) {
            abort(403);
        }

        // Students may reach the dashboard after login to see the prompt, but
        // every other portal area remains unavailable until their profile is
        // saved successfully. This also prevents bypassing the modal by URL.
        if (! $request->user()->hasCompleteProfile()
            && ! $request->routeIs('student.dashboard', 'student.profile', 'student.profile.update')) {
            return redirect()
                ->route('student.profile')
                ->with('profile_completion_required', 'Please complete all required profile information before using the student portal.');
        }

        return $next($request);
    }
}
