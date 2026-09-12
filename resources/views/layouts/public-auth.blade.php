<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | Agusan National High School Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100">
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
                    @yield('intro')
                </div>

                <div class="w-full max-w-md justify-self-center">
                    <div class="bg-white shadow-xl rounded-lg px-8 pt-8 pb-10">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </main>

    @stack('scripts')
    <x-auth-session-sync />
</body>

</html>
