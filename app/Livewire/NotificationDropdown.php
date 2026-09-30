<?php

namespace App\Livewire;

use App\Models\DatabaseNotification;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public bool $autoRefresh = false;

    public function markAsSeen(): void
    {
        $this->notificationQuery()?->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    public function markAsRead(string $notificationId): void
    {
        $query = $this->notificationQuery();

        if ($query === null) {
            return;
        }

        $notification = $query->whereKey($notificationId)->first();

        if (! $notification instanceof DatabaseNotification) {
            return;
        }

        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $this->notifiable()?->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        $user = $this->notifiable();

        return view('livewire.notification-dropdown', [
            'notifications' => $user?->notifications()->with('notificationType')->latest()->limit(20)->get() ?? collect(),
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
            'newCount' => $user?->unreadNotifications()->whereNull('seen_at')->count() ?? 0,
        ]);
    }

    private function notifiable(): Student|Staff|null
    {
        $user = Auth::user();

        return $user instanceof Student || $user instanceof Staff ? $user : null;
    }

    private function notificationQuery(): ?MorphMany
    {
        return $this->notifiable()?->notifications();
    }
}
