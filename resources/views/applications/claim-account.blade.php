<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Student Account | Agusan National High School Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-8 shadow-xl border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-900">Activate Student Account</h1>
        <p class="mt-2 text-sm text-slate-600">
            Use the details from your downloaded activation file to set your password.
        </p>

        @if ($errors->any())
            <div class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('student.claim.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-semibold text-slate-700">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="lrn" class="mb-1 block text-sm font-semibold text-slate-700">LRN</label>
                <input id="lrn" name="lrn" type="text" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" value="{{ old('lrn') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="birthdate" class="mb-1 block text-sm font-semibold text-slate-700">Birthdate</label>
                <input id="birthdate" name="birthdate" type="date" value="{{ old('birthdate') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="activation_code" class="mb-1 block text-sm font-semibold text-slate-700">Activation Code</label>
                <input id="activation_code" name="activation_code" type="text" value="{{ old('activation_code') }}" required
                    class="w-full rounded border border-slate-300 px-3 py-2 uppercase text-sm tracking-wide focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">New Password</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-slate-700">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <button type="submit"
                class="w-full rounded bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                Activate Account
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-700 hover:underline">Back to login</a>
        </div>
    </div>
</body>

</html>
