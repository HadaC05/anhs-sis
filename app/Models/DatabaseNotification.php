<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

class DatabaseNotification extends LaravelDatabaseNotification
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'notification_type_ID' => 'integer',
        ];
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_ID', 'notification_type_ID');
    }

    public function typeName(): string
    {
        return $this->notificationType?->name
            ?: (string) ($this->data['title'] ?? 'Notification');
    }
}
