<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends LaravelDatabaseChannel
{
    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);
        $typeId = $payload['data']['notification_type_ID'] ?? null;

        if (is_numeric($typeId)) {
            $payload['notification_type_ID'] = (int) $typeId;
        }

        return $payload;
    }
}
