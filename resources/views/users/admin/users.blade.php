@extends('users.admin.layout')

@section('title', 'Users')

@section('content')
@php
$roleCardStyles = [
'admin' => ['text' => 'text-purple-700', 'hover' => 'hover:border-purple-200'],
'teacher' => ['text' => 'text-blue-700', 'hover' => 'hover:border-blue-200'],
'guidance counselor' => ['text' => 'text-emerald-700', 'hover' => 'hover:border-emerald-200'],
'registrar' => ['text' => 'text-amber-700', 'hover' => 'hover:border-amber-200'],
'principal' => ['text' => 'text-rose-700', 'hover' => 'hover:border-rose-200'],
'student' => ['text' => 'text-cyan-700', 'hover' => 'hover:border-cyan-200'],
];
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Users</h1>
    </div>
    <button type="button" onclick="openAddModal()" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">
        Add New Staff
    </button>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
    {{ session('success') }}
</div>
@endif
@if($errors->any() && old('_form') !== 'add_staff')
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    @foreach($errors->all() as $error)
    <p>{{ $error }}</p>
    @endforeach
</div>
@endif

<div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
    <a href="{{ route('admin.users', ['tab' => 'staff']) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#296374]/30 hover:shadow-md">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Users</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
        <p class="mt-1 text-xs text-gray-500">Staff + student accounts</p>
    </a>
    @foreach($roleCounts as $roleName => $count)
    @php
    $styles = $roleCardStyles[$roleName] ?? ['text' => 'text-gray-800', 'hover' => 'hover:border-gray-300'];
    $cardUrl = $roleName === 'student'
    ? route('admin.users', ['tab' => 'students'])
    : route('admin.users', ['tab' => 'staff', 'role' => $roleName]);
    @endphp
    <a href="{{ $cardUrl }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $styles['hover'] }}">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ ucfirst($roleName) }}</p>
        <p class="mt-2 text-3xl font-bold {{ $styles['text'] }}">{{ number_format($count) }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $roleName === 'student' ? 'Portal accounts' : 'Staff accounts' }}</p>
    </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ $activeTab === 'students' ? 'Student Accounts' : 'Staff Users' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $activeTab === 'students' ? 'Student portal usernames and login status' : 'Personnel accounts across all staff roles' }}</p>
        </div>
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
            <a href="{{ route('admin.users', array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'staff'])) }}"
                class="rounded-md px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'staff' ? 'bg-white text-[#296374] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                Staff
                <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'staff' ? 'bg-[#296374] text-white' : 'bg-gray-200 text-gray-600' }}">{{ $staffCount }}</span>
            </a>
            <a href="{{ route('admin.users', array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'students'])) }}"
                class="rounded-md px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'students' ? 'bg-white text-[#296374] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                Students
                <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'students' ? 'bg-[#296374] text-white' : 'bg-gray-200 text-gray-600' }}">{{ $studentCount }}</span>
            </a>
        </div>
    </div>

    <div class="border-b border-gray-100 px-4 py-4">
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                </svg>
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ $activeTab === 'students' ? 'Search name, LRN, or email' : 'Search name, username, or email' }}"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
            </div>
            @if($activeTab === 'staff')
            <select name="role" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="">All roles</option>
                @foreach($staffRoles as $role)
                <option value="{{ $role->role_name }}" {{ request('role') === $role->role_name ? 'selected' : '' }}>
                    {{ ucfirst($role->role_name) }}
                </option>
                @endforeach
            </select>
            @endif
            <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <option value="active" @selected(request('status') !== 'inactive')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                @foreach([10, 15, 25, 50, 100] as $size)
                <option value="{{ $size }}" {{ (int) ($perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
            @if(request()->hasAny(['search', 'role', 'status', 'per_page']))
            <a href="{{ route('admin.users', ['tab' => $activeTab]) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">Name</th>
                    @if($activeTab === 'staff')
                    <th class="border-r border-gray-200 px-5 py-4">Username</th>
                    <th class="border-r border-gray-200 px-5 py-4">Role</th>
                    @else
                    <th class="border-r border-gray-200 px-5 py-4">Username</th>
                    <th class="border-r border-gray-200 px-5 py-4">LRN</th>
                    @endif
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="border-r border-gray-200 px-5 py-4">Created</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($users as $user)
                @php
                $roleName = $user->roleName() !== '' ? $user->roleName() : ($activeTab === 'students' ? 'student' : 'unknown');
                $displayName = trim(($user->last_name ? $user->last_name.', ' : '').$user->first_name.($user->middle_name ? ' '.substr($user->middle_name, 0, 1).'.' : '').($user->suffix ? ' '.$user->suffix : ''));
                $isActive = $activeTab === 'students'
                    ? $user->status !== 'inactive'
                    : ($user->status ?? 'active') === 'active';
                @endphp
                <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                    <td class="border-r border-gray-100 px-5 py-4">
                        <p class="font-semibold text-gray-900">{{ $displayName !== '' ? $displayName : '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email ?: 'No email' }}</p>
                    </td>
                    @if($activeTab === 'staff')
                    <td class="border-r border-gray-100 px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $user->username }}</td>
                    <td class="border-r border-gray-100 px-5 py-4">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1
                                    @if($roleName === 'admin') bg-purple-50 text-purple-700 ring-purple-200
                                    @elseif($roleName === 'teacher') bg-blue-50 text-blue-700 ring-blue-200
                                    @elseif($roleName === 'guidance counselor') bg-emerald-50 text-emerald-700 ring-emerald-200
                                    @elseif($roleName === 'registrar') bg-amber-50 text-amber-700 ring-amber-200
                                    @elseif($roleName === 'principal') bg-rose-50 text-rose-700 ring-rose-200
                                    @else bg-gray-100 text-gray-700 ring-gray-200
                                    @endif">
                            {{ ucfirst($roleName) }}
                        </span>
                    </td>
                    @else
                    <td class="border-r border-gray-100 px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $user->username ?: '—' }}</td>
                    <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $user->lrn ?: 'Not set' }}</td>
                    @endif
                    <td class="border-r border-gray-100 px-5 py-4">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $isActive ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                            {{ $isActive ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="border-r border-gray-100 px-5 py-4 text-gray-600">{{ $user->created_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1">
                            @if($activeTab === 'students')
                            <button type="button" onclick="openEditStudentModal({{ json_encode([
                                        'id' => $user->id,
                                        'username' => $user->username,
                                        'email' => $user->email,
                                        'lrn' => $user->lrn,
                                        'first_name' => $user->first_name,
                                        'middle_name' => $user->middle_name,
                                        'last_name' => $user->last_name,
                                    ]) }})"
                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            @else
                            <button type="button" onclick="openEditModal({{ json_encode($user) }})"
                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    class="rounded-lg p-2 {{ $isActive ? 'text-gray-500 hover:bg-red-50 hover:text-red-600' : 'text-gray-500 hover:bg-emerald-50 hover:text-emerald-600' }} transition"
                                    title="{{ $isActive ? 'Deactivate' : 'Activate' }}">
                                    @if($isActive)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                    @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    @endif
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-gray-500">No {{ $activeTab === 'students' ? 'students' : 'staff users' }} found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
</div>

@php
$addStaffModalOpen = $errors->any() && old('_form') === 'add_staff';
$addFieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
$addPasswordFieldClass = 'h-10 w-full rounded-lg border bg-white pl-3 pr-10 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
@endphp
<div id="addModal" role="dialog" aria-modal="true" aria-labelledby="addStaffTitle" data-open="{{ $addStaffModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $addStaffModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto flex max-h-[calc(100vh-8rem)] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="shrink-0 border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">User management</p>
                    <h3 id="addStaffTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add new staff</h3>
                    <p class="mt-1 text-sm text-white/80">Create a staff login and assign a system role.</p>
                </div>
                <button type="button" onclick="closeAddModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="addStaffForm" action="{{ route('admin.users.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_form" value="add_staff">

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                @if($addStaffModalOpen)
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Please fix the highlighted fields before creating this account.</p>
                </div>
                @endif

                <section>
                    <div class="mb-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-400">Personal information</p>
                        <p class="mt-1 text-sm text-gray-500">Name details as they should appear in staff records.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add_first_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">First name <span class="text-red-500">*</span></label>
                            <input id="add_first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name"
                                class="{{ $addFieldClass }} {{ $errors->has('first_name') ? 'border-red-300' : 'border-gray-200' }}">
                            @error('first_name')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_last_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Last name <span class="text-red-500">*</span></label>
                            <input id="add_last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                                class="{{ $addFieldClass }} {{ $errors->has('last_name') ? 'border-red-300' : 'border-gray-200' }}">
                            @error('last_name')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_middle_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Middle name</label>
                            <input id="add_middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}" autocomplete="additional-name"
                                class="{{ $addFieldClass }} border-gray-200">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="add_suffix" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Suffix</label>
                                <select id="add_suffix" name="suffix" class="{{ $addFieldClass }} {{ $errors->has('suffix') ? 'border-red-300' : 'border-gray-200' }}">
                                    <option value="">None</option>
                                    @foreach ($suffixOptions as $suffixOption)
                                        <option value="{{ $suffixOption }}" @selected(old('suffix') === $suffixOption)>{{ $suffixOption }}</option>
                                    @endforeach
                                </select>
                                @error('suffix')
                                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="add_birthdate" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Birthdate <span class="text-red-500">*</span></label>
                                <input id="add_birthdate" type="date" name="birthdate" value="{{ old('birthdate') }}" required
                                    min="{{ $earliestBirthdate }}" max="{{ $latestBirthdate }}"
                                    class="{{ $addFieldClass }} {{ $errors->has('birthdate') ? 'border-red-300' : 'border-gray-200' }}">
                                @error('birthdate')
                                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="my-6 border-t border-gray-200"></div>

                <section>
                    <div class="mb-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-400">Account access</p>
                        <p class="mt-1 text-sm text-gray-500">Login credentials and the role this staff member will use.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="add_role" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Role <span class="text-red-500">*</span></label>
                            <select id="add_role" name="role" required class="{{ $addFieldClass }} {{ $errors->has('role') ? 'border-red-300' : 'border-gray-200' }}">
                                <option value="">Select a role</option>
                                @foreach($staffRoles as $role)
                                <option value="{{ $role->role_name }}" @selected(old('role')===$role->role_name)>{{ ucfirst($role->role_name) }}</option>
                                @endforeach
                            </select>
                            @error('role')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_username" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Username <span class="text-red-500">*</span></label>
                            <input id="add_username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                                class="{{ $addFieldClass }} {{ $errors->has('username') ? 'border-red-300' : 'border-gray-200' }}">
                            @error('username')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Email <span class="text-red-500">*</span></label>
                            <input id="add_email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                class="{{ $addFieldClass }} {{ $errors->has('email') ? 'border-red-300' : 'border-gray-200' }}">
                            @error('email')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="add_password" type="password" name="password" required minlength="12" autocomplete="new-password"
                                    class="{{ $addPasswordFieldClass }} {{ $errors->has('password') ? 'border-red-300' : 'border-gray-200' }}">
                                <button type="button" onclick="togglePasswordVisibility('add_password', this)" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400 transition hover:text-gray-600" aria-label="Show password">
                                    <svg data-icon="show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg data-icon="hide" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                </button>
                            </div>
                            @error('password')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add_password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Confirm password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="add_password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"
                                    class="{{ $addPasswordFieldClass }} {{ $errors->has('password_confirmation') ? 'border-red-300' : 'border-gray-200' }}">
                                <button type="button" onclick="togglePasswordVisibility('add_password_confirmation', this)" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400 transition hover:text-gray-600" aria-label="Show password">
                                    <svg data-icon="show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg data-icon="hide" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-gray-500">Use at least 12 characters, with uppercase and lowercase letters, a number, and a symbol. The staff member will be asked to change this password after first login.</p>
                </section>
            </div>

            <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeAddModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Create staff account
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-gray-200 px-6 py-5">
            <h3 class="text-xl font-bold text-gray-800">Edit Staff User</h3>
        </div>
        <form id="editForm" method="POST" class="space-y-4 p-6">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" id="edit_username" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit_email" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Role <span class="text-red-500">*</span></label>
                <select name="role" id="edit_role" required class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
                    @foreach($staffRoles as $role)
                    <option value="{{ $role->role_name }}">{{ ucfirst($role->role_name) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">New Password</label>
                <input type="password" name="password" minlength="12" autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
                <p class="mt-1 text-xs text-gray-500">Leave blank to keep the current password. If changing, use at least 12 characters, with uppercase and lowercase letters, a number, and a symbol.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" minlength="12" autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeEditModal()" class="flex-1 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-700 transition hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="flex-1 rounded-lg px-4 py-2 font-medium text-white transition hover:opacity-90" style="background-color: #296374;">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editStudentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-gray-200 px-6 py-5">
            <h3 class="text-xl font-bold text-gray-800">Edit Student Account</h3>
        </div>
        <form id="editStudentForm" method="POST" class="space-y-4 p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_student" value="1">

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-800" id="student_name_display">Student Name</p>
                <p class="text-xs text-gray-500" id="student_lrn_display">LRN: ---</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="edit_student_email" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
                <p class="mt-1 text-xs text-gray-500">This is also used as the username for login</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">New Password</label>
                <input type="password" name="password" minlength="12" autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
                <p class="mt-1 text-xs text-gray-500">Leave blank to keep the current password. If changing, use at least 12 characters, with uppercase and lowercase letters, a number, and a symbol.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" minlength="12" autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 outline-none focus:border-transparent focus:ring-2 focus:ring-[#296374]">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeEditStudentModal()" class="flex-1 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-700 transition hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="flex-1 rounded-lg px-4 py-2 font-medium text-white transition hover:opacity-90" style="background-color: #296374;">
                    Update Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        var modal = document.getElementById('addModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        var firstField = document.getElementById('add_first_name');
        if (firstField) {
            firstField.focus();
        }
    }

    function closeAddModal() {
        var modal = document.getElementById('addModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
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

    function togglePasswordVisibility(inputId, button) {
        var input = document.getElementById(inputId);
        var showIcon = button.querySelector('[data-icon="show"]');
        var hideIcon = button.querySelector('[data-icon="hide"]');
        var isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        showIcon.classList.toggle('hidden', isHidden);
        hideIcon.classList.toggle('hidden', !isHidden);
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    }

    function syncPasswordConfirmationValidity() {
        var password = document.getElementById('add_password');
        var confirmation = document.getElementById('add_password_confirmation');

        if (!password || !confirmation) {
            return true;
        }

        if (confirmation.value !== '' && confirmation.value !== password.value) {
            confirmation.setCustomValidity('The confirm password must match the password.');
            return false;
        }

        confirmation.setCustomValidity('');
        return true;
    }

    document.getElementById('addStaffForm').addEventListener('submit', function (event) {
        if (! syncPasswordConfirmationValidity()) {
            event.preventDefault();
            document.getElementById('add_password_confirmation').reportValidity();
        }
    });
    document.getElementById('add_password').addEventListener('input', syncPasswordConfirmationValidity);
    document.getElementById('add_password_confirmation').addEventListener('input', syncPasswordConfirmationValidity);

    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeAddModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('addModal').getAttribute('data-open') === 'true') {
            closeAddModal();
        }
    });
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    document.getElementById('editStudentModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditStudentModal();
    });
</script>
@endsection
