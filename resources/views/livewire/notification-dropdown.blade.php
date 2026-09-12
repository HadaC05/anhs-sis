<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="relative"
>
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <button
        type="button"
        @click="open = ! open"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"
        aria-label="Notifications"
        :aria-expanded="open.toString()"
        data-test="notification-bell"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span
                class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-bold leading-4 text-slate-900"
                data-test="notification-unread-count"
            >
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-[60] mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl sm:w-96"
        role="menu"
        aria-label="Notifications"
        data-test="notification-dropdown"
    >
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
            <div>
                <p class="text-sm font-bold text-gray-900">Notifications</p>
                <p class="text-xs text-gray-500">{{ $unreadCount }} unread</p>
            </div>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-semibold text-[#296374] hover:underline"
                    data-test="notification-mark-all-read"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $url = $data['url'] ?? '#';
                    $isUnread = $notification->read_at === null;
                    $title = $notification->typeName();
                @endphp
                <a
                    href="{{ $url }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    wire:key="notification-{{ $notification->id }}"
                    class="block border-b border-gray-100 px-4 py-3 last:border-b-0 transition hover:bg-slate-50 {{ $isUnread ? 'bg-[#296374]/5' : 'bg-white' }}"
                    data-test="notification-item"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full {{ $isUnread ? 'bg-amber-400' : 'bg-transparent' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-600">{{ $data['message'] ?? '' }}</p>
                            <p class="mt-1 text-[11px] text-gray-400">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-500" data-test="notification-empty">
                    You have no notifications yet.
                </p>
            @endforelse
        </div>
    </div>
</div>
