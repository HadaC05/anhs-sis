@php
    $statisticsConfig = ['periods' => $periods, 'bands' => $descriptorBands, 'section' => $section->name, 'grade' => $gradeLabel, 'subject' => $subjectLabel, 'year' => $section->academicYear?->school_year, 'filename' => \Illuminate\Support\Str::slug($section->name.'-'.$subjectCode)];
@endphp
<div data-grade-panel="statistics" class="hidden p-6">
    <h2 class="text-lg font-bold text-gray-900">Class Statistics</h2>
    <p class="mt-2 text-sm text-gray-500">Includes the current grade inputs, including unsaved changes. Passing rate = learners with grades of 75 or above ÷ learners with recorded grades. Missing grades are excluded, not counted as failures.</p>
    <p class="mt-2 text-sm text-gray-500">Overall uses each learner's average of available terms, matching Grade Summary. It is provisional until all terms are recorded. Descriptor scale: <strong>{{ $descriptorBands[0]['description'] === 'Advancing' ? 'SF9 Performance Report' : 'Original SF9 Progress Report' }}</strong>.</p>
    <div class="mt-6 grid min-w-0 items-start gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 lg:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-bold text-gray-800">Passing rate per term and overall</h3><button type="button" data-download-chart="passing" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Download PNG</button></div>
        <div class="mt-4 overflow-x-auto"><canvas id="passing-rate-chart" class="w-full" role="img" aria-label="Passing rates per term and overall. Exact counts are in the adjacent details card."></canvas></div>
        </div>
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="font-bold text-gray-800">Passing rate details</h3>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-sm"><caption class="sr-only">Passing rate statistics by period</caption><thead class="bg-gray-50"><tr><th scope="col" class="p-2 text-left">Measure</th>@foreach($periods as $period)<th scope="col" class="p-2">{{ $period['label'] }}</th>@endforeach<th scope="col" class="p-2">Overall</th></tr></thead><tbody id="passing-rate-rows"></tbody></table></div>
        <p id="statistics-completeness" class="mt-3 text-xs text-gray-500"></p>
        </div>
    </div>
    <div class="mt-6 grid min-w-0 items-start gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 lg:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-bold text-gray-800">Students by descriptor</h3>
            <div class="flex flex-wrap items-center gap-3"><label for="descriptor-period" class="text-sm font-semibold text-gray-700">Period</label><select id="descriptor-period" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="overall">Overall</option>@foreach($periods as $period)<option value="{{ $period['key'] }}">{{ $period['label'] }}</option>@endforeach</select><button type="button" data-download-chart="descriptors" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Download PNG</button></div>
        </div>
        <canvas id="descriptor-chart" class="mt-4 w-full" role="img" aria-label="Number of students by descriptor. Exact counts are in the adjacent details card."></canvas>
        </div>
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="font-bold text-gray-800">Descriptor details</h3>
        <table class="mt-4 w-full text-sm"><caption class="sr-only">Descriptor distribution</caption><thead class="bg-gray-50"><tr><th class="p-2 text-left">Descriptor</th><th class="p-2 text-left">Grading scale</th><th class="p-2 text-right">Students</th></tr></thead><tbody id="descriptor-rows"></tbody></table>
        </div>
    </div>
    <p id="statistics-export-error" role="alert" class="mt-3 text-sm text-red-700" hidden></p>
    <script type="application/json" id="grade-statistics-config">@json($statisticsConfig)</script>
</div>

@vite('resources/js/app.js')
