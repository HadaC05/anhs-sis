<section class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="open-grading-periods-title">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="open-grading-periods-title" class="text-lg font-bold text-gray-900">Open Grading Periods</h2>
            <p class="mt-1 text-sm text-gray-500">Currently open for grade entry</p>
        </div>
        <a href="{{ route('principal.grading-term-config.index') }}" class="text-sm font-semibold text-[#296374] hover:underline">Manage grading terms</a>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($openGradingPeriods as $level => $period)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-700">{{ $level }}</h3>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $period !== null ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ $period !== null ? 'Open' : 'None open' }}
                    </span>
                </div>
                <p class="mt-3 text-lg font-bold {{ $period !== null ? 'text-[#296374]' : 'text-gray-500' }}">{{ $period ?? 'No open grading period' }}</p>
            </div>
        @endforeach
    </div>
</section>
