<?php

namespace App\Http\Middleware;

use App\Support\AuditTrail;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordAuditTrail
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();
        $definitions = config('audit.actions', []);
        $definition = $definitions[$name] ?? null;
        if (! $definition || $request->isMethod('HEAD')) {
            return $next($request);
        }

        [$module, $action, $description] = $definition;
        if ($module === 'School Forms' && $request->isMethod('GET')) {
            $action = 'Accessed';
            $description = str_replace('Generated ', 'Accessed ', $description);
        }
        $actor = AuditTrail::actor($request->user());
        $request->attributes->set('audit_tracking', true);
        $request->attributes->set('audit_references', []);
        $request->attributes->set('audit_changes', []);
        $request->attributes->set('audit_transactions', []);
        $beforeFlash = $request->session()->get('_flash.new', []);
        $references = [];
        foreach ($request->route()->parameters() as $key => $value) {
            $value = $value instanceof Model ? $value->getKey() : $value;
            if (is_scalar($value)) {
                $references[] = $key.':'.$value;
            }
        }

        // Bulk requests may update records through query builders (no model events).
        foreach (['enrollment_ids', 'document_ids', 'assignment_ids', 'student_ids'] as $key) {
            $ids = $request->input($key, []);
            if (is_array($ids)) {
                $ids = array_filter($ids, fn ($id) => is_scalar($id) && ctype_digit((string) $id));
                if ($ids !== []) {
                    $references[] = $key.':'.implode(',', $ids);
                }
            }
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $request->attributes->set('audit_tracking', false);
            AuditTrail::record($actor, $action, $module, 'Failed: '.$description, implode('; ', $references) ?: null, 'Failed');
            throw $exception;
        }

        $request->attributes->set('audit_tracking', false);
        $newFlash = array_diff($request->session()->get('_flash.new', []), $beforeFlash);
        $failed = $response->getStatusCode() >= 400 || count(array_intersect(['errors', 'error'], $newFlash)) > 0;
        // An unauthenticated redirect is not a completed action.
        $failed = $failed || ($actor['user_id'] === null && ! in_array($name, ['register.store', 'applications.store'], true));
        $status = $failed ? 'Failed' : 'Success';

        if ($response->isRedirect(route('force-password.edit')) || $response->isRedirect(route('login'))) {
            $failed = true;
            $status = 'Failed';
        }
        if ($name === 'teacher.sections.grades.store' && $request->boolean('submit')) {
            $action = 'Submitted';
            $description = 'Saved and submitted grades for approval.';
        }

        if (in_array('enrollment_result', $newFlash, true)) {
            $result = $request->session()->get('enrollment_result', []);
            if (($result['failed'] ?? 0) > 0) {
                $status = ($result['updated'] ?? 0) > 0 ? 'Partial' : 'Failed';
            }
            foreach (['updated', 'skipped', 'failed'] as $key) {
                if (isset($result[$key]) && is_numeric($result[$key])) {
                    $description .= ' '.ucfirst($key).': '.(int) $result[$key].'.';
                }
            }
        }

        if (! $failed) {
            $description = $request->attributes->get('audit_description', $description);
            $references = array_merge($references, array_keys($request->attributes->get('audit_references', [])));
            $changes = $request->attributes->get('audit_changes', []);
            if ($changes !== []) {
                $description .= ' '.implode(' ', $changes);
            }
        }
        AuditTrail::record($actor, $action, $module, ($failed ? 'Failed: ' : '').$description, implode('; ', array_unique($references)) ?: null, $status);

        return $response;
    }
}
