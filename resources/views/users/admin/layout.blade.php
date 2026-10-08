<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | Agusan National High School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('users.partials.sidebar-behavior', ['sidebarMobileBreakpoint' => 1023])
    @include('users.partials.admin-responsive')
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

<body class="admin-shell min-h-screen flex flex-col bg-gray-100">

    <!-- Header with Logo and Navigation - Fixed at top -->
    <header class="fixed top-0 left-0 right-0 w-full backdrop-blur-sm shadow-sm border-b border-white/20 z-50" style="background-color: #296374;">
        <div class="mx-auto flex h-20 w-full items-center justify-between gap-3 px-4 sm:px-6">
            <!-- Logo on the left -->
            <div class="flex min-w-0 items-center">
                <button type="button" id="sidebar-toggle" aria-controls="admin-sidebar" class="sidebar-toggle" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <img src="{{ asset('images/school-logo-dark.png') }}" alt="School Logo" class="h-12 w-auto">
            </div>

            <!-- Navigation links on the right -->
            <nav class="flex shrink-0 items-center justify-end gap-3">
                @include('users.partials.staff-profile-menu')
            </nav>
        </div>
    </header>

    <!-- Main Container with Sidebar -->
    <div class="min-h-screen flex relative pt-20" style="background-image: url('{{ asset('images/student-dash-image.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;">

        <!-- Sidebar -->
        <button type="button" id="sidebar-backdrop" class="fixed inset-x-0 bottom-0 top-20 z-30 bg-slate-900/50" aria-label="Close navigation menu" tabindex="-1"></button>
        <aside id="admin-sidebar" class="app-sidebar fixed left-0 top-20 bottom-0 bg-white shadow-2xl border-r border-gray-200/50 z-40 overflow-y-auto">
            <!-- Sidebar Header -->
            <div class="p-6 border-b border-gray-200/50 bg-gradient-to-r from-[#296374]/5 to-transparent">
                <div class="flex items-center gap-3 mb-1">
                    <div class="sidebar-user-icon h-10 w-10 rounded-lg flex items-center justify-center shadow-md" style="background-color: #296374;">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="sidebar-user-details min-w-0">
                        <p class="sidebar-user-label">Signed in</p>
                        <h2 class="truncate text-sm font-bold text-[#296374]">{{ trim(implode(' ', array_filter([Auth::user()?->first_name, Auth::user()?->middle_name, Auth::user()?->last_name, Auth::user()?->suffix]))) ?: (Auth::user()?->username ?? 'Administrator') }}</h2>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="p-4 pt-6 pb-24">
                <nav class="space-y-5">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-semibold">Dashboard</span>
                    </a>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic Records</p>
                        <a href="{{ route('admin.enrollments.index') }}" class="sidebar-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-semibold">Enrollment Management</span>
                        </a>
                        <a href="{{ route('admin.students') }}" class="sidebar-link {{ request()->routeIs('admin.students*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a4 4 0 100 8 4 4 0 000-8zM6 20a6 6 0 0112 0v1H6v-1z"></path>
                            </svg>
                            <span class="font-semibold">Student Masterlist</span>
                        </a>
                    </div>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">User Management</p>
                        <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users') || request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="font-semibold">Users</span>
                        </a>
                    </div>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">School Management</p>
                        <a href="{{ route('admin.school-forms.edit') }}" class="sidebar-link {{ request()->routeIs('admin.sf9-configuration.*', 'admin.school-forms.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M5 3h10l4 4v14H5z" /></svg>
                            <span class="font-semibold">School Forms</span>
                        </a>
                        <a href="{{ route('admin.school-information.edit') }}" class="sidebar-link {{ request()->routeIs('admin.school-information.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h1m4 0h1M9 12h1m4 0h1"></path></svg>
                            <span class="font-semibold">School Information</span>
                        </a>
                        <a href="{{ route('admin.academic-year-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.academic-year-config.*', 'admin.grading-term-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-semibold">Academic Setup</span>
                        </a>
                        <a href="{{ route('admin.curriculum-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.curriculum-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-9 4h12m-8 4h8m-8 4h8M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                            </svg>
                            <span class="font-semibold">Curriculum</span>
                        </a>
                        <a href="{{ route('admin.subject-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.subject-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-semibold">Tracks &amp; Subjects</span>
                        </a>
                        <a href="{{ route('admin.teacher-assignments.index') }}" class="sidebar-link {{ request()->routeIs('admin.teacher-assignments.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"></path>
                            </svg>
                            <span class="font-semibold">Teacher Assignments</span>
                        </a>
                        <a href="{{ route('admin.attendance-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.attendance-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span class="font-semibold">Attendance Configuration</span>
                        </a>
                        <a href="{{ route('admin.section-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.section-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            <span class="font-semibold">Sections</span>
                        </a>
                        <a href="{{ route('admin.room-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.room-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V3h14v18M9 21v-6h6v6M9 7h1m4 0h1M9 11h1m4 0h1"></path>
                            </svg>
                            <span class="font-semibold">Rooms</span>
                        </a>
                        <a href="{{ route('admin.movement-reason-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.movement-reason-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0117 7.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-semibold">Movement Reasons</span>
                        </a>
                        <a href="{{ route('admin.document-return-reason-config.index') }}" class="sidebar-link {{ request()->routeIs('admin.document-return-reason-config.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0117 8.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-semibold">Document Return Reasons</span>
                        </a>
                    </div>

                    <div class="space-y-1">
                        <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reports</p>
                        <a href="{{ route('admin.proficiency-levels') }}" class="sidebar-link {{ request()->routeIs('admin.proficiency-levels') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15zM8 7h8M8 11h8M8 15h5"></path>
                            </svg>
                            <span class="font-semibold">Proficiency Levels</span>
                        </a>
                        <a href="{{ route('admin.reports.enrollment') }}" class="sidebar-link {{ request()->routeIs('admin.reports.enrollment') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-4m3 4V7m3 10v-7M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span class="font-semibold">Enrollment Reports</span>
                        </a>
                        <a href="{{ route('admin.reports.promotion') }}" class="sidebar-link {{ request()->routeIs('admin.reports.promotion') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M6 15V9m6 6V5m6 10v-4"></path></svg>
                            <span class="font-semibold">Promotion Reports</span>
                        </a>
                        <a href="{{ route('admin.reports.age-for-grade') }}" class="sidebar-link {{ request()->routeIs('admin.reports.age-for-grade') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-8 0H5a2 2 0 01-2-2V7a2 2 0 012-2h3m11 12h2a2 2 0 002-2V7a2 2 0 00-2-2h-3m-4 0h-4m4 0a2 2 0 11-4 0"></path>
                            </svg>
                            <span class="font-semibold">Age Alignment</span>
                        </a>
                        <a href="{{ route('admin.audit-trail.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit-trail.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6m-6 4h6"></path></svg>
                            <span class="font-semibold">Audit Trail</span>
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="app-main flex-1 relative">
            <div class="admin-content mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    @include('users.partials.archive-confirmation')
    @stack('modals')
    @stack('toasts')
    <x-idle-session-timeout />
</body>

</html>
