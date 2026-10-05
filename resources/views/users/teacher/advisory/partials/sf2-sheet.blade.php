@php
    $layout = $selectedUpload->sf2_layout ?? [];
    $showPresent = $sf2Configuration->format === 'lis';
    $secondTotal = $showPresent ? 'days_present' : 'days_tardy';
    $dates = $selectedUpload->class_dates ?? [];
    $sheetRows = collect($selectedUpload->import_rows)->map(function ($row) use ($layout) {
        return $row + ($layout['rows'][$row['page'].'-'.$row['row_number']] ?? ['sex' => 'Unspecified', 'daily' => null, 'tardy_dates_complete' => false]);
    });
    $groups = $sheetRows->groupBy('sex');
    $dayTotal = fn ($group, $date) => $group->every(fn ($row) => isset($row['daily'][$date]))
        ? $group->filter(fn ($row) => $row['daily'][$date] !== 'X')->count() : '—';
    $markLabels = ['X' => 'Absent', 'L' => 'Late arrival', 'C' => 'Cutting classes', 'T' => 'Tardy', '' => 'No absence or extracted tardy mark', '?' => 'Daily record unavailable'];
@endphp

<section data-sf2-format="{{ $sf2Configuration->format }}" class="overflow-hidden rounded-xl border border-[#296374]/35 bg-white shadow-sm" aria-labelledby="sf2-sheet-title">
    <div class="border-b border-gray-200 bg-[#eef5f7] px-5 py-4">
        <h3 id="sf2-sheet-title" class="font-bold text-[#296374]">School Form 2 (SF2) — Daily Attendance Report of Learners</h3>
        <p class="mt-1 text-xs text-gray-600">Recreated from {{ $selectedUpload->original_filename }}. Displayed as {{ $sf2Configuration->selectedFormat()['label'] }}.</p>
    </div>
    <div class="flex flex-wrap gap-x-8 gap-y-2 border-b border-gray-200 px-5 py-4 text-sm text-gray-700">
        @if(!empty($layout['school_name']))<p><span class="text-gray-500">School:</span> <strong>{{ $layout['school_name'] }}</strong></p>@endif
        <p><span class="text-gray-500">Grade / Section:</span> <strong>{{ $layout['section'] ?? $section->name }}</strong></p>
        <p><span class="text-gray-500">Report month:</span> <strong>{{ $months[$selectedMonth] }}</strong></p>
        <p><span class="text-gray-500">Class days:</span> <strong>{{ $selectedUpload->school_days }}</strong></p>
        <p><span class="text-gray-500">School year in system:</span> <strong>{{ $section->academicYear->school_year }}</strong></p>
    </div>
    @if($selectedUpload->use_section_school_year)
        <p class="border-b border-amber-200 bg-amber-50 px-5 py-3 text-xs text-amber-900">This earlier upload was recorded under {{ $section->academicYear->school_year }} with a different PDF year. The date headings retain the original {{ $months[$selectedMonth] }} {{ $selectedUpload->report_year }} calendar.</p>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1200px] border-collapse text-xs">
            <caption class="sr-only">SF2 daily attendance by learner, grouped by sex, with monthly absence and tardy totals.</caption>
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th rowspan="2" class="border border-gray-200 px-2 py-3">No.</th>
                    <th rowspan="2" class="sticky left-0 z-10 min-w-[240px] border border-gray-200 bg-gray-50 px-3 py-3 text-left">Learner's name<br><span class="font-normal text-gray-500">Last name, first name, middle name</span></th>
                    @foreach($dates as $date)
                        <th class="min-w-[30px] border border-gray-200 px-1 py-2">{{ (int) substr($date, -2) }}</th>
                    @endforeach
                    <th colspan="2" class="border border-gray-200 px-3 py-2">Total for the month</th>
                    <th rowspan="2" class="min-w-[170px] border border-gray-200 px-3 py-2 text-left">Remarks</th>
                </tr>
                <tr>
                    @foreach($dates as $date)
                        <th class="border border-gray-200 px-1 py-2 text-[10px] font-medium">{{ ['Mon'=>'M','Tue'=>'T','Wed'=>'W','Thu'=>'TH','Fri'=>'F','Sat'=>'S','Sun'=>'SU'][date('D', strtotime($date))] }}</th>
                    @endforeach
                    <th class="border border-gray-200 px-3 py-2 text-red-700">Absent</th>
                    <th class="border border-gray-200 px-3 py-2 {{ $showPresent ? 'text-emerald-700' : 'text-amber-700' }}" data-test="sf2-second-total">{{ $showPresent ? 'Present' : 'Tardy' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $sex => $group)
                    <tr><th colspan="{{ count($dates) + 5 }}" class="border border-gray-200 bg-[#eef5f7] px-3 py-2 text-left font-bold uppercase tracking-wide text-[#296374]">{{ $sex }} learners</th></tr>
                    @foreach($group as $learner)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-200 px-2 py-2 text-center">{{ $learner['row_number'] }}</td>
                            <th scope="row" class="sticky left-0 z-10 border border-gray-200 bg-white px-3 py-2 text-left font-medium">{{ $learner['name'] }}</th>
                            @foreach($dates as $date)
                                @php $mark = $learner['daily'][$date] ?? '?'; @endphp
                                <td class="border border-gray-200 px-1 py-2 text-center font-bold {{ $mark === 'X' ? 'bg-red-50 text-red-700' : (in_array($mark, ['L','C','T']) ? 'bg-amber-50 text-amber-800' : 'text-gray-400') }}" title="{{ $learner['name'] }} — {{ $date }}: {{ $markLabels[$mark] }}" aria-label="{{ $markLabels[$mark] }}">{{ ['L'=>'◤','C'=>'◢'][$mark] ?? $mark }}</td>
                            @endforeach
                            <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-red-700">{{ $learner['days_absent'] }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-center font-semibold {{ $showPresent ? 'text-emerald-700' : 'text-amber-700' }}">{{ $learner[$secondTotal] }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-600">{{ $learner['remarks'] }} @if(!$learner['tardy_dates_complete'] && $learner['days_tardy'] > 0)<span class="block text-amber-800">Tardy dates unavailable; monthly total retained.</span>@endif</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 font-semibold">
                        <th colspan="2" class="border border-gray-200 px-3 py-2 text-left">{{ $sex }} | Total present per day</th>
                        @foreach($dates as $date)<td class="border border-gray-200 px-1 py-2 text-center">{{ $dayTotal($group, $date) }}</td>@endforeach
                        <td class="border border-gray-200 px-3 py-2 text-center">{{ $group->sum('days_absent') }}</td><td class="border border-gray-200 px-3 py-2 text-center">{{ $group->sum($secondTotal) }}</td><td class="border border-gray-200"></td>
                    </tr>
                @endforeach
                <tr class="bg-[#eef5f7] font-bold text-[#296374]">
                    <th colspan="2" class="border border-gray-200 px-3 py-3 text-left">Combined total present per day</th>
                    @foreach($dates as $date)<td class="border border-gray-200 px-1 py-3 text-center">{{ $dayTotal($sheetRows, $date) }}</td>@endforeach
                    <td class="border border-gray-200 px-3 py-3 text-center">{{ $sheetRows->sum('days_absent') }}</td><td class="border border-gray-200 px-3 py-3 text-center">{{ $sheetRows->sum($secondTotal) }}</td><td class="border border-gray-200"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="px-5 py-3 text-xs text-gray-500">Blank = no absence/tardy mark &middot; X = absent &middot; ◤ = late arrival &middot; ◢ = cutting classes &middot; T = tardy &middot; ? = daily marks could not be extracted. Tardy learners are included in the daily present count.</p>
</section>
