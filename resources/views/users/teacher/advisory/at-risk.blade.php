@extends('users.teacher.layout')

@section('title', 'At-risk Students')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'at-risk'])

@push('toasts')
    <x-password-reset-toasts test-prefix="advisory-risk" />
@endpush

<section class="overflow-hidden rounded-xl border border-[#296374]/35 bg-white shadow-sm">
    <div class="border-b border-gray-200 bg-gray-50 px-5 py-5">
        <h2 class="text-lg font-bold text-gray-900">At-risk Students <span class="ml-2 rounded-full bg-red-100 px-2.5 py-1 text-sm text-red-800">{{ $rows->count() }}</span></h2>
        <p class="mt-2 text-sm text-gray-600">Learners with at least one recorded subject or MAPEH component grade below 75 in the selected period. Missing grades are not counted. Recorded grades may still be under review.</p>
        <p class="mt-1 text-sm text-gray-600">Notify student sends a private in-app reminder and an email when the student has an email address. Reminders can be sent once per period every 24 hours.</p>
        <form method="GET" action="{{ route('teacher.advisory.at-risk', $section) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div>
                <label for="risk-term" class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Grading period</label>
                <select id="risk-term" name="term" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20">
                    @forelse($periods as $period)
                        <option value="{{ $period['key'] }}" @selected($term === $period['key'])>{{ $period['label'] }}</option>
                    @empty
                        <option value="">No available periods</option>
                    @endforelse
                </select>
            </div>
            <button type="submit" class="h-10 rounded-lg bg-[#296374] px-4 text-sm font-semibold text-white hover:bg-[#1f4e5c]">View students</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Learner / LRN</th><th class="px-5 py-3">Grades needing attention</th><th class="px-5 py-3">Action</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $row['student']->name }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['student']->lrn }}</p></td>
                        <td class="px-5 py-4"><ul class="space-y-1">@foreach($row['grades'] as $grade)<li><span class="text-gray-700">{{ $grade->assignment->curriculumSubject->subject->title }}</span> <span class="font-bold text-red-700">{{ number_format((float) $grade->numeric_grade, 0) }}</span></li>@endforeach</ul></td>
                        <td class="px-5 py-4">
                            <form method="POST" action="{{ route('teacher.advisory.at-risk.notify', [$section, $row['enrollment']]) }}">
                                @csrf
                                <input type="hidden" name="term" value="{{ $term }}">
                                <button type="submit" class="whitespace-nowrap rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1f4e5c]">Notify student</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-12 text-center text-gray-500">No recorded grades below 75 for this period. Students with missing grades are not included.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
