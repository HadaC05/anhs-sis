@php
    $profileTeacher = Auth::user();
    $profileTeacherName = trim(implode(' ', array_filter([
        $profileTeacher?->first_name,
        $profileTeacher?->middle_name,
        $profileTeacher?->last_name,
        $profileTeacher?->suffix,
    ]))) ?: ($profileTeacher?->username ?? 'Teacher');
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
    <style>[x-cloak] { display: none !important; }</style>

    <button
        type="button"
        @click="open = ! open"
        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"
        aria-label="Teacher profile menu"
        :aria-expanded="open.toString()"
        data-test="teacher-profile-menu"
    >
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-[60] mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl"
        role="menu"
        aria-label="Teacher profile"
        data-test="teacher-profile-dropdown"
    >
        <div class="border-b border-gray-100 px-4 py-3">
            <p class="text-sm font-bold text-gray-900" data-test="teacher-profile-name">{{ $profileTeacherName }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ $profileTeacher?->employee_no ? 'Employee No. '.$profileTeacher->employee_no : $profileTeacher?->username }}</p>
        </div>
        <div class="p-2">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50" data-test="teacher-logout-button">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
