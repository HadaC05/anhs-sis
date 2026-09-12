@extends('layouts.public-auth')

@section('title', 'Forgot Password')

@section('intro')
    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.35em] text-white/80">Student Portal</p>
    <h1 class="mb-6 text-4xl font-bold leading-tight sm:text-5xl">
        Forgot your password?
    </h1>
    <p class="max-w-xl text-lg leading-relaxed text-white/85">
        Enter the email address on your account and we will send a link to reset your password.
    </p>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm" data-test="password-reset-status">
            {{ session('status') }}
        </div>
    @elseif ($errors->has('email'))
        <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm" data-test="password-reset-error">
            {{ $errors->first('email') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <h1 class="text-3xl font-bold text-blue-800 tracking-tight mb-6">RESET PASSWORD</h1>

        <div class="mb-8">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email Address</label>
            <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                placeholder="email@example.com"
                required
                autofocus
                autocomplete="email">
        </div>

        <button class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300 transform active:scale-95"
            type="submit"
            data-test="email-password-reset-link-button">
            Email password reset link
        </button>

        <div class="mt-6 text-center">
            <p class="text-s text-gray-400">
                Remembered your password?
                <a href="{{ route('login') }}" class="text-[#76A08D] font-bold hover:underline">
                    Sign in
                </a>
            </p>
        </div>
    </form>
@endsection
