<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Dashboard') | Agusan National High School</title>
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
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">
    <header class="fixed top-0 left-0 right-0 w-full backdrop-blur-sm shadow-sm border-b border-white/20 z-50" style="background-color: #296374;">
        <div class="container mx-auto flex min-h-20 items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center">
                <button type="button" id="sidebar-toggle" class="sidebar-toggle" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <img src="{{ asset('images/school-logo-dark.png') }}" alt="Agusan National High School" class="h-10 w-auto sm:h-12">
            </div>

            <nav class="flex shrink-0 items-center gap-2 sm:gap-4" aria-label="Account controls">
                <livewire:notification-dropdown />
                @include('users.student.partials.profile-menu')
            </nav>
        </div>
    </header>

    <div class="relative flex min-h-screen pt-20" style="background-image: url('{{ asset('images/student-dash-image.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-950/45" aria-hidden="true"></div>
        <aside class="app-sidebar fixed left-0 top-20 bottom-0 bg-white shadow-2xl border-r border-gray-200/50 z-40 overflow-y-auto">
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
                        <h2 class="truncate text-sm font-bold text-[#296374]">{{ trim(implode(' ', array_filter([Auth::user()?->first_name, Auth::user()?->middle_name, Auth::user()?->last_name, Auth::user()?->suffix]))) ?: (Auth::user()?->name ?? 'Student') }}</h2>
                        <p class="text-xs text-gray-500">Student</p>
                    </div>
                </div>
            </div>

            <div class="p-4 pt-6 pb-24">
                <nav class="space-y-1">
                    <a href="{{ route('student.dashboard') }}" class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-semibold">Dashboard</span>
                    </a>
                    <a href="{{ route('student.profile') }}" class="sidebar-link {{ request()->routeIs('student.profile') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A12.07 12.07 0 0112 15.75c2.54 0 4.897.786 6.879 2.054M15 11a3 3 0 11-6 0 3 3 0 016 0zm6 1a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold">Student Profile</span>
                    </a>
                    <a href="{{ route('student.subjects') }}" class="sidebar-link {{ request()->routeIs('student.subjects') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span class="font-semibold">Subjects</span>
                    </a>
                    <a href="{{ route('student.grades') }}" class="sidebar-link {{ request()->routeIs('student.grades') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="font-semibold">Grades</span>
                    </a>
                    <a href="{{ route('student.documents') }}" class="sidebar-link {{ request()->routeIs('student.documents') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-semibold">Documents</span>
                    </a>
                </nav>
            </div>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200/50 bg-gray-50/50">
                <div class="text-center">
                    <p class="text-xs text-gray-500 font-medium">Agusan National High School</p>
                </div>
            </div>
        </aside>

        <main class="app-main flex-1 relative z-10">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 md:px-8 md:py-12">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    <x-idle-session-timeout />
    @livewireScripts
</body>

</html>
