<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Agusan National High School Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-gray-100"
    x-data="{ showStatusModal: {{ session('application_status_message') || $errors->statusCheck->has('status_lookup') || $errors->statusCheck->has('status_lrn') || $errors->statusCheck->has('status_birthdate') ? 'true' : 'false' }} }">

    <header class="fixed top-0 left-0 right-0 w-full bg-white/95 backdrop-blur-sm shadow-sm border-b border-gray-200 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center pl-6">
                <img src="{{ asset('images/school-logo-light.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <nav class="flex items-center flex-wrap justify-center gap-8 sm:gap-12 pr-4">
                <a href="{{ route('home') }}" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Home</a>
                <a href="{{ route('home') }}#about-us" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">About</a>
                <a href="{{ route('home') }}#faq" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">FAQ</a>
                <a href="{{ route('home') }}#contact-us" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Contact Us</a>
                <a href="{{ route('login') }}" class="rounded-full bg-[#0C2C55] px-5 py-2 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-[#143d73]">Login</a>
            </nav>
        </div>
    </header>

    <main class="relative min-h-screen pt-24" style="background-image: url('{{ asset('images/background-blue.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0C2C55]/90 via-[#0C2C55]/70 to-[#0C2C55]/35"></div>

        <div class="relative container mx-auto flex min-h-[calc(100vh-6rem)] items-center justify-center px-4 py-10">
            <div class="grid w-full max-w-6xl gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                <div class="text-white">
                    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.35em] text-white/80">Student Portal</p>
                    <h1 class="mb-6 text-4xl font-bold leading-tight sm:text-5xl">
                        Sign in to access your student portal
                    </h1>
                    <p class="max-w-xl text-lg leading-relaxed text-white/85">
                        Continue to your dashboard, track your enrollment journey, and manage your school records securely.
                    </p>
                </div>

                <div class="w-full max-w-md justify-self-center">
                    <div class="bg-white shadow-xl rounded-lg px-8 pt-8 pb-10">
                        @if ($errors->has('username') || $errors->has('password') || $errors->has('captcha_answer'))
                        <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
                            {{ $errors->first('username') ?: $errors->first('password') ?: $errors->first('captcha_answer') }}
                        </div>
                        @endif

                        <form action="{{ route('login.store') }}" method="POST">
                            @csrf

                            <h1 class="text-3xl font-bold text-blue-800 tracking-tight mb-6">LOGIN</h1>

                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                                    Username
                                </label>
                                <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                    id="username"
                                    name="username"
                                    type="text"
                                    placeholder="Enter your username"
                                    value="{{ old('username') }}"
                                    required
                                    autofocus>
                            </div>

                            <div class="mb-8">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                    Password
                                </label>
                                <div class="relative">
                                    <input class="shadow-sm appearance-none border rounded w-full py-3 px-4 pr-12 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        id="password"
                                        name="password"
                                        type="password"
                                        placeholder="*******"
                                        required>
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
                                <div class="flex items-center gap-3">
                                    <input class="w-16 text-center shadow-sm appearance-none border rounded py-2 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        type="text"
                                        value="{{ $captcha_first }}"
                                        readonly>
                                    <span class="text-gray-600 text-lg font-semibold">+</span>
                                    <input class="w-16 text-center shadow-sm appearance-none border rounded py-2 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        type="text"
                                        value="{{ $captcha_second }}"
                                        readonly>
                                    <span class="text-gray-600 text-lg font-semibold">=</span>
                                    <input class="w-20 text-center shadow-sm appearance-none border rounded py-2 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        id="captcha_answer"
                                        name="captcha_answer"
                                        type="text"
                                        inputmode="numeric"
                                        pattern="\d*"
                                        required>
                                    <button type="button"
                                        onclick="window.location.reload()"
                                        class="ml-1 inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        aria-label="Refresh security check">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 10a6 6 0 10-2.05 4.47M16 10V6m0 4h-4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300 transform active:scale-95"
                                type="submit">
                                Sign In
                            </button>
                            <div class="mt-6 text-center">
                                <p class="text-xs text-gray-400">
                                    Don't have an account?
                                    <a href="{{ route('register') }}" class="text-[#76A08D] font-bold hover:underline">
                                        Submit Application
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <button type="button"
            @click="showStatusModal = true"
            class="fixed bottom-6 left-6 z-50 bg-blue-700 hover:bg-blue-800 text-white font-semibold px-5 py-3 rounded-full shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-300">
            Check Application Status
        </button>
    </main>

    <div x-show="showStatusModal"
        x-transition
        class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
        @click.self="showStatusModal = false"
        x-cloak>
        <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-[#0C2C55]">Application Status</h3>
                <button type="button" @click="showStatusModal = false" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            @if (session('application_status_message'))
            <div class="mb-4 p-3 rounded bg-blue-50 border-l-4 border-blue-500 text-blue-700 text-sm">
                {{ session('application_status_message') }}
            </div>
            @endif

            @if (session('application_status_enrollment_year'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 space-y-3">
                <div>
                    <p class="font-semibold">Login Instructions</p>
                    <p>Use these instructions to sign in, then change your password immediately after your first login.</p>
                </div>
                <div class="text-xs leading-relaxed">
                    Username: use your LRN.
                </div>
                <div class="text-xs leading-relaxed">
                    Password: use the first 2 letters of your first name + the first 2 letters of your last name + {{ session('application_status_enrollment_year') }} + anhs.
                    Example format only: `jado{{ session('application_status_enrollment_year') }}anhs`
                </div>
            </div>
            @endif

            @if ($errors->statusCheck->has('status_lookup') || $errors->statusCheck->has('status_lrn') || $errors->statusCheck->has('status_birthdate'))
            <div class="mb-4 p-3 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
                {{ $errors->statusCheck->first('status_lookup') ?: $errors->statusCheck->first('status_lrn') ?: $errors->statusCheck->first('status_birthdate') }}
            </div>
            @endif

            <form action="{{ route('applications.status') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status_lrn">LRN</label>
                    <input id="status_lrn" name="status_lrn" type="text" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" required
                        value="{{ old('status_lrn') }}"
                        class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status_birthdate">Birthdate</label>
                    <input id="status_birthdate" name="status_birthdate" type="date" required
                        value="{{ old('status_birthdate') }}"
                        class="shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                </div>
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-4 focus:ring-blue-300 transition duration-300">
                    Check Status
                </button>
            </form>
        </div>
    </div>

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
</body>

</html>
