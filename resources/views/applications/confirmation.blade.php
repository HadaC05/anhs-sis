@extends('layouts.public-enrollment')

@section('title', 'Registration Submitted')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-3 tracking-tight">Enrollment Application Submitted</h1>
        <p class="text-gray-600 text-sm md:text-base leading-6">
            {{ session('status', 'You are temporarily enrolled. Check your email for login instructions, then sign in to upload your required documents.') }}
        </p>
        <p class="mt-4 text-sm text-gray-500 leading-6">
            Username is your LRN. Your default password uses the first 2 letters of your first name, the first 2 letters of your last name, the school year, and anhs.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 transition">
                Back to Home
            </a>
            <a href="{{ route('login') }}" class="inline-flex justify-center rounded-lg px-6 py-3 text-sm font-bold text-white shadow-md hover:opacity-90 transition" style="background-color: #296374;">
                Go to Login
            </a>
        </div>
    </div>
</div>
@endsection
