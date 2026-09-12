@extends('users.student.layout')

@section('title', 'Account Profile')

@section('content')
@php
    $fieldClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $readonlyClass = 'w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-[#296374] shadow-sm';
    $labelClass = 'mb-1.5 block text-sm text-gray-500';
    $photoUrl = $student?->photoUrl();
    $passwordModalOpen = $errors->has('current_password') || $errors->has('password');
@endphp

<div
    class="space-y-5"
    x-data="{ passwordModal: {{ $passwordModalOpen ? 'true' : 'false' }} }"
    @keydown.escape.window="passwordModal = false"
>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any() && ! $passwordModalOpen)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('student.account.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-5">
                <h1 class="text-2xl font-bold tracking-tight text-gray-800">Account Profile</h1>
                <p class="mt-1 text-sm text-gray-500">View your login details and update your profile photo.</p>
            </div>

            <div class="space-y-8 px-6 py-6">
                <div>
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">Profile Photo</h2>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-slate-100 text-slate-400">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Profile photo" class="h-full w-full object-cover" data-test="student-account-photo">
                            @else
                                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" data-test="student-account-photo-placeholder">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <label for="photo" class="{{ $labelClass }}">Upload photo</label>
                            <input
                                id="photo"
                                type="file"
                                name="photo"
                                accept=".jpg,.jpeg,.png"
                                class="{{ $fieldClass }} file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700"
                                data-test="student-account-photo-input"
                            >
                            <p class="mt-1 text-xs text-gray-500">JPG or PNG, up to 2MB. This photo appears in your profile menu.</p>
                            @error('photo')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">Account Information</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="username" class="{{ $labelClass }}">Login Username</label>
                            <input
                                id="username"
                                type="text"
                                value="{{ $student?->username }}"
                                class="{{ $readonlyClass }}"
                                readonly
                                data-test="student-account-username"
                            >
                        </div>
                        <div>
                            <label for="email" class="{{ $labelClass }}">Email</label>
                            <input
                                id="email"
                                type="text"
                                value="{{ $student?->email ?: 'Not set' }}"
                                class="{{ $readonlyClass }}"
                                readonly
                                data-test="student-account-email"
                            >
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#296374]">Change Password</h2>
                        <p class="mt-1 text-sm text-gray-500">Use the edit button to change your password with the required strong password policy.</p>
                    </div>
                    <button
                        type="button"
                        @click="passwordModal = true"
                        class="inline-flex items-center justify-center rounded-md border border-[#296374] px-4 py-2 text-sm font-semibold uppercase tracking-wide text-[#296374] transition hover:bg-[#296374]/5"
                        data-test="student-account-password-edit"
                    >
                        Edit
                    </button>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-4">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-[#296374] px-5 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-[#214e5c]"
                    data-test="student-account-save"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    <div
        x-cloak
        x-show="passwordModal"
        x-transition.opacity
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="student-account-password-title"
        data-test="student-account-password-modal"
    >
        <div class="absolute inset-0 bg-slate-900/55" @click="passwordModal = false"></div>
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl"
            @click.stop
        >
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 id="student-account-password-title" class="text-lg font-bold tracking-tight text-gray-800">Change Password</h2>
                    <p class="mt-1 text-sm text-gray-500">All fields are required.</p>
                </div>
                <button
                    type="button"
                    @click="passwordModal = false"
                    class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Close"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('student.account.password') }}" class="px-6 py-5" data-password-form novalidate>
                @csrf
                @method('PUT')

                @if ($passwordModalOpen)
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="{{ $labelClass }}">Current Password</label>
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            class="{{ $fieldClass }}"
                            required
                            autocomplete="current-password"
                            data-test="student-account-current-password"
                        >
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="{{ $labelClass }}">New Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="{{ $fieldClass }} pr-12"
                                required
                                autocomplete="new-password"
                                data-test="student-account-password"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-2 inline-flex items-center justify-center px-2 text-slate-500 transition hover:text-[#296374]"
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
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="hidden rounded-xl border border-slate-200 bg-slate-50/80 p-3.5" data-checklist>
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
                        <label for="password_confirmation" class="{{ $labelClass }}">Confirm New Password</label>
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="{{ $fieldClass }} pr-12"
                                required
                                autocomplete="new-password"
                                data-test="student-account-password-confirmation"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-2 inline-flex items-center justify-center px-2 text-slate-500 transition hover:text-[#296374]"
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
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="passwordModal = false"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-[#296374] px-5 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-[#214e5c] disabled:cursor-not-allowed disabled:bg-slate-400"
                        data-submit-button
                        data-test="student-account-password-save"
                        disabled
                    >
                        Save Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.querySelector('[data-password-form]');

        if (!form) {
            return;
        }

        const currentInput = form.querySelector('#current_password');
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

            const allPassed = Object.values(results).every(Boolean) && currentInput.value.length > 0;
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

        currentInput.addEventListener('input', updateState);
        passwordInput.addEventListener('input', updateState);
        confirmationInput.addEventListener('input', updateState);
        updateState();
    })();
</script>
@endsection
