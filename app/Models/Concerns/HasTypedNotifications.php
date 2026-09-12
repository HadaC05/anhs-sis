<?php

namespace App\Models\Concerns;

use App\Models\DatabaseNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTypedNotifications
{
    /**
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }
}
