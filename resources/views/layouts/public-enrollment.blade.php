<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Registration') | Agusan National High School</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">
    <header class="fixed top-0 left-0 right-0 w-full bg-white/95 backdrop-blur-sm shadow-sm border-b border-gray-200 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center pl-6">
                <img src="{{ asset('images/school-logo-light.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <nav class="flex items-center flex-wrap justify-center gap-6 sm:gap-8 pr-6">
                <a href="{{ route('home') }}" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Home</a>
                <a href="{{ route('login') }}" class="text-[#0C2C55] hover:text-[#0C2C55]/80 font-medium transition duration-200 text-sm sm:text-base uppercase tracking-wide">Login</a>
            </nav>
        </div>
    </header>

    <div class="min-h-screen flex relative pt-20" style="background-image: url('{{ asset('images/background-blue.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
        <main class="flex-1 relative z-10">
            <div class="max-w-7xl mx-auto py-12 px-4 md:px-8">
                @yield('content')
            </div>
        </main>
    </div>
    <x-auth-session-sync />
</body>

</html>
