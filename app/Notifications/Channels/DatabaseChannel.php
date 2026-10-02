<?php

namespace App\Notifications\Channels;

use App\Notifications\GradesApproved;
use App\Notifications\GradesReleased;
use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class DatabaseChannel extends LaravelDatabaseChannel
{
    /**
     * Keep unseen teacher grade updates as one notification per action.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $notification instanceof GradesApproved && ! $notification instanceof GradesReleased) {
            return parent::send($notifiable, $notification);
        }

        return DB::transaction(function () use ($notifiable, $notification) {
            $notifications = $notifiable->routeNotificationFor('database', $notification);
            $existing = (clone $notifications)
                ->where('type', $notification::class)
                ->whereNull('read_at')
                ->whereNull('seen_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                return $notifications->create($this->buildPayload($notifiable, $notification));
            }

            $notification->gradeCount += (int) ($existing->data['grade_count'] ?? 1);
            $payload = $this->buildPayload($notifiable, $notification);
            $timestamp = now();

            $existing->forceFill([
                'data' => $payload['data'],
                'notification_type_ID' => $payload['notification_type_ID'] ?? null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->save();

            return $existing;
        });
    }

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
