@extends('users.guidance.layout')
@section('title', 'Promotion Reports')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div><h1 class="text-2xl font-bold text-gray-800">Promotion Reports</h1><p class="mt-1 text-sm text-gray-600">Review saved promotion outcomes and learner records.</p></div>
    <div class="flex flex-wrap gap-2">
        @foreach(['summary' => 'Download summary CSV', 'records' => 'Download records CSV'] as $download => $label)
            <a href="{{ route('guidance.reports.promotion', array_merge($filters, ['download' => $download])) }}" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#205060]">{{ $label }}</a>
        @endforeach
    </div>
</div>
<form method="GET" action="{{ route('guidance.reports.promotion') }}" class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
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
    <div class="mt-4 flex items-center gap-4"><button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Apply filters</button><a href="{{ route('guidance.reports.promotion') }}" class="text-sm text-gray-600 hover:underline">Reset</a></div>
</form>
<div class="mb-6 rounded-xl border border-[#296374]/20 bg-white p-4 text-sm text-gray-600">
    <p class="font-semibold text-[#296374]">{{ $reportContext[0] }} &middot; Generated {{ $generatedAt }}</p>
    <p class="mt-2">This report uses saved promotion statuses and counts enrollment records. All enrollment statuses are included unless filtered. Select all school years to compare outcomes across years; the same learner may appear in multiple years.</p>
    <p class="mt-2">Eligible learners have not yet been promoted. Conditional promotion does not permit advancement. Grade 12 learners marked eligible have completed Grade 12.</p>
    <a href="{{ route('guidance.promotions.index', ['academic_year_id' => $filters['academic_year_id'], 'eligibility' => 'all']) }}" class="mt-2 inline-block font-semibold text-[#296374] hover:underline">Open promotion evaluation and confirmation</a>
</div>
<div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold text-gray-500">Total records</p><p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($total) }}</p></div>
    @foreach($rows as $row)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold text-gray-500">{{ $row->label }}</p><p class="mt-2 text-3xl font-bold text-[#296374]">{{ number_format($row->total) }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($total ? $row->total / $total * 100 : 0, 1) }}% of filtered records</p></div>
    @endforeach
</div>
<section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3"><h2 class="font-bold text-gray-800">Promotion outcomes</h2><a href="{{ route('guidance.reports.promotion', array_merge($filters, ['download' => 'chart'])) }}" class="rounded-lg border border-[#296374]/30 px-3 py-2 text-xs font-semibold text-[#296374]">Download SVG</a></div>
    <div class="overflow-x-auto"><div class="min-w-[640px]">@include('users.guidance.reports.partials.enrollment-chart', ['title' => 'Promotion outcomes', 'reportName' => 'Promotion report'])</div></div>
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
                    <td class="px-4 py-3 font-semibold text-[#296374]">{{ $record->promotion_status ?? 'Unspecified' }}</td><td class="px-4 py-3"><a href="{{ route('guidance.enrollments.show', $record->enrollment_ID) }}" class="font-semibold text-[#296374] hover:underline" aria-label="View enrollment for {{ $record->first_name }} {{ $record->last_name }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No promotion records match these filters.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="border-t border-gray-100 px-5 py-4">{{ $records->links() }}</div>
</section>
@endsection
