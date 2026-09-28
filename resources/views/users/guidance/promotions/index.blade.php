@extends('users.guidance.layout')

@section('title', 'Promotions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Promotion Confirmation</h1>
</div>

@if (session('status'))
    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $errors->first() }}</div>
@endif

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
        <h2 class="font-bold text-gray-800">Promotion</h2>
        <p class="mt-1 text-sm text-gray-500">Eligible learners have passed every released subject. Confirming creates their next school-year enrollment without assigning a section.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-5 py-3">Learner</th>
                    <th scope="col" class="px-5 py-3">LRN</th>
                    <th scope="col" class="px-5 py-3">Completed enrollment</th>
                    <th scope="col" class="px-5 py-3">Eligibility</th>
                    <th scope="col" class="px-5 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($enrollments as $enrollment)
                    @php
                        $student = $enrollment->student;
                        $name = $student?->application
                            ? trim($student->application->last_name.', '.$student->application->first_name.' '.$student->application->middle_name)
                            : ($student?->name ?? 'Learner');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $student?->lrn ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">
                            <p>{{ $enrollment->getRelation('gradeLevel')?->grade_label ?? '—' }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $enrollment->academicYear?->school_year ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Eligible for Promotion</span>
                        </td>
                        <td class="px-5 py-4">
                            <form method="POST" action="{{ route('guidance.promotions.confirm', $enrollment) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label for="promotion-year-{{ $enrollment->enrollment_ID }}" class="sr-only">Next school year for {{ $name }}</label>
                                <select id="promotion-year-{{ $enrollment->enrollment_ID }}" name="SY_ID" required class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 focus:border-[#296374] focus:ring-[#296374]">
                                    <option value="">Next school year</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->SY_ID }}">{{ $year->school_year }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Confirm</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No learners are currently eligible for promotion.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
