<div class="mb-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">Academic Setup</h1>
            <p class="mt-1 text-sm text-gray-500">Manage school years and grading periods.</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Current academic year</p>
            <p class="mt-1 font-bold text-[#296374]">{{ $currentYear?->school_year ?? 'No active school year' }}</p>
        </div>
    </div>
    <style>
        .academic-setup-tab { background: white; color: #4b5563; }
        .academic-setup-tab:hover { background: #f0f7f8; color: #296374; }
        .academic-setup-tab[aria-selected="true"] { background: #296374; color: white; box-shadow: 0 2px 6px rgb(41 99 116 / 25%); }
    </style>
    <nav aria-label="Academic setup" role="tablist" class="mt-6 grid w-full grid-cols-2 gap-2 rounded-xl border border-gray-200 bg-gray-100 p-2 shadow-sm">
        @foreach (['academic-year-config' => 'Academic Years', 'grading-term-config' => 'Grading Terms'] as $page => $label)
            @php($selected = $setupTab === $page)
            <a href="{{ route($managementRoutePrefix.$page.'.index') }}"
                id="{{ $page }}-tab" data-academic-tab="{{ $page }}" role="tab"
                aria-controls="{{ $page }}-panel" aria-selected="{{ $selected ? 'true' : 'false' }}" tabindex="{{ $selected ? '0' : '-1' }}"
                class="academic-setup-tab flex min-h-14 min-w-0 items-center justify-center rounded-lg px-3 py-4 text-center text-sm font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#296374] sm:text-base">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
