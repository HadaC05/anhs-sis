<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(41,99,116,0.14),_transparent_34%),linear-gradient(180deg,_#f8fafc_0%,_#eef4f5_100%)] px-4 py-6 text-slate-900">
    <div class="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-xl items-center justify-center">
        <section class="w-full rounded-[1.75rem] border border-white/70 bg-white/90 px-5 py-6 shadow-[0_35px_90px_-55px_rgba(15,23,42,0.45)] backdrop-blur-xl md:px-7 md:py-8">
            <div data-password-form>
                <span class="inline-flex rounded-full bg-[#296374]/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#296374]">
                    First-time Login
                </span>

                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">Change Your Password</h1>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Please review your password.</p>
                        <ul class="mt-2 space-y-1 text-rose-700/90">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('force-password.update') }}" class="mt-6 space-y-5" novalidate>
                    @csrf

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">New Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 shadow-sm outline-none transition focus:border-[#296374] focus:ring-4 focus:ring-[#296374]/10"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-2 inline-flex h-full items-center justify-center px-2 text-slate-500 transition hover:text-[#296374]"
                                data-password-toggle="password"
                                aria-label="Show password"
                            >
                                <svg class="h-5 w-5" data-eye-open fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"></path>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg class="hidden h-5 w-5" data-eye-closed fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.584 10.587A2 2 0 0013.414 13.4"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.88 5.09A10.94 10.94 0 0112 4.9c4.63 0 8.54 3.01 9.82 7.1a11.72 11.72 0 01-4.04 5.55"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.61 6.61A11.68 11.68 0 002.18 12c1.28 4.09 5.19 7.1 9.82 7.1 1.61 0 3.14-.36 4.51-1.01"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div
                        class="hidden rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-3.5"
                        data-checklist
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Password Checklist</p>
                        <div class="mt-3 space-y-2.5 text-sm">
                            <div class="flex items-center gap-3 text-slate-600" data-rule="length">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>At least 12 characters</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600" data-rule="lower">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>Contains a lowercase letter</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600" data-rule="upper">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>Contains an uppercase letter</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600" data-rule="number">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>Contains a number</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600" data-rule="symbol">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>Contains a special character</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600" data-rule="match">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold">o</span>
                                <span>Matches the confirmation password</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</label>
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 shadow-sm outline-none transition focus:border-[#296374] focus:ring-4 focus:ring-[#296374]/10"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-2 inline-flex h-full items-center justify-center px-2 text-slate-500 transition hover:text-[#296374]"
                                data-password-toggle="password_confirmation"
                                aria-label="Show confirmation password"
                            >
                                <svg class="h-5 w-5" data-eye-open fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"></path>
                                    <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                                </svg>
                                <svg class="hidden h-5 w-5" data-eye-closed fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.584 10.587A2 2 0 0013.414 13.4"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.88 5.09A10.94 10.94 0 0112 4.9c4.63 0 8.54 3.01 9.82 7.1a11.72 11.72 0 01-4.04 5.55"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.61 6.61A11.68 11.68 0 002.18 12c1.28 4.09 5.19 7.1 9.82 7.1 1.61 0 3.14-.36 4.51-1.01"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#296374] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#296374]/25 transition hover:bg-[#214e5c] disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none"
                        data-submit-button
                        disabled
                    >
                        Save Password
                    </button>
                </form>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-password-form]');

            if (!form) {
                return;
            }

            const passwordInput = form.querySelector('#password');
            const confirmationInput = form.querySelector('#password_confirmation');
            const submitButton = form.querySelector('[data-submit-button]');
            const checklist = form.querySelector('[data-checklist]');
            const toggleButtons = form.querySelectorAll('[data-password-toggle]');

            const rules = {
                length: (value) => value.length >= 12,
                lower: (value) => /[a-z]/.test(value),
                upper: (value) => /[A-Z]/.test(value),
                number: (value) => /\d/.test(value),
                symbol: (value) => /[^A-Za-z0-9]/.test(value),
                match: (value, confirmation) => value.length > 0 && value === confirmation,
            };

            const setRuleState = (name, passed) => {
                const row = form.querySelector(`[data-rule="${name}"]`);

                if (!row) {
                    return;
                }

                const icon = row.querySelector('span');

                row.className = `flex items-center gap-3 text-sm ${passed ? 'text-emerald-700' : 'text-slate-600'}`;
                icon.className = `inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${passed ? 'border border-emerald-200 bg-emerald-100 text-emerald-700' : 'border border-slate-300 text-slate-400'}`;
                icon.textContent = passed ? 'OK' : 'o';
            };

            const updateState = () => {
                const password = passwordInput.value;
                const confirmation = confirmationInput.value;
                const shouldShowChecklist = password.length > 0 || confirmation.length > 0;

                checklist.classList.toggle('hidden', !shouldShowChecklist);

                const results = {
                    length: rules.length(password),
                    lower: rules.lower(password),
                    upper: rules.upper(password),
                    number: rules.number(password),
                    symbol: rules.symbol(password),
                    match: rules.match(password, confirmation),
                };

                Object.entries(results).forEach(([name, passed]) => setRuleState(name, passed));

                const allPassed = Object.values(results).every(Boolean);
                submitButton.disabled = !allPassed;
            };

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-password-toggle');
                    const input = form.querySelector(`#${targetId}`);

                    if (!input) {
                        return;
                    }

                    const showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    button.setAttribute('aria-label', showing ? `Show ${targetId.replace('_', ' ')}` : `Hide ${targetId.replace('_', ' ')}`);

                    const openIcon = button.querySelector('[data-eye-open]');
                    const closedIcon = button.querySelector('[data-eye-closed]');

                    openIcon.classList.toggle('hidden', !showing);
                    closedIcon.classList.toggle('hidden', showing);
                });
            });

            passwordInput.addEventListener('input', updateState);
            confirmationInput.addEventListener('input', updateState);
            updateState();
        })();
    </script>
</body>

</html>
