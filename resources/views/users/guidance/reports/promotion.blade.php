@extends(\App\Support\AcademicPortal::layout('guidance'))
@section('title', 'Promotion Reports')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div><h1 class="text-2xl font-bold text-gray-800">Promotion Reports</h1><p class="mt-1 text-sm text-gray-600">Review saved promotion outcomes and learner records.</p></div>
    <div class="flex flex-wrap gap-2">
        @foreach(['summary' => 'Download summary CSV', 'records' => 'Download records CSV'] as $download => $label)
            <a href="{{ route(\App\Support\AcademicPortal::routeName('guidance.reports.promotion'), array_merge($filters, ['download' => $download])) }}" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060]">{{ $label }}</a>
        @endforeach
    </div>
</div>
<form method="GET" action="{{ route(\App\Support\AcademicPortal::routeName('guidance.reports.promotion')) }}" class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach([
            'academic_year_id' => ['School year', $years->pluck('school_year', 'SY_ID')],
            'grade_id' => ['Grade level', $grades->pluck('grade_label', 'grade_ID')],
            'status' => ['Promotion status', $statuses],
            'enrollment_status' => ['Enrollment status', $enrollmentStatuses],
        ] as $key => [$label, $options])
            <div>
                <label for="{{ $key }}" class="mb-1 block text-xs font-semibold text-gray-600">{{ $label }}</label>
                <select id="{{ $key }}" name="{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">{{ ['academic_year_id' => 'All school years', 'grade_id' => 'All grade levels', 'status' => 'All promotion statuses', 'enrollment_status' => 'All enrollment statuses'][$key] }}</option>
                    @foreach($options as $value => $name)
                        @if((string) $value !== '')
                            <option value="{{ $value }}" @selected((string) ($filters[$key] ?? '') === (string) $value)>{{ $name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        @endforeach
        <div><label for="search" class="mb-1 block text-xs font-semibold text-gray-600">Learner name or LRN</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search name or LRN" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    </div>
    @if($errors->any())<p role="alert" class="mt-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
    <div class="mt-4 flex items-center gap-4"><button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Apply filters</button><a href="{{ route(\App\Support\AcademicPortal::routeName('guidance.reports.promotion')) }}" class="text-sm text-gray-600 hover:underline">Reset</a></div>
</form>
<div class="mb-6 rounded-xl border border-[#296374]/20 bg-white p-4 text-sm text-gray-600">
    <p class="font-semibold text-[#296374]">{{ $reportContext[0] }} &middot; Generated {{ $generatedAt }}</p>
    <p class="mt-2">This report uses saved promotion statuses and counts enrollment records. All enrollment statuses are included unless filtered. Select all school years to compare outcomes across years; the same learner may appear in multiple years.</p>
    <p class="mt-2">Eligible learners have not yet been promoted. Conditional promotion does not permit advancement. Grade 12 learners marked eligible have completed Grade 12.</p>
    @unless (request()->routeIs('admin.*'))
    <a href="{{ route(\App\Support\AcademicPortal::routeName('guidance.promotions.index'), ['academic_year_id' => $filters['academic_year_id'], 'eligibility' => 'all']) }}" class="mt-2 inline-block font-semibold text-[#296374] hover:underline">Open promotion evaluation and confirmation</a>
    @endunless
</div>
<div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold text-gray-500">Total records</p><p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($total) }}</p></div>
    @foreach($rows as $row)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold text-gray-500">{{ $row->label }}</p><p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($row->total) }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($total ? $row->total / $total * 100 : 0, 1) }}% of filtered records</p></div>
    @endforeach
</div>
<section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-gray-100 px-5 py-4">
        <div>
            <h2 class="font-bold text-gray-800">Promotion outcome ratio</h2>
            <p class="mt-1 text-xs text-gray-500">Compare the percentage share of each saved promotion outcome.</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="promotionRatioGrade" class="mb-1 block text-xs font-semibold text-gray-600">Grade level</label>
                <select id="promotionRatioGrade" class="min-w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="all">All grade levels</option>
                    @foreach($promotionRatioChart['grades'] as $index => $grade)
                        <option value="{{ $index }}">{{ $grade['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="downloadPromotionRatio" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060] disabled:cursor-not-allowed disabled:opacity-50">Download PNG</button>
        </div>
    </div>
    <div class="p-5">
        <div class="relative h-96 min-w-0">
            <canvas id="promotionRatioChart" role="img" aria-label="Promotion outcome ratios for all grade levels"></canvas>
            <div id="promotionRatioEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-gray-500">No promotion outcomes match this grade level.</div>
        </div>
    </div>
</section>
<section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
        <div>
            <h2 class="font-bold text-gray-800">Previous vs. current promotion rate</h2>
            <p class="mt-1 text-xs text-gray-500">Promoted and completed junior high records as a percentage of all records in each year.</p>
        </div>
        <button type="button" id="downloadPromotionRate" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060] disabled:cursor-not-allowed disabled:opacity-50">Download PNG</button>
    </div>
    <div class="p-5">
        @if($promotionRateComparison['years']->count() < 2)
            <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">A previous school year is not available for comparison.</p>
        @endif
        <div class="relative h-96 min-w-0">
            <canvas id="promotionRateComparisonChart" role="img" aria-label="Previous and current school year promotion rate comparison"></canvas>
            <div id="promotionRateComparisonEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-gray-500">No promotion records are available for these school years.</div>
        </div>
        <p class="mt-3 text-xs text-gray-500">Grade level, enrollment status, and learner search filters apply. The promotion-status filter is excluded so each year keeps a complete rate denominator.</p>
    </div>
</section>
@foreach($matrices as $title => $matrix)
    <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-100 px-5 py-4 font-bold text-gray-800">Outcomes by {{ strtolower($title) }}</h2>
        <div class="max-h-96 overflow-auto"><table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500"><tr><th scope="col" class="px-4 py-3">{{ $title }}</th>@foreach($statuses as $name)<th scope="col" class="px-4 py-3 text-right">{{ $name }}</th>@endforeach<th scope="col" class="px-4 py-3 text-right">Total</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($matrix as $row)
                    <tr><th scope="row" class="whitespace-nowrap px-4 py-3 font-semibold">{{ $row['label'] }}</th>@foreach($statuses as $slug => $name)<td class="px-4 py-3 text-right">{{ number_format($row['counts'][$slug] ?? 0) }}</td>@endforeach<td class="px-4 py-3 text-right font-bold">{{ number_format($row['total']) }}</td></tr>
                @empty
                    <tr><td colspan="{{ count($statuses) + 2 }}" class="px-5 py-6 text-center text-gray-500">No matching promotion records.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endforeach
<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-gray-800">Detailed promotion records</h2><p class="mt-1 text-xs text-gray-500">{{ number_format($total) }} matching records. Downloads include all matching records.</p></div>
    <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500"><tr>@foreach(['Learner / LRN', 'School year', 'Grade', 'Section', 'Enrollment status', 'Promotion status', 'Details'] as $heading)<th scope="col" class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($records as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><p class="font-semibold">{{ $record->last_name }}, {{ $record->first_name }} {{ $record->middle_name }} {{ $record->suffix }}</p><p class="text-xs text-gray-500">{{ $record->lrn ?: 'No LRN' }}</p></td>
                    <td class="px-4 py-3">{{ $record->school_year ?? 'Unspecified' }}</td><td class="px-4 py-3">{{ $record->grade_label ?? 'Unspecified' }}</td><td class="px-4 py-3">{{ $record->section_name ?? 'Unassigned' }}</td><td class="px-4 py-3">{{ $record->enrollment_status ?? 'Unspecified' }}</td>
                    <td class="px-4 py-3 font-semibold text-[#296374]">{{ $record->promotion_status ?? 'Unspecified' }}</td><td class="px-4 py-3"><a href="{{ route(\App\Support\AcademicPortal::routeName('guidance.enrollments.show'), $record->enrollment_ID) }}" class="font-semibold text-[#296374] hover:underline" aria-label="View enrollment for {{ $record->first_name }} {{ $record->last_name }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No promotion records match these filters.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="border-t border-gray-100 px-5 py-4">{{ $records->links() }}</div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.promotionRatioChartData = @json($promotionRatioChart);
    window.promotionRateComparisonData = @json($promotionRateComparison);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('promotionRatioChart');
        var gradeSelect = document.getElementById('promotionRatioGrade');
        var downloadButton = document.getElementById('downloadPromotionRatio');
        var emptyState = document.getElementById('promotionRatioEmpty');
        var payload = window.promotionRatioChartData || {};

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var currentData = payload.overall || { label: 'All grade levels', counts: [] };
        var labels = payload.labels || [];
        var colors = payload.colors || [];

        function totalFor(values) {
            return values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
        }

        var centerTotal = {
            id: 'centerTotal',
            beforeDraw: function (chart) {
                var area = chart.chartArea;
                if (!area) return;
                var values = chart.data.datasets[0].data || [];
                var total = totalFor(values);
                var context = chart.ctx;
                context.save();
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillStyle = '#1f2937';
                context.font = '700 24px system-ui, sans-serif';
                context.fillText(total.toLocaleString(), (area.left + area.right) / 2, (area.top + area.bottom) / 2 - 8);
                context.fillStyle = '#6b7280';
                context.font = '12px system-ui, sans-serif';
                context.fillText('records', (area.left + area.right) / 2, (area.top + area.bottom) / 2 + 16);
                context.restore();
            }
        };

        var ratioChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: currentData.counts,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            plugins: [centerTotal],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                            generateLabels: function (chart) {
                                var values = chart.data.datasets[0].data || [];
                                var total = totalFor(values);
                                return chart.data.labels.map(function (label, index) {
                                    var value = Number(values[index] || 0);
                                    var ratio = total ? (value / total * 100).toFixed(1) : '0.0';
                                    return {
                                        text: label + ' — ' + ratio + '% (' + value.toLocaleString() + ')',
                                        fillStyle: colors[index],
                                        strokeStyle: '#ffffff',
                                        lineWidth: 1,
                                        hidden: !chart.getDataVisibility(index),
                                        index: index
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var total = totalFor(context.dataset.data);
                                var value = Number(context.raw || 0);
                                var ratio = total ? (value / total * 100).toFixed(1) : '0.0';
                                return context.label + ': ' + value.toLocaleString() + ' (' + ratio + '%)';
                            }
                        }
                    }
                }
            }
        });

        function updateChart(selection) {
            currentData = selection === 'all'
                ? payload.overall
                : (payload.grades || [])[Number(selection)];
            currentData = currentData || { label: 'All grade levels', counts: [] };
            var total = totalFor(currentData.counts);
            ratioChart.data.datasets[0].data = currentData.counts;
            ratioChart.update();
            canvas.setAttribute('aria-label', 'Promotion outcome ratios for ' + currentData.label);
            emptyState.classList.toggle('hidden', total > 0);
            emptyState.classList.toggle('flex', total === 0);
            canvas.classList.toggle('invisible', total === 0);
            downloadButton.disabled = total === 0;
        }

        gradeSelect.addEventListener('change', function () {
            updateChart(gradeSelect.value);
        });

        downloadButton.addEventListener('click', function () {
            var padding = 28;
            var headerHeight = 70;
            var width = ratioChart.width;
            var height = ratioChart.height;
            var scale = 2;
            var exportCanvas = document.createElement('canvas');
            exportCanvas.width = (width + padding * 2) * scale;
            exportCanvas.height = (height + headerHeight + padding * 2) * scale;
            var context = exportCanvas.getContext('2d');
            context.scale(scale, scale);
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, exportCanvas.width / scale, exportCanvas.height / scale);
            context.textAlign = 'center';
            context.fillStyle = '#1f2937';
            context.font = '700 20px system-ui, sans-serif';
            context.fillText('Promotion Outcome Ratio', width / 2 + padding, padding + 22);
            context.fillStyle = '#6b7280';
            context.font = '13px system-ui, sans-serif';
            context.fillText(currentData.label, width / 2 + padding, padding + 48);
            context.drawImage(canvas, 0, 0, canvas.width, canvas.height, padding, padding + headerHeight, width, height);
            var link = document.createElement('a');
            var safeGrade = String(currentData.label || 'all-grades').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
            link.download = 'promotion-outcome-ratio-' + safeGrade + '.png';
            link.href = exportCanvas.toDataURL('image/png');
            link.click();
        });

        updateChart('all');
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('promotionRateComparisonChart');
        var downloadButton = document.getElementById('downloadPromotionRate');
        var emptyState = document.getElementById('promotionRateComparisonEmpty');
        var payload = window.promotionRateComparisonData || {};
        var years = payload.years || [];

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var hasRecords = years.some(function (year) { return Number(year.total) > 0; });
        emptyState.classList.toggle('hidden', hasRecords);
        emptyState.classList.toggle('flex', !hasRecords);
        canvas.classList.toggle('invisible', !hasRecords);
        downloadButton.disabled = !hasRecords;

        var valueLabels = {
            id: 'promotionRateValueLabels',
            afterDatasetsDraw: function (chart) {
                var context = chart.ctx;
                context.save();
                context.fillStyle = '#1f2937';
                context.font = '700 13px system-ui, sans-serif';
                context.textAlign = 'center';
                context.textBaseline = 'bottom';
                chart.getDatasetMeta(0).data.forEach(function (bar, index) {
                    context.fillText(Number(years[index].rate).toFixed(1) + '%', bar.x, bar.y - 6);
                });
                context.restore();
            }
        };

        var rateChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: years.map(function (year) { return year.label; }),
                datasets: [{
                    label: 'Promotion rate',
                    data: years.map(function (year) { return year.rate; }),
                    backgroundColor: years.map(function (year, index) { return index === years.length - 1 ? '#059669' : '#2563eb'; }),
                    borderRadius: 7,
                    maxBarThickness: 150
                }]
            },
            plugins: [valueLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 24 } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) { return value + '%'; }
                        },
                        title: {
                            display: true,
                            text: 'Promotion rate'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var year = years[context.dataIndex];
                                return Number(year.rate).toFixed(1) + '% — ' + Number(year.successful).toLocaleString() + ' promoted/completed out of ' + Number(year.total).toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        downloadButton.addEventListener('click', function () {
            var padding = 28;
            var headerHeight = 76;
            var width = rateChart.width;
            var height = rateChart.height;
            var scale = 2;
            var exportCanvas = document.createElement('canvas');
            exportCanvas.width = (width + padding * 2) * scale;
            exportCanvas.height = (height + headerHeight + padding * 2) * scale;
            var context = exportCanvas.getContext('2d');
            context.scale(scale, scale);
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, exportCanvas.width / scale, exportCanvas.height / scale);
            context.textAlign = 'center';
            context.fillStyle = '#1f2937';
            context.font = '700 20px system-ui, sans-serif';
            context.fillText('Previous vs. Current Promotion Rate', width / 2 + padding, padding + 22);
            context.fillStyle = '#6b7280';
            context.font = '13px system-ui, sans-serif';
            context.fillText('Promoted and completed junior high records / all records', width / 2 + padding, padding + 48);
            context.drawImage(canvas, 0, 0, canvas.width, canvas.height, padding, padding + headerHeight, width, height);
            var link = document.createElement('a');
            var currentYear = years.length ? years[years.length - 1].label : 'comparison';
            var safeYear = String(currentYear).replace(/[^a-z0-9-]+/gi, '-').toLowerCase();
            link.download = 'promotion-rate-comparison-' + safeYear + '.png';
            link.href = exportCanvas.toDataURL('image/png');
            link.click();
        });
    });
</script>
@endsection
