@props(['includeErrors' => true, 'testPrefix' => 'password-reset', 'positionClass' => 'top-4'])

@php
    $notifications = [];
    foreach (['status' => 'success', 'success' => 'success', 'warning' => 'warning', 'error' => 'error'] as $key => $type) {
        if (session($key)) {
            $notifications[] = ['type' => $type, 'message' => session($key)];
        }
    }
    if ($includeErrors) {
        foreach (array_unique($errors->all()) as $message) {
            $notifications[] = ['type' => session('password_reset_notice_type', 'error'), 'message' => $message];
        }
    }
    $colors = [
        'success' => 'border-emerald-200 text-emerald-800',
        'warning' => 'border-amber-200 text-amber-800',
        'error' => 'border-red-200 text-red-800',
    ];
@endphp

@if ($notifications)
    <div class="fixed right-4 {{ $positionClass }} z-[120] flex max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-sm flex-col gap-3 overflow-y-auto" data-password-reset-toasts>
        @foreach ($notifications as $notification)
            <div class="flex items-start gap-3 rounded-xl border bg-white p-4 text-sm shadow-xl {{ $colors[$notification['type']] ?? $colors['error'] }}"
                role="{{ $notification['type'] === 'success' ? 'status' : 'alert' }}"
                data-test="{{ $testPrefix }}-{{ $notification['type'] === 'success' ? 'status' : 'error' }}"
                data-toast-type="{{ $notification['type'] }}">
                <div class="min-w-0 flex-1 break-words">
                    <p class="font-bold">{{ ucfirst($notification['type']) }}</p>
                    <p class="mt-1">{{ $notification['message'] }}</p>
                </div>
                <button type="button" class="shrink-0 rounded px-1 text-xl leading-none hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-current" aria-label="Close notification">&times;</button>
            </div>
        @endforeach
    </div>
    <script>
        (() => {
            const container = document.currentScript.previousElementSibling;
            container.querySelectorAll('[data-toast-type]').forEach((toast) => {
                let timer;
                const dismiss = () => {
                    clearTimeout(timer);
                    toast.remove();
                    if (!container.children.length) container.remove();
                };
                const pause = () => clearTimeout(timer);
                const resume = () => {
                    pause();
                    if (!toast.matches(':hover') && !toast.contains(document.activeElement)) {
                        timer = setTimeout(dismiss, 8000);
                    }
                };
                toast.querySelector('button').addEventListener('click', dismiss);
                toast.addEventListener('mouseenter', pause);
                toast.addEventListener('mouseleave', resume);
                toast.addEventListener('focusin', pause);
                toast.addEventListener('focusout', () => setTimeout(resume, 0));
                resume();
            });
        })();
    </script>
@endif
