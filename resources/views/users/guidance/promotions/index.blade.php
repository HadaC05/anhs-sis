@extends('users.guidance.layout')

@section('title', 'Promotions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Promotion Confirmation</h1>
    <p class="mt-1 text-sm text-gray-600">Eligible learners have passed every released subject. Confirming creates their next school-year enrollment without assigning a section.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Learner</th><th class="px-5 py-3">Completed enrollment</th><th class="px-5 py-3">Promote to</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($enrollments as $enrollment)
            @php $student = $enrollment->student; $name = $student?->application ? trim($student->application->last_name.', '.$student->application->first_name) : ($student?->name ?? 'Learner'); @endphp
            <tr>
                <td class="px-5 py-4 font-semibold text-gray-800">{{ $name }}<div class="mt-1 text-xs font-normal text-gray-500">{{ $student?->lrn }}</div></td>
                <td class="px-5 py-4 text-gray-600">{{ $enrollment->gradeLevel?->grade_label }} · {{ $enrollment->academicYear?->school_year }}</td>
                <td class="px-5 py-4"><form method="POST" action="{{ route('guidance.promotions.confirm', $enrollment) }}" class="flex gap-2">@csrf<select name="SY_ID" required class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm"> <option value="">Next school year</option>@foreach($academicYears as $year)<option value="{{ $year->SY_ID }}">{{ $year->school_year }}</option>@endforeach</select><button class="rounded-lg bg-[#296374] px-3 py-1.5 text-xs font-bold text-white">Confirm</button></form></td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-5 py-12 text-center text-gray-500">No learners are currently eligible for promotion.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
