@auth
    @php
        $idleTimeoutMinutes = max(1, (int) config('session.idle_timeout', 5));
        $idleTimeoutMs = $idleTimeoutMinutes * 60 * 1000;
    @endphp

    <div
        id="idle-session-timeout"
        data-timeout-ms="{{ $idleTimeoutMs }}"
        data-logout-url="{{ route('logout') }}"
        data-login-url="{{ route('login') }}"
        data-csrf="{{ csrf_token() }}"
    >
        <style>
            @media print {
                #idle-session-timeout { display: none !important; }
            }
        </style>
        <div
            id="idle-session-modal"
            class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-900/70 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="idle-session-title"
            data-test="idle-session-modal"
        >
            <div class="mx-auto w-full max-w-sm rounded-lg border border-gray-300 bg-white px-6 py-8 text-center shadow-2xl">
                <h3 id="idle-session-title" class="text-lg font-bold text-gray-900">Session expired</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-500">
                    You were idle for {{ $idleTimeoutMinutes }} {{ $idleTimeoutMinutes === 1 ? 'minute' : 'minutes' }}, so you have been logged out. Sign in again to continue.
                </p>
                <button
                    type="button"
                    id="idle-session-ok"
                    class="mt-6 w-full rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #296374;"
                >
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            if (window.__idleSessionTimeoutInitialized) {
                return;
            }

            window.__idleSessionTimeoutInitialized = true;

            const root = document.getElementById('idle-session-timeout');

            if (! root) {
                return;
            }

            const timeoutMs = Number(root.dataset.timeoutMs);
            const logoutUrl = root.dataset.logoutUrl;
            const loginUrl = root.dataset.loginUrl;
            const csrf = root.dataset.csrf;
            const activityKey = 'anhs-idle-last-activity';
            const logoutKey = 'anhs-idle-logged-out';
            let timedOut = false;
            let signingOut = false;
            let lastWrite = 0;

            const now = () => Date.now();

            const showModal = () => {
                const dialog = document.getElementById('idle-session-modal');

                if (! dialog) {
                    return;
                }

                dialog.classList.remove('hidden');
                dialog.classList.add('flex');
                document.body.style.overflow = 'hidden';
                document.getElementById('idle-session-ok')?.focus();
            };

            const logout = () => {
                if (signingOut) {
                    return;
                }

                if (timedOut) {
                    showModal();

                    return;
                }

                timedOut = true;
                localStorage.setItem(logoutKey, '1');
                localStorage.setItem('anhs-auth-fingerprint', 'guest');
                showModal();

                fetch(logoutUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin',
                    body: new URLSearchParams({ _token: csrf }),
                }).catch(() => {});
            };

            const markActivity = () => {
                if (timedOut || localStorage.getItem(logoutKey) === '1') {
                    return;
                }

                const timestamp = now();

                if (timestamp - lastWrite < 1000) {
                    return;
                }

                lastWrite = timestamp;
                localStorage.setItem(activityKey, String(timestamp));
            };

            const checkIdle = () => {
                if (timedOut || signingOut) {
                    return;
                }

                if (localStorage.getItem(logoutKey) === '1') {
                    logout();

                    return;
                }

                const lastActivity = Number(localStorage.getItem(activityKey) || now());

                if (now() - lastActivity >= timeoutMs) {
                    logout();
                }
            };

            localStorage.removeItem(logoutKey);
            markActivity();
            const idleTimer = setInterval(checkIdle, 1000);

            ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click', 'wheel'].forEach((eventName) => {
                document.addEventListener(eventName, markActivity, { passive: true });
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    checkIdle();
                }
            });

            window.addEventListener('storage', (event) => {
                if (event.key === logoutKey && event.newValue === '1') {
                    logout();
                }
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (form instanceof HTMLFormElement && (form.action === logoutUrl || form.action.endsWith('/logout'))) {
                    signingOut = true;
                    clearInterval(idleTimer);
                }
            });

            window.addEventListener('pageshow', (event) => {
                if (! event.persisted) {
                    return;
                }

                if (signingOut || localStorage.getItem(logoutKey) === '1') {
                    window.location.replace(loginUrl);

                    return;
                }

                window.location.reload();
            });

            document.addEventListener('click', (event) => {
                if (event.target.closest('#idle-session-ok')) {
                    window.location.replace(loginUrl);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && timedOut) {
                    event.preventDefault();
                }
            });
        })();
    </script>
@endauth

<x-auth-session-sync />
