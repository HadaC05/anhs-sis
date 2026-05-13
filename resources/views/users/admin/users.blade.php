@extends('users.admin.layout')

@section('title', 'Users')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Users</h1>
            </div>
            <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57] transition-colors shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Add New Staff
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totalUsers }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Users</p>
        </div>
        @foreach($roleCounts as $roleName => $count)
        <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-gray-100 p-4 text-center">
            <p class="text-2xl font-bold 
                    @if($roleName === 'admin') text-purple-600
                    @elseif($roleName === 'teacher') text-blue-600
                    @elseif($roleName === 'guidance counselor') text-green-600
                    @elseif($roleName === 'registrar') text-yellow-600
                    @elseif($roleName === 'principal') text-red-600
                    @elseif($roleName === 'student') text-cyan-600
                    @else text-gray-600
                    @endif">{{ $count }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ ucfirst($roleName) }}</p>
        </div>
        @endforeach
    </div>

    <!-- Tabs -->
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex">
                <a href="{{ route('admin.users', array_merge(request()->except(['tab', 'page']), ['tab' => 'staff'])) }}"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'staff' ? 'border-[#296374] text-[#296374]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Staff Users
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'staff' ? 'bg-[#296374] text-white' : 'bg-gray-200 text-gray-600' }}">{{ $staffCount }}</span>
                </a>
                <a href="{{ route('admin.users', array_merge(request()->except(['tab', 'page']), ['tab' => 'students'])) }}"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'students' ? 'border-[#296374] text-[#296374]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Students
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'students' ? 'bg-[#296374] text-white' : 'bg-gray-200 text-gray-600' }}">{{ $studentCount }}</span>
                </a>
            </nav>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 bg-gray-50/50">
            <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ $activeTab === 'students' ? 'Name, LRN, or email...' : 'Username or email...' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                </div>
                @if($activeTab === 'staff')
                <div class="w-40">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                        <option value="">All Roles</option>
                        @foreach($staffRoles as $role)
                        <option value="{{ $role->role_name }}" {{ request('role') === $role->role_name ? 'selected' : '' }}>
                            {{ ucfirst($role->role_name) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="w-40">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                    <select name="per_page" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                        @foreach([10, 15, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) ($perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57] transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'status', 'per_page']))
                <a href="{{ route('admin.users', ['tab' => $activeTab]) }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Clear
                </a>
                @endif
            </form>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">User</th>
                        @if($activeTab === 'staff')
                        <th class="px-6 py-4">Role</th>
                        @else
                        <th class="px-6 py-4">LRN</th>
                        @endif
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Created</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white
                                        @if($user->role->role_name === 'admin') bg-purple-500
                                        @elseif($user->role->role_name === 'teacher') bg-blue-500
                                        @elseif($user->role->role_name === 'guidance counselor') bg-green-500
                                        @elseif($user->role->role_name === 'registrar') bg-yellow-500
                                        @elseif($user->role->role_name === 'principal') bg-red-500
                                        @elseif($user->role->role_name === 'student') bg-cyan-500
                                        @else bg-gray-500
                                        @endif">
                                    @if($activeTab === 'students' && $user->student)
                                    {{ strtoupper(substr(optional($user->student->application)->first_name ?? $user->name ?? 'S', 0, 1)) }}
                                    @elseif($activeTab === 'staff' && $user->staff)
                                    {{ strtoupper(substr($user->staff->first_name ?? $user->username, 0, 1)) }}
                                    @else
                                    {{ strtoupper(substr($user->username, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    @if($activeTab === 'students' && $user->student)
                                    <p class="font-semibold text-gray-800">
                                        {{ optional($user->student->application)->last_name ?? $user->name }},
                                        {{ optional($user->student->application)->first_name ?? '' }}
                                        {{ optional($user->student->application)->middle_name ? substr(optional($user->student->application)->middle_name, 0, 1) . '.' : '' }}
                                    </p>
                                    @elseif($activeTab === 'staff' && $user->staff)
                                    <p class="font-semibold text-gray-800">
                                        {{ $user->staff->last_name }}, {{ $user->staff->first_name }} {{ $user->staff->middle_name ? substr($user->staff->middle_name, 0, 1) . '.' : '' }}
                                    </p>
                                    @else
                                    <p class="font-semibold text-gray-800">{{ $user->username }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        @if($activeTab === 'staff')
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
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
                        @else
                        <td class="px-6 py-4 text-gray-600">
                            {{ $user->student->lrn ?? 'Not set' }}
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ ($user->status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($user->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-sm">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($activeTab === 'students')
                                <button onclick="openEditStudentModal({{ json_encode([
                                    'id' => $user->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'lrn' => optional($user->student)->lrn,
                                    'first_name' => optional(optional($user->student)->application)->first_name,
                                    'middle_name' => optional(optional($user->student)->application)->middle_name,
                                    'last_name' => optional(optional($user->student)->application)->last_name,
                                ]) }})"
                                    class="p-2 text-gray-500 hover:text-[#296374] hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @else
                                <button onclick="openEditModal({{ json_encode($user) }})"
                                    class="p-2 text-gray-500 hover:text-[#296374] hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @endif
                                @if($activeTab === 'students' || $user->id !== auth()->user()?->staff_id)
                                <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($activeTab === 'students')
                                    <input type="hidden" name="is_student" value="1">
                                    @endif
                                    <button type="submit"
                                        class="p-2 {{ ($user->status ?? 'active') === 'active' ? 'text-gray-500 hover:text-red-600 hover:bg-red-50' : 'text-gray-500 hover:text-green-600 hover:bg-green-50' }} rounded-lg transition-colors"
                                        title="{{ ($user->status ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}">
                                        @if(($user->status ?? 'active') === 'active')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone and will also delete their profile and related data.');">
                                    @csrf
                                    @method('DELETE')
                                    @if($activeTab === 'students')
                                    <input type="hidden" name="is_student" value="1">
                                    @endif
                                    <button type="submit"
                                        class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Delete User">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <p>No {{ $activeTab === 'students' ? 'students' : 'staff users' }} found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $users->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Staff Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 class="text-xl font-bold text-gray-800">Add New Staff User</h3>
            <p class="text-sm text-gray-500 mt-1">Create a new account for a staff member</p>
        </div>
        <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Name</label>
                    <input type="text" name="middle_name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Suffix</label>
                    <input type="text" name="suffix" placeholder="Jr., Sr., III, etc."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Birthdate</label>
                <input type="date" name="birthdate"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                    <option value="">Select a role</option>
                    @foreach($staffRoles as $role)
                    <option value="{{ $role->role_name }}">{{ ucfirst($role->role_name) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAddModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57] transition-colors font-medium">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#296374]/10 to-transparent">
            <h3 class="text-xl font-bold text-gray-800">Edit Staff User</h3>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" id="edit_username" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit_email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" id="edit_role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                    @foreach($staffRoles as $role)
                    <option value="{{ $role->role_name }}">{{ ucfirst($role->role_name) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57] transition-colors font-medium">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-cyan-500/10 to-transparent">
            <h3 class="text-xl font-bold text-gray-800">Edit Student Account</h3>
        </div>
        <form id="editStudentForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_student" value="1">

            <div class="p-4 bg-cyan-50 rounded-lg border border-cyan-200">
                <p class="text-sm font-semibold text-cyan-800" id="student_name_display">Student Name</p>
                <p class="text-xs text-cyan-600" id="student_lrn_display">LRN: ---</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit_student_email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">This is also used as the username for login</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password. Use this to reset forgotten passwords.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#296374] focus:border-transparent">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeEditStudentModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-[#296374] text-white rounded-lg hover:bg-[#1e4a57] transition-colors font-medium">
                    Update Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addModal').classList.remove('flex');
    }

    function openEditModal(user) {
        document.getElementById('editForm').action = '/admin/users/' + user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role.role_name;
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }

    function openEditStudentModal(data) {
        document.getElementById('editStudentForm').action = '/admin/users/' + data.id;
        document.getElementById('edit_student_email').value = data.email;

        if (data) {
            const fullName = [data.last_name, data.first_name].filter(Boolean).join(', ');
            document.getElementById('student_name_display').textContent = fullName || 'Unknown Student';
            document.getElementById('student_lrn_display').textContent = 'LRN: ' + (data.lrn || 'Not set');
        }

        document.getElementById('editStudentModal').classList.remove('hidden');
        document.getElementById('editStudentModal').classList.add('flex');
    }

    function closeEditStudentModal() {
        document.getElementById('editStudentModal').classList.add('hidden');
        document.getElementById('editStudentModal').classList.remove('flex');
    }

    // Close modals when clicking outside
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeAddModal();
    });
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    document.getElementById('editStudentModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditStudentModal();
    });
</script>
@endsection
