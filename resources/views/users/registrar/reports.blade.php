@extends('users.registrar.layout')

@section('title', $title)

@section('content')
    <style>
        @media print {
            @page { size: landscape; margin: 12mm; }
            .registrar-topbar, .app-sidebar, #sidebar-backdrop, .report-controls { display: none !important; }
            .registrar-shell { background: none !important; padding: 0 !important; }
            .app-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .registrar-content { max-width: none !important; padding: 0 !important; }
            .report-table { overflow: visible !important; }
            table { font-size: 10px !important; }
            tr { break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold text-[#296374]">Registrar Reports · Agusan National High School</p>
            <h1 class="mt-1 text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $description }}</p>
        </div>

        <form method="GET" action="{{ route('registrar.reports.index') }}" class="report-controls rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <input type="hidden" name="report" value="{{ $report }}">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700">School year</label>
                    <select id="academic_year_id" name="academic_year_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm">
                        @forelse ($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" @selected($selectedYear?->SY_ID === $year->SY_ID)>{{ $year->school_year }}</option>
                        @empty
                            <option value="">No school years available</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label for="grade_id" class="block text-sm font-medium text-gray-700">Grade level</label>
                    <select id="grade_id" name="grade_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm">
                        <option value="">All grade levels</option>
                        @foreach ($gradeLevels as $grade)
                            <option value="{{ $grade->grade_ID }}" @selected((string) $gradeId === (string) $grade->grade_ID)>{{ $grade->grade_label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($report === 'grades')
                    <div>
                        <label for="term_id" class="block text-sm font-medium text-gray-700">Grading term</label>
                        <select id="term_id" name="term_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm">
                            <option value="">All grading terms</option>
                            @foreach ($terms as $term)
                                <option value="{{ $term->term_ID }}" @selected((string) $termId === (string) $term->term_ID)>{{ $term->label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex items-end"><button class="rounded-lg bg-[#296374] px-5 py-2 text-sm font-semibold text-white hover:bg-[#205160]">Generate report</button></div>
            </div>
            @if ($errors->any())
                <p class="mt-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
            @endif
        </form>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $scope }}</p>
                    <p class="mt-1 text-xs text-gray-500">Generated {{ $generatedAt }} (Asia/Manila)</p>
                </div>
                <div class="report-controls flex flex-wrap gap-2">
                    <a href="{{ route('registrar.reports.index', ['report' => $report, 'academic_year_id' => $selectedYear?->SY_ID, 'grade_id' => $gradeId, 'term_id' => $termId, 'format' => 'csv']) }}" class="rounded-lg border border-[#296374] px-4 py-2 text-sm font-semibold text-[#296374] hover:bg-slate-50">Export CSV</a>
                    <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Print / Save PDF</button>
                </div>
            </div>
            <div class="p-5 bg-slate-50">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $totalLabel }}</p>
                <p class="mt-1 text-3xl font-bold text-[#296374]">{{ number_format($total) }}</p>
            </div>
            <div class="report-table overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ $title }} — {{ $scope }}</caption>
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr>
                        @foreach ($headers as $header)<th scope="col" class="whitespace-nowrap px-5 py-3">{{ $header }}</th>@endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-slate-50">
                                @foreach ($row as $cell)<td class="px-5 py-3 {{ is_int($cell) ? 'tabular-nums' : '' }}">{{ is_int($cell) ? number_format($cell) : $cell }}</td>@endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($headers) }}" class="px-5 py-12 text-center text-gray-500">No records found for the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($report === 'grades')<p class="p-5 text-xs text-gray-500">Counts represent individual learner grade records across the selected terms. Classes with zero total records have no encoded grades in this period.</p>@endif
        </div>
    </div>
@endsection
