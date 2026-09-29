@php
    $notifications = [];
    foreach (['toast_success', 'success'] as $key) {
        if (session($key)) {
            $notifications[] = ['type' => 'success', 'message' => session($key)];
        }
    }
    $errorMessages = $errors->all();
    if (session('error')) {
        $errorMessages[] = session('error');
    }
    foreach (array_unique($errorMessages) as $message) {
        $notifications[] = ['type' => 'error', 'message' => $message];
    }
@endphp

@if ($notifications)
    <div class="fixed right-5 top-24 z-[120] flex max-h-[calc(100dvh-7rem)] w-[calc(100%-2.5rem)] max-w-sm flex-col gap-3 overflow-y-auto" data-guidance-result-toasts>
        @foreach ($notifications as $notification)
            <div role="{{ $notification['type'] === 'success' ? 'status' : 'alert' }}"
                class="flex items-start gap-3 rounded-xl border bg-white p-4 text-sm shadow-xl {{ $notification['type'] === 'success' ? 'border-emerald-200 text-emerald-800' : 'border-red-200 text-red-800' }}"
                data-toast-type="{{ $notification['type'] }}">
                <div class="min-w-0 flex-1 break-words">
                    <p class="font-bold">{{ $notification['type'] === 'success' ? 'Success' : 'Action failed' }}</p>
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
                    if (toast.dataset.toastType === 'success' && !toast.matches(':hover') && !toast.contains(document.activeElement)) {
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
