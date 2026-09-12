<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Teacher Dashboard') | Agusan National High School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('users.partials.sidebar-behavior')
    @livewireStyles
    <style>
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            border-radius: 0.5rem;
            position: relative;
            width: 100%;
        }

        .sidebar-link.active {
            color: #296374;
            background-color: rgba(41, 99, 116, 0.10) !important;
            box-shadow: none;
        }

        .sidebar-link.active::before {
            display: none;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background-color: #fbbf24;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-link:not(.active) {
            color: #374151;
        }

        .sidebar-link:not(.active):hover {
            background-color: #f9fafb;
            color: #296374;
            transform: translateX(4px);
        }

        .sidebar-link svg {
            flex-shrink: 0;
            transition: transform 0.2s;
        }

        .sidebar-link:hover svg {
            transform: scale(1.1);
        }

        .sidebar-link.active svg {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .sidebar-link.active span {
            color: #296374 !important;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">
    <header class="fixed top-0 left-0 right-0 w-full backdrop-blur-sm shadow-sm border-b border-white/20 z-50" style="background-color: #296374;">
        <div class="container mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center pl-6">
                <button type="button" id="sidebar-toggle" class="sidebar-toggle" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <img src="{{ asset('images/school-logo-dark.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <nav class="flex items-center flex-wrap justify-center gap-4 sm:gap-6 pr-6">
                <livewire:notification-dropdown />
                @include('users.teacher.partials.profile-menu')
            </nav>
        </div>
    </header>

    <div class="min-h-screen flex relative pt-20" style="background-image: url('{{ asset('images/student-dash-image.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
        <aside class="app-sidebar fixed left-0 top-20 bottom-0 bg-white/98 backdrop-blur-md shadow-2xl border-r border-gray-200/50 z-40 overflow-y-auto">
            <div class="p-6 border-b border-gray-200/50 bg-gradient-to-r from-[#296374]/5 to-transparent">
                <div class="flex items-center gap-3 mb-1">
                    <div class="sidebar-user-icon h-10 w-10 rounded-lg flex items-center justify-center shadow-md" style="background-color: #296374;">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                    <div class="sidebar-user-details min-w-0">
                        <p class="sidebar-user-label">Signed in</p>
                        <h2 class="truncate text-sm font-bold text-[#296374]">{{ trim(implode(' ', array_filter([Auth::user()?->first_name, Auth::user()?->middle_name, Auth::user()?->last_name, Auth::user()?->suffix]))) ?: (Auth::user()?->username ?? 'Teacher') }}</h2>
                        <p class="text-xs text-gray-500">Teacher</p>
                    </div>
                </div>
            </div>

            <div class="p-4 pt-6 pb-24">
                <nav class="space-y-5">
                    <a href="{{ route('teacher.dashboard') }}" class="sidebar-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-semibold">Dashboard</span>
                    </a>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academics</p>
                        <a href="{{ route('teacher.advisory.index') }}" class="sidebar-link {{ request()->routeIs('teacher.advisory.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 100-8 4 4 0 000 8zm8 0a4 4 0 100-8 4 4 0 000 8z"></path>
                            </svg>
                            <span class="font-semibold">Advisory</span>
                        </a>
                        <a href="{{ route('teacher.sections.index') }}" class="sidebar-link {{ request()->routeIs('teacher.sections.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <span class="font-semibold">Subjects</span>
                        </a>
                    </div>
                </nav>
            </div>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200/50 bg-gray-50/50">
                <div class="text-center">
                    <p class="text-xs text-gray-500 font-medium">Agusan National High School</p>
                </div>
            </div>
        </aside>

        <main class="app-main flex-1 relative z-10">
            <div class="max-w-7xl mx-auto py-12 px-4 md:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    <x-idle-session-timeout />
    @livewireScripts
</body>

</html>
