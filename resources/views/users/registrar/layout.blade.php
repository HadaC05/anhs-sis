<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Registrar Dashboard') | Agusan National High School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('users.partials.sidebar-behavior')
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
                @include('users.partials.staff-profile-menu')
            </nav>
        </div>
    </header>

    <div class="min-h-screen flex relative pt-20" style="background-image: url('{{ asset('images/student-dash-image.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">
        <aside class="app-sidebar fixed left-0 top-20 bottom-0 bg-white/98 backdrop-blur-md shadow-2xl border-r border-gray-200/50 z-40 overflow-y-auto">
            <div class="p-6 border-b border-gray-200/50 bg-gradient-to-r from-[#296374]/5 to-transparent">
                <div class="flex items-center gap-3 mb-1">
                    <div class="sidebar-user-icon h-10 w-10 rounded-lg flex items-center justify-center shadow-md" style="background-color: #296374;">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </div>
                    <div class="sidebar-user-details min-w-0">
                        <p class="sidebar-user-label">Signed in</p>
                        <h2 class="truncate text-sm font-bold text-[#296374]">{{ trim(implode(' ', array_filter([Auth::user()?->first_name, Auth::user()?->middle_name, Auth::user()?->last_name, Auth::user()?->suffix]))) ?: (Auth::user()?->username ?? 'Registrar') }}</h2>
                        <p class="text-xs text-gray-500">Registrar</p>
                    </div>
                </div>
            </div>

            <div class="p-4 pt-6 pb-24">
                <nav class="space-y-5">
                    <a href="{{ route('registrar.dashboard') }}" class="sidebar-link {{ request()->routeIs('registrar.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-semibold">Dashboard</span>
                    </a>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Students</p>
                        <a href="{{ route('registrar.grade-approvals') }}" class="sidebar-link {{ request()->routeIs('registrar.grade-approvals*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-semibold">Grade Approvals</span>
                        </a>
                        <a href="{{ route('registrar.class-subjects.index') }}" class="sidebar-link {{ request()->routeIs('registrar.class-subjects*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="font-semibold">Class Subjects</span>
                        </a>
                        <a href="{{ route('registrar.teacher-assignments') }}" class="sidebar-link {{ request()->routeIs('registrar.teacher-assignments') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m18 0v-2a4 4 0 00-3-3.87m-2-12.13a4 4 0 010 7.75M10 11a4 4 0 100-8 4 4 0 000 8z"></path>
                            </svg>
                            <span class="font-semibold">Teacher Assignments</span>
                        </a>
                        <a href="{{ route('registrar.students') }}" class="sidebar-link {{ request()->routeIs('registrar.students') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a4 4 0 100 8 4 4 0 000-8zM6 20a6 6 0 0112 0v1H6v-1z"></path>
                            </svg>
                            <span class="font-semibold">Student Masterlist</span>
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <main class="app-main flex-1 relative z-10">
            <div class="max-w-7xl mx-auto py-12 px-4 md:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <x-idle-session-timeout />
</body>

</html>
