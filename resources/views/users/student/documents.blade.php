@extends('users.student.layout')

@section('title', 'Documents')

@section('content')
@php
    $enrollment = $currentEnrollment ?? null;
    $documentTypes = $documentTypes ?? [];
    $uploadedDocuments = $documents->unique('doc_type')->keyBy('doc_type');
    $requiredTypes = collect($documentTypes)->filter(fn (array $info): bool => $info['required'])->keys();
    $requiredCount = $requiredTypes->count();
    $uploadedRequiredCount = $requiredTypes->filter(fn (string $type): bool => $uploadedDocuments->has($type))->count();
    $canUploadAny = collect($documentTypes)->keys()->contains(fn (string $type): bool => ! $uploadedDocuments->get($type)?->isVerified());
    $fieldClass = 'w-full min-w-[12rem] rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-800 shadow-sm outline-none transition file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-[11px] file:font-semibold file:text-gray-700 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $iconButtonClass = 'inline-flex rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]';
    $iconDeleteClass = 'inline-flex rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600';
@endphp

<div class="space-y-5">
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div id="student-documents-client-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p id="student-documents-client-error-text"></p>
    </div>

    @include('users.student.partials.enrollment-summary', [
        'student' => $student,
        'application' => $application,
        'enrollment' => $enrollment,
        'activeYear' => $activeYear,
    ])

    <form id="student-documents-form" action="{{ route('student.documents.upload') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">Documents</h1>
                    <p class="mt-1 text-sm text-gray-500">Upload your required documents for enrollment verification. Each file must be 15MB or smaller.</p>
                </div>
                <p class="text-sm text-gray-500">{{ $uploadedRequiredCount }}/{{ $requiredCount }} required documents uploaded</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-collapse text-sm text-gray-800">
                <thead>
                    <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                        <th class="border border-gray-200 px-3 py-3">Document</th>
                        <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Requirement</th>
                        <th class="border border-gray-200 px-3 py-3">Status</th>
                        <th class="border border-gray-200 px-3 py-3 whitespace-nowrap">Date Uploaded</th>
                        <th class="border border-gray-200 px-3 py-3">Remarks</th>
                        <th class="border border-gray-200 px-3 py-3">File</th>
                        <th class="border border-gray-200 px-3 py-3 text-center whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documentTypes as $docType => $docInfo)
                        @php
                            $uploadedDoc = $uploadedDocuments->get($docType);
                            $status = $uploadedDoc?->status ?? 'not_uploaded';
                            $statusLabel = $status === 'not_uploaded' ? 'Not uploaded' : ($uploadedDoc?->status_label ?: ucfirst($status));
                            $statusClass = match ($status) {
                                'verified' => 'bg-emerald-50 text-emerald-800',
                                'returned' => 'bg-red-50 text-red-700',
                                'rejected' => 'bg-red-50 text-red-700',
                                'pending' => 'bg-amber-50 text-amber-800',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            $remarks = match (true) {
                                $uploadedDoc?->isVerified() => 'This document is verified and cannot be replaced.',
                                $uploadedDoc?->isReturned() => 'This document was returned'.($uploadedDoc->returnReason ? ': '.$uploadedDoc->returnReason->name : '').'.',
                                ($uploadedDoc?->status ?? '') === 'pending' => 'Awaiting guidance review.',
                                default => '—',
                            };
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="border border-gray-200 px-3 py-2.5 align-top">
                                <p class="font-semibold text-gray-800">{{ $docInfo['title'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $docInfo['desc'] }}</p>
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top whitespace-nowrap">
                                <span class="text-xs font-semibold {{ $docInfo['required'] ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ $docInfo['required'] ? 'Required' : 'Optional' }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top whitespace-nowrap text-gray-700">
                                {{ $uploadedDoc?->date_uploaded?->format('M d, Y h:i A') ?? '—' }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top text-xs {{ $uploadedDoc?->isReturned() ? 'text-red-700' : 'text-gray-600' }}">
                                {{ $remarks }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top">
                                @unless ($uploadedDoc?->isVerified())
                                    <input
                                        form="student-documents-form"
                                        type="file"
                                        name="documents[{{ $docType }}]"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="student-document-file {{ $fieldClass }}"
                                    >
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endunless
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-middle">
                                <div class="flex items-center justify-center gap-1">
                                    @if ($uploadedDoc?->file_path)
                                        <a href="{{ route('student.documents.view', $uploadedDoc) }}" target="_blank" rel="noopener noreferrer" class="{{ $iconButtonClass }}" title="View Document" aria-label="View Document">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        @unless ($uploadedDoc->isVerified())
                                            <form action="{{ route('student.documents.delete', $uploadedDoc) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $iconDeleteClass }}" title="Delete" aria-label="Delete" onclick="return confirm('Are you sure you want to delete this document?')">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endunless
                                    @endif

                                    @unless ($uploadedDoc?->isVerified())
                                        <button
                                            form="student-documents-form"
                                            type="submit"
                                            class="{{ $iconButtonClass }}"
                                            title="{{ $uploadedDoc ? 'Replace Document' : 'Upload Document' }}"
                                            aria-label="{{ $uploadedDoc ? 'Replace Document' : 'Upload Document' }}"
                                        >
                                            @if ($uploadedDoc)
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500">Birth Certificate and Form 137 / SF9 are required. Each file must be a PDF, JPG, or PNG and 15MB or smaller. Choose files for each document, then use Submit All so they are saved together. Unverified documents can be replaced. Verified documents cannot be replaced until the guidance counselor unverifies them.</p>
                @if ($canUploadAny)
                    <button
                        id="student-documents-submit-all"
                        form="student-documents-form"
                        type="submit"
                        class="inline-flex shrink-0 items-center justify-center rounded-md px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90"
                        style="background-color: #296374;"
                    >
                        Submit All
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const maxBytes = {{ \App\Http\Requests\Student\StoreStudentDocumentsRequest::MAX_FILE_SIZE_KILOBYTES * 1024 }};
        const maxMegabytes = {{ \App\Http\Requests\Student\StoreStudentDocumentsRequest::MAX_FILE_SIZE_MEGABYTES }};
        const form = document.getElementById('student-documents-form');
        const errorBox = document.getElementById('student-documents-client-error');
        const errorText = document.getElementById('student-documents-client-error-text');

        if (! form) {
            return;
        }

        const showError = (message) => {
            if (errorBox && errorText) {
                errorText.textContent = message;
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        const hideError = () => {
            if (errorBox) {
                errorBox.classList.add('hidden');
            }
        };

        const fileIsTooLarge = (file) => file && file.size > maxBytes;

        document.querySelectorAll('.student-document-file').forEach((input) => {
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];

                if (fileIsTooLarge(file)) {
                    input.value = '';
                    showError('Each document must be ' + maxMegabytes + 'MB or smaller.');
                    return;
                }

                hideError();
            });
        });

        form.addEventListener('submit', (event) => {
            const oversized = Array.from(document.querySelectorAll('.student-document-file'))
                .some((input) => fileIsTooLarge(input.files && input.files[0]));

            if (oversized) {
                event.preventDefault();
                showError('Each document must be ' + maxMegabytes + 'MB or smaller.');
            }
        });
    })();
</script>
@endsection
