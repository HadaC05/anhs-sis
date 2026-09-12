@extends('users.admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600 md:text-base">System users and account overview</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white/90 px-4 py-3 shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">System users</p>
        <p class="text-sm font-semibold text-gray-700">{{ number_format($totalUsers) }} accounts</p>
    </div>
</div>

<div class="mb-8">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Users in the System</h2>
            <p class="mt-1 text-sm text-gray-500">Staff accounts, student portal users, and account status</p>
        </div>
        <a href="{{ route('admin.users') }}" class="text-sm font-semibold text-[#296374] transition hover:underline">Manage all users</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('admin.users') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#296374]/30 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Users</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Staff + student accounts</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#296374]/10 text-[#296374]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path></svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.users') }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Staff Members</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($staffCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500">All personnel roles</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"></path></svg>
                </div>
            </div>
        </a>

        <div class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Student Records</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($studentCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ number_format($studentAccountCount) }} with login accounts</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 0 1 .665 6.479A11.952 11.952 0 0 0 12 20.055a11.952 11.952 0 0 0-6.824-2.998 12.078 12.078 0 0 1 .665-6.479L12 14z"></path></svg>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.users', ['status' => 'active']) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Active Staff</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($activeStaffCount) }}</p>
                    <p class="mt-1 text-xs text-emerald-600">{{ $staffCount > 0 ? round(($activeStaffCount / $staffCount) * 100) : 0 }}% of staff</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.users', ['status' => 'inactive']) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-200 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Inactive Staff</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($inactiveStaffCount) }}</p>
                    <p class="mt-1 text-xs text-rose-600">{{ $staffCount > 0 ? round(($inactiveStaffCount / $staffCount) * 100) : 0 }}% of staff</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-900">Users by Role</h2>
            <p class="mt-1 text-sm text-gray-500">Distribution of staff accounts across system roles</p>
        </div>
        <div class="relative h-72">
            <canvas id="usersByRoleChart"></canvas>
            @if (collect($usersByRole)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No user data yet</div>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-900">Staff Account Status</h2>
            <p class="mt-1 text-sm text-gray-500">Active vs inactive staff logins</p>
        </div>
        <div class="relative mx-auto h-72 max-w-sm">
            <canvas id="staffStatusChart"></canvas>
            @if (collect($staffStatusDistribution)->sum('total') === 0)
                <div class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">No staff accounts yet</div>
            @endif
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900">System Users</h3>
            <p class="mt-1 text-sm text-gray-500">Staff accounts across all roles</p>
        </div>
        <a href="{{ route('admin.users') }}" class="text-sm font-semibold text-[#296374] transition hover:underline">Manage users</a>
    </div>

    <div class="border-b border-gray-100 px-4 py-4">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path></svg>
                <input type="search" name="recent_users_search" value="{{ request('recent_users_search') }}" placeholder="Search name, username, or email" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            <select name="recent_users_role" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All roles</option>
                @foreach ($roles as $roleName)
                    <option value="{{ $roleName }}" {{ request('recent_users_role') === $roleName ? 'selected' : '' }}>{{ ucfirst($roleName) }}</option>
                @endforeach
            </select>
            <select name="recent_users_status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All status</option>
                <option value="active" {{ request('recent_users_status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('recent_users_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <select name="recent_users_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach ([5, 10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" {{ (int) ($usersPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">User</th>
                    <th class="border-r border-gray-200 px-5 py-4">Username</th>
                    <th class="border-r border-gray-200 px-5 py-4">Role</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="px-5 py-4">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($recentUsers as $user)
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ trim($user->first_name.' '.$user->last_name) ?: '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email ?: 'No email' }}</p>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $user->username }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1
                                @if($user->role?->role_name === 'admin') bg-purple-50 text-purple-700 ring-purple-200
                                @elseif($user->role?->role_name === 'teacher') bg-blue-50 text-blue-700 ring-blue-200
                                @elseif($user->role?->role_name === 'guidance counselor') bg-emerald-50 text-emerald-700 ring-emerald-200
                                @elseif($user->role?->role_name === 'registrar') bg-amber-50 text-amber-700 ring-amber-200
                                @elseif($user->role?->role_name === 'principal') bg-rose-50 text-rose-700 ring-rose-200
                                @else bg-gray-100 text-gray-700 ring-gray-200
                                @endif">
                                {{ ucfirst($user->role?->role_name ?? 'unknown') }}
                            </span>
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                {{ ucfirst($user->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $user->created_at?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($recentUsers->hasPages())
        <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            {{ $recentUsers->links() }}
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var palette = ['#296374', '#8b5cf6', '#3b82f6', '#14b8a6', '#f59e0b', '#f97316', '#64748b'];
        var usersByRole = @json($usersByRole);
        var staffStatus = @json($staffStatusDistribution);

        if (usersByRole.length > 0 && document.getElementById('usersByRoleChart')) {
            new Chart(document.getElementById('usersByRoleChart'), {
                type: 'bar',
                data: {
                    labels: usersByRole.map(function (item) { return item.label; }),
                    datasets: [{
                        label: 'Users',
                        data: usersByRole.map(function (item) { return item.total; }),
                        backgroundColor: palette,
                        borderRadius: 8,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                        y: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                    },
                },
            });
        }

        if (staffStatus.length > 0 && document.getElementById('staffStatusChart')) {
            new Chart(document.getElementById('staffStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: staffStatus.map(function (item) { return item.label; }),
                    datasets: [{
                        data: staffStatus.map(function (item) { return item.total; }),
                        backgroundColor: ['#10b981', '#f43f5e'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 12, weight: '600' } } },
                    },
                },
            });
        }
    });
</script>
@endsection
