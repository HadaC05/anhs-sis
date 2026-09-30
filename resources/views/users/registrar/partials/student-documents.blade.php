<section id="detail-documents" class="registrar-record-section p-6" data-record-section>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-[#296374]">Documents</h2>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $student->documents->count() }} uploaded</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[650px] text-left text-sm">
            <caption class="sr-only">Supporting documents for {{ $fullName }}</caption>
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                @foreach (['Document', 'Uploaded', 'Status', 'Review details', 'Action'] as $heading)
                <th scope="col" class="px-4 py-3">{{ $heading }}</th>
                @endforeach
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($student->documents as $document)
                @php
                    $documentLabel = $document->documentType?->name ?? ucwords(str_replace('_', ' ', $document->doc_type));
                    $badgeClasses = match ($document->status) {
                        'verified' => 'bg-green-100 text-green-800',
                        'returned' => 'bg-red-100 text-red-800',
                        default => 'bg-amber-100 text-amber-800',
                    };
                @endphp
                <tr>
                    <th scope="row" class="px-4 py-4 font-semibold text-[#296374]">{{ $documentLabel }}
                        @if ($document->original_filename)<span class="mt-1 block break-all text-xs font-normal text-gray-500">{{ $document->original_filename }}</span>@endif
                    </th>
                    <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-600">{{ $document->date_uploaded?->format('M d, Y h:i A') ?? '—' }}</td>
                    <td class="px-4 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeClasses }}">{{ $document->documentStatus?->name ?? ucfirst($document->status) }}</span></td>
                    <td class="px-4 py-4 text-xs text-gray-600">
                        @if ($document->status === 'returned' && $document->returnReason)
                        <span class="text-red-700">{{ $document->returnReason->name }}</span>
                        @elseif ($document->date_verified)
                        Reviewed {{ $document->date_verified->format('M d, Y h:i A') }}
                        @else
                        Awaiting review
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        @if ($document->file_path)
                        <a href="{{ route('registrar.students.documents.view', ['student' => $student, 'document' => $document]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg border border-[#296374]/20 px-3 py-2 text-xs font-bold text-[#296374] hover:bg-[#296374]/5" aria-label="View {{ $documentLabel }} (opens in a new tab)">View</a>
                        @else
                        <span class="text-xs text-gray-500">No file attached.</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No supporting documents uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
