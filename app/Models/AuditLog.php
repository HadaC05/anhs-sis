<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    protected $primaryKey = 'audit_id';

    public $timestamps = false;

    protected $guarded = ['audit_id'];

    protected function casts(): array
    {
        return ['timestamp' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records cannot be edited.'));
        static::deleting(fn () => throw new LogicException('Audit records cannot be deleted.'));
    }
}
