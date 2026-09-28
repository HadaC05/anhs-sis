<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()?->roleName(), ['admin', 'principal'], true), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'user' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:80'],
            'module' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:Success,Failed,Partial'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $query = AuditLog::query();
        foreach (['role', 'module', 'action', 'status'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, $filters[$column]);
            }
        }
        foreach (['search' => ['user_name', 'user_id', 'reference', 'description'], 'user' => ['user_name', 'user_id']] as $filter => $columns) {
            if (filled($filters[$filter] ?? null)) {
                $query->where(function ($inner) use ($columns, $filters, $filter): void {
                    foreach ($columns as $column) {
                        $inner->orWhere($column, 'like', '%'.$filters[$filter].'%');
                    }
                });
            }
        }
        if (filled($filters['from'] ?? null)) {
            $query->where('timestamp', '>=', CarbonImmutable::parse($filters['from'], config('audit.timezone'))->startOfDay()->setTimezone(config('app.timezone')));
        }
        if (filled($filters['to'] ?? null)) {
            $query->where('timestamp', '<', CarbonImmutable::parse($filters['to'], config('audit.timezone'))->addDay()->startOfDay()->setTimezone(config('app.timezone')));
        }

        return view('users.audit-trail', [
            'logs' => $query->orderByDesc('timestamp')->orderByDesc('audit_id')->paginate($filters['per_page'] ?? 25)->withQueryString(),
            'filters' => $filters,
            'options' => collect(['role', 'module', 'action'])->mapWithKeys(fn ($column) => [$column => AuditLog::query()->distinct()->orderBy($column)->pluck($column)]),
            'portal' => $request->user()->roleName(),
        ]);
    }
}
