<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemediationCase extends Model
{
    public const IN_PROGRESS = 'in_progress';

    public const AWAITING_APPROVAL = 'awaiting_approval';

    public const APPROVED_PASSED = 'approved_passed';

    public const NEEDS_INTERVENTION = 'needs_intervention';

    protected $primaryKey = 'remediation_case_ID';

    protected $fillable = [
        'enrollment_ID', 'status', 'start_date', 'end_date', 'started_by',
        'approved_by', 'approved_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'remediation_case_ID';
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(RemediationSubject::class, 'remediation_case_ID', 'remediation_case_ID');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'started_by', 'staff_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by', 'staff_id');
    }
}
