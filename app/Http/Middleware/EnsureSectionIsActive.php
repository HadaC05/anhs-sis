<?php

namespace App\Http\Middleware;

use App\Models\Section;
use App\Models\TeacherSubjectAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSectionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // The archive-management screen must retain one recovery action.
        if ($request->routeIs('admin.section-config.toggle-status', 'principal.section-config.toggle-status')) {
            return $next($request);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Section && ! $parameter->isActive()) {
                abort(404);
            }

            if ($parameter instanceof TeacherSubjectAssignment
                && ! $parameter->section()->active()->exists()) {
                abort(404);
            }
        }

        return $next($request);
    }
}
