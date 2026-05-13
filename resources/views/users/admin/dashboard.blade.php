@extends('users.admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Page Title -->
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">System Overview</h1>
        <p class="text-gray-600 text-sm md:text-base">Manage and monitor the student information system</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="shadow-xl rounded-2xl p-6 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-full p-4 inline-flex shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Total Students</p>
            <p class="text-3xl font-bold text-white">{{ $studentCount }}</p>
        </div>

        <div class="shadow-xl rounded-2xl p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #10b981;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-full p-4 inline-flex shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Staff Members</p>
            <p class="text-3xl font-bold text-white">{{ $staffCount }}</p>
        </div>

        <div class="shadow-xl rounded-2xl p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #f59e0b;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-full p-4 inline-flex shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Total Users</p>
            <p class="text-3xl font-bold text-white">{{ $userCount }}</p>
        </div>

        <div class="shadow-xl rounded-2xl p-6 border-l-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl" style="background-color: #296374; border-left-color: #8b5cf6;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-full p-4 inline-flex shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">System Status</p>
            <p class="text-lg font-bold text-white italic uppercase">Online</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl p-8 border border-gray-100">
        <h3 class="text-base font-bold text-[#296374] mb-6 uppercase tracking-wider">Quick Actions</h3>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('admin.users') }}" class="text-white font-bold text-sm uppercase tracking-wide px-8 py-3 rounded-lg shadow-lg transition-all transform hover:-translate-y-1 hover:shadow-xl active:scale-95 inline-flex items-center gap-2" style="background-color: #296374;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Manage Users
            </a>
            <a href="{{ route('admin.applications.index') }}" class="text-white font-bold text-sm uppercase tracking-wide px-8 py-3 rounded-lg shadow-lg transition-all transform hover:-translate-y-1 hover:shadow-xl active:scale-95 inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Review Applications
            </a>
            <button class="bg-white/80 border-2 border-gray-300 text-gray-700 hover:bg-gray-50 font-bold text-sm uppercase tracking-wide px-8 py-3 rounded-lg transition-all inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Generate Reports
            </button>
        </div>
    </div>

    <!-- Student Application Summary -->
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-emerald-500/10 to-transparent">
            <h3 class="text-base font-bold text-emerald-700 uppercase tracking-wider">Student Application Summary</h3>
        </div>
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Applications</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ $applicationCounts['total'] }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 p-4 bg-amber-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ $applicationCounts['pending'] }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 p-4 bg-emerald-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Approved</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $applicationCounts['approved'] }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 p-4 bg-rose-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Rejected</p>
                <p class="mt-1 text-2xl font-bold text-rose-700">{{ $applicationCounts['rejected'] }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Student Applications -->
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-emerald-500/10 to-transparent flex items-center justify-between gap-3">
            <h3 class="text-base font-bold text-emerald-700 uppercase tracking-wider">Recent Student Applications</h3>
            <a href="{{ route('admin.applications.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">View all</a>
        </div>
        <div class="px-6 pt-4">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="recent_users_search" value="{{ request('recent_users_search') }}">
                <input type="hidden" name="recent_users_status" value="{{ request('recent_users_status') }}">
                <input type="hidden" name="recent_users_role" value="{{ request('recent_users_role') }}">
                <input type="hidden" name="recent_users_per_page" value="{{ request('recent_users_per_page', $usersPerPage ?? 10) }}">
                <input type="hidden" name="recent_users_page" value="{{ request('recent_users_page') }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="recent_applications_search" value="{{ request('recent_applications_search') }}" class="px-3 py-2 border rounded text-sm" placeholder="LRN or name">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="recent_applications_status" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        <option value="pending" {{ request('recent_applications_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('recent_applications_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('recent_applications_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Per Page</label>
                    <select name="recent_applications_per_page" class="px-3 py-2 border rounded text-sm">
                        @foreach([5, 10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" {{ (int) ($appsPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-3 py-2 rounded text-white text-sm bg-emerald-700 hover:bg-emerald-800">Apply</button>
                @if(request()->hasAny(['recent_applications_search', 'recent_applications_status', 'recent_applications_per_page']))
                    <a href="{{ route('admin.dashboard', request()->except(['recent_applications_search', 'recent_applications_status', 'recent_applications_per_page', 'recent_applications_page'])) }}" class="px-3 py-2 rounded text-sm bg-gray-200 text-gray-700">Clear</a>
                @endif
            </form>
        </div>
        <div class="p-6">
            @if($recentApplications->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Submitted</th>
                                <th class="pb-3">LRN</th>
                                <th class="pb-3">Applicant</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Reviewed By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recentApplications as $application)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 text-gray-500 text-sm">{{ optional($application->submitted_at)->format('M d, Y h:i A') }}</td>
                                    <td class="py-3 font-medium text-gray-800">{{ $application->lrn }}</td>
                                    <td class="py-3 text-gray-700">{{ $application->last_name }}, {{ $application->first_name }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $application->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                            {{ $application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : '' }}">
                                            {{ ucfirst($application->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-gray-600">{{ optional($application->reviewer)->username ?? 'Not yet reviewed' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($recentApplications->hasPages())
                    <div class="mt-4">
                        {{ $recentApplications->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <p class="text-gray-500 text-center py-4">No student applications yet.</p>
            @endif
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 class="text-base font-bold text-[#296374] uppercase tracking-wider">Recent Users</h3>
        </div>
        <div class="px-6 pt-4">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="recent_applications_search" value="{{ request('recent_applications_search') }}">
                <input type="hidden" name="recent_applications_status" value="{{ request('recent_applications_status') }}">
                <input type="hidden" name="recent_applications_per_page" value="{{ request('recent_applications_per_page', $appsPerPage ?? 10) }}">
                <input type="hidden" name="recent_applications_page" value="{{ request('recent_applications_page') }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="recent_users_search" value="{{ request('recent_users_search') }}" class="px-3 py-2 border rounded text-sm" placeholder="Username or email">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Role</label>
                    <select name="recent_users_role" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        @foreach($roles as $roleName)
                            <option value="{{ $roleName }}" {{ request('recent_users_role') === $roleName ? 'selected' : '' }}>{{ ucfirst($roleName) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="recent_users_status" class="px-3 py-2 border rounded text-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('recent_users_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('recent_users_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Per Page</label>
                    <select name="recent_users_per_page" class="px-3 py-2 border rounded text-sm">
                        @foreach([5, 10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" {{ (int) ($usersPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-3 py-2 rounded text-white text-sm" style="background-color: #296374;">Apply</button>
                @if(request()->hasAny(['recent_users_search', 'recent_users_role', 'recent_users_status', 'recent_users_per_page']))
                    <a href="{{ route('admin.dashboard', request()->except(['recent_users_search', 'recent_users_role', 'recent_users_status', 'recent_users_per_page', 'recent_users_page'])) }}" class="px-3 py-2 rounded text-sm bg-gray-200 text-gray-700">Clear</a>
                @endif
            </form>
        </div>
        <div class="p-6">
            @if($recentUsers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Username</th>
                                <th class="pb-3">Email</th>
                                <th class="pb-3">Role</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recentUsers as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 font-medium text-gray-800">{{ $user->username }}</td>
                                    <td class="py-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($user->role->role_name === 'admin') bg-purple-100 text-purple-700
                                            @elseif($user->role->role_name === 'teacher') bg-blue-100 text-blue-700
                                            @elseif($user->role->role_name === 'guidance counselor') bg-green-100 text-green-700
                                            @elseif($user->role->role_name === 'registrar') bg-yellow-100 text-yellow-700
                                            @elseif($user->role->role_name === 'principal') bg-red-100 text-red-700
                                            @else bg-gray-100 text-gray-700
                                            @endif">
                                            {{ ucfirst($user->role->role_name) }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ ucfirst($user->status ?? 'active') }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-gray-500 text-sm">{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($recentUsers->hasPages())
                    <div class="mt-4">
                        {{ $recentUsers->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <p class="text-gray-500 text-center py-4">No recent users found.</p>
            @endif
        </div>
    </div>
</div>
@endsection

