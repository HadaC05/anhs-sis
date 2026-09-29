<div class="rounded-lg border border-gray-200 bg-white p-3">
    <h3 class="mb-3 text-sm font-semibold text-gray-800">Submitted grades &mdash; {{ $period['label'] }}</h3>
    <table class="w-full text-left text-xs">
        <thead class="bg-gray-50"><tr>
            @foreach (['LRN', 'Student', 'Grade', 'Status'] as $heading)
            <th scope="col" class="px-3 py-2">{{ $heading }}</th>
            @endforeach
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($grades as $grade)
            @php $student = $grade->studentSubject?->enrollment?->student; @endphp
            <tr>
                <td class="px-3 py-2">{{ $student?->lrn ?? 'N/A' }}</td>
                <td class="px-3 py-2">{{ $student ? trim($student->last_name.', '.$student->first_name.' '.$student->middle_name) : 'Student unavailable' }}</td>
                <td class="px-3 py-2 font-semibold">{{ $grade->numeric_grade !== null ? number_format((float) $grade->numeric_grade, 2) : '-' }}</td>
                <td class="px-3 py-2">{{ $grade->gradeStatus?->name ?? ucfirst($grade->status) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-3 py-4 text-gray-500">No submitted grades for active students in this term.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
