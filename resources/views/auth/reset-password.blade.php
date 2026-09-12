@extends('layouts.public-auth')

@section('title', 'Reset Password')

@section('intro')
    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.35em] text-white/80">Student Portal</p>
    <h1 class="mb-6 text-4xl font-bold leading-tight sm:text-5xl">
        Choose a new password
    </h1>
    <p class="max-w-xl text-lg leading-relaxed text-white/85">
        Use at least 12 characters, with uppercase and lowercase letters, a number, and a symbol.
    </p>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm" data-test="password-reset-error">
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <h1 class="text-3xl font-bold text-blue-800 tracking-tight mb-6">NEW PASSWORD</h1>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email Address</label>
            <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                required
                autocomplete="email">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">New Password</label>
            <div class="relative">
                <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 pr-12 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password">
                <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg id="password-eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="password-eye-slash" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">Confirm Password</label>
            <div class="relative">
                <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 pr-12 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password">
                <button type="button" onclick="togglePassword('password_confirmation')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg id="password_confirmation-eye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="password_confirmation-eye-slash" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
        </div>

        <button class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300 transform active:scale-95"
            type="submit"
            data-test="reset-password-button">
            Reset password
        </button>
    </form>
@endsection

@push('scripts')
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.getElementById(inputId + '-eye');
            const eyeSlashIcon = document.getElementById(inputId + '-eye-slash');

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }
    </script>
@endpush
