<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;

class AuditTrail
{
    /** Do not describe changes that were undone by a nested workflow transaction. */
    public static function transactionChanged(TransactionBeginning|TransactionCommitted|TransactionRolledBack $event): void
    {
        if (! app()->bound('request') || ! request()->attributes->get('audit_tracking')) {
            return;
        }

        $request = request();
        $snapshots = $request->attributes->get('audit_transactions', []);
        $level = $event->connection->transactionLevel();
        $connection = $event->connectionName;
        if ($event instanceof TransactionBeginning) {
            $snapshots[$connection][$level] = [
                $request->attributes->get('audit_references', []),
                $request->attributes->get('audit_changes', []),
            ];
        } else {
            if ($event instanceof TransactionRolledBack && isset($snapshots[$connection][$level + 1])) {
                [$references, $changes] = $snapshots[$connection][$level + 1];
                $request->attributes->set('audit_references', $references);
                $request->attributes->set('audit_changes', $changes);
            }
            foreach (array_keys($snapshots[$connection] ?? []) as $snapshotLevel) {
                if ($snapshotLevel > $level) {
                    unset($snapshots[$connection][$snapshotLevel]);
                }
            }
        }
        $request->attributes->set('audit_transactions', $snapshots);
    }

    public static function actor(?Authenticatable $user): array
    {
        return [
            'user_id' => $user ? (string) $user->getAuthIdentifier() : null,
            'user_name' => $user?->name ?: ($user?->username ?: 'Unauthenticated applicant'),
            'role' => $user?->roleName() ?: 'guest',
        ];
    }

    public static function record(array $actor, string $action, string $module, string $description, ?string $reference = null, string $status = 'Success'): AuditLog
    {
        return AuditLog::create([
            ...$actor,
            'timestamp' => now(),
            'action' => $action,
            'module' => $module,
            'reference' => $reference,
            'description' => $description,
            'status' => $status,
        ]);
    }

    /** Capture database-assigned references only, never model attributes or secrets. */
    public static function captureReference(string $event, array $models): void
    {
        if (! app()->bound('request')) {
            return;
        }

        $request = request();
        if (! $request->attributes->get('audit_tracking')) {
            return;
        }

        $model = $models[0] ?? null;
        if (! $model instanceof Model || $model instanceof AuditLog || ! str_starts_with($model::class, 'App\\Models\\')) {
            return;
        }

        $references = $request->attributes->get('audit_references', []);
        $reference = class_basename($model).':'.$model->getKey();
        $references[$reference] = true;
        $request->attributes->set('audit_references', $references);

        // Only operational fields are eligible for before/after descriptions.
        // Never serialize a full model, request, validation error, or upload.
        if (str_starts_with($event, 'eloquent.updated:')) {
            $changes = $request->attributes->get('audit_changes', []);
            foreach (['role_id', 'status', 'SY_ID', 'section_ID', 'enrollment_status_ID', 'grade_status_ID', 'numeric_grade', 'term_ID', 'placement_status_ID', 'promotion_status_ID'] as $field) {
                if ($model->wasChanged($field)) {
                    $old = $model->getRawOriginal($field);
                    $new = $model->getAttributes()[$field] ?? null;
                    if ((is_scalar($old) || $old === null) && (is_scalar($new) || $new === null)) {
                        $changes[] = $reference.' '.$field.': '.($old === null ? 'none' : (string) $old).' → '.($new === null ? 'none' : (string) $new).'.';
                    }
                }
            }
            $request->attributes->set('audit_changes', $changes);
        }
    }
}
