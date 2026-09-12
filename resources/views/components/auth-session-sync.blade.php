@php
    $authFingerprint = \App\Support\AuthSessionFingerprint::current();
@endphp

<div
    id="auth-session-sync"
    hidden
    data-test="auth-session-sync"
    data-fingerprint="{{ $authFingerprint }}"
    data-authenticated="{{ auth()->check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}"
    data-dashboard-url="{{ route('dashboard') }}"
    data-logout-url="{{ route('logout') }}"
></div>

<script>
    (function () {
        const storageKey = 'anhs-auth-fingerprint';
        const idleLogoutKey = 'anhs-idle-logged-out';
        const channelName = 'anhs-auth-session';

        const readRoot = () => document.getElementById('auth-session-sync');

        const applyFromOtherTab = (fingerprint) => {
            const root = readRoot();

            if (! root || fingerprint === null || fingerprint === undefined || fingerprint === '') {
                return;
            }

            const pageFingerprint = root.dataset.fingerprint;

            if (fingerprint === pageFingerprint) {
                return;
            }

            if (fingerprint !== 'guest') {
                if (localStorage.getItem(idleLogoutKey) === '1') {
                    return;
                }

                window.location.replace(root.dataset.dashboardUrl);

                return;
            }

            if (localStorage.getItem(idleLogoutKey) === '1') {
                return;
            }

            if (root.dataset.authenticated === '1') {
                window.location.replace(root.dataset.loginUrl);
            }
        };

        const syncAuthenticatedTab = () => {
            const root = readRoot();

            if (! root || root.dataset.authenticated !== '1') {
                return;
            }

            applyFromOtherTab(localStorage.getItem(storageKey));
        };

        const publish = () => {
            const root = readRoot();

            if (! root || root.dataset.authenticated !== '1') {
                return;
            }

            try {
                localStorage.setItem(storageKey, root.dataset.fingerprint);
            } catch (error) {
                // Private browsing can block localStorage.
            }

            window.__authSessionChannel?.postMessage(root.dataset.fingerprint);
        };

        const publishGuest = () => {
            try {
                localStorage.setItem(storageKey, 'guest');
            } catch (error) {
                // Private browsing can block localStorage.
            }

            window.__authSessionChannel?.postMessage('guest');
        };

        if (! window.__authSessionSyncInitialized) {
            window.__authSessionSyncInitialized = true;

            try {
                window.__authSessionChannel = ('BroadcastChannel' in window)
                    ? new BroadcastChannel(channelName)
                    : null;
            } catch (error) {
                window.__authSessionChannel = null;
            }

            window.__authSessionChannel?.addEventListener('message', (event) => {
                applyFromOtherTab(event.data);
            });

            window.addEventListener('storage', (event) => {
                if (event.key !== storageKey || event.newValue === null) {
                    return;
                }

                applyFromOtherTab(event.newValue);
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    syncAuthenticatedTab();
                }
            });

            window.addEventListener('pageshow', (event) => {
                if (! event.persisted) {
                    return;
                }

                syncAuthenticatedTab();
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;
                const root = readRoot();

                if (! (form instanceof HTMLFormElement) || ! root) {
                    return;
                }

                const logoutUrl = root.dataset.logoutUrl;

                if (form.action === logoutUrl || form.action.endsWith('/logout')) {
                    publishGuest();
                }
            });
        }

        const root = readRoot();

        if (root && root.dataset.authenticated === '1') {
            publish();
        } else {
            publishGuest();
        }
    })();
</script>
