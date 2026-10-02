@php
    $gradeReleaseError = session('error') ?: ($errors->any() ? $errors->first() : null);
@endphp

@push('toasts')
    @if (session('status') || $gradeReleaseError)
        <div class="fixed right-4 top-24 z-[120] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 sm:right-5" aria-live="polite" data-grade-release-toasts>
            @if (session('status'))
                <div id="gradeReleaseToast" role="status" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl" data-grade-release-toast="success">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="min-w-0 flex-1">{{ session('status') }}</span>
                    <button type="button" class="-mr-1 -mt-1 rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" data-dismiss-grade-release-toast aria-label="Close notification">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            @if ($gradeReleaseError)
                <div id="gradeReleaseErrorToast" role="alert" class="flex items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 text-sm font-semibold text-rose-800 shadow-xl" data-grade-release-toast="error">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-3l-7.82-13.5a2 2 0 00-3.42.36z" />
                    </svg>
                    <span class="min-w-0 flex-1">{{ $gradeReleaseError }}</span>
                    <button type="button" class="-mr-1 -mt-1 rounded p-1 text-rose-700/70 transition hover:bg-rose-50 hover:text-rose-800" data-dismiss-grade-release-toast aria-label="Close notification">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-grade-release-toast]').forEach((toast) => {
                    let timer;
                    const dismiss = () => toast.remove();
                    const resume = () => {
                        window.clearTimeout(timer);
                        timer = window.setTimeout(dismiss, toast.dataset.gradeReleaseToast === 'error' ? 6000 : 4000);
                    };

                    toast.querySelector('[data-dismiss-grade-release-toast]')?.addEventListener('click', dismiss);
                    toast.addEventListener('mouseenter', () => window.clearTimeout(timer));
                    toast.addEventListener('mouseleave', resume);
                    toast.addEventListener('focusin', () => window.clearTimeout(timer));
                    toast.addEventListener('focusout', () => window.setTimeout(resume, 0));
                    resume();
                });
            });
        </script>
    @endif
@endpush
