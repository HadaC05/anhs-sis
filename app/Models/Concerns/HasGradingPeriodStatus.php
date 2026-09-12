<?php

namespace App\Models\Concerns;

use App\Models\GradingPeriodStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasGradingPeriodStatus
{
    public function status(): BelongsTo
    {
        return $this->belongsTo(GradingPeriodStatus::class, 'grading_period_status_ID', 'grading_period_status_ID');
    }

    public function isActive(): bool
    {
        if ($this->relationLoaded('status') && $this->status) {
            return $this->status->slug === GradingPeriodStatus::ACTIVE;
        }

        $activeId = GradingPeriodStatus::idFor(GradingPeriodStatus::ACTIVE);

        return $activeId !== null && (int) $this->grading_period_status_ID === $activeId;
    }

    public function scopeActive(Builder $query): Builder
    {
        $activeId = GradingPeriodStatus::idFor(GradingPeriodStatus::ACTIVE);

        if ($activeId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where($this->getTable().'.grading_period_status_ID', $activeId);
    }
}
