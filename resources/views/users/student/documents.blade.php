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
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div id="student-documents-client-error" hidden class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
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
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">Documents</h1>
                </div>
                <p class="text-sm text-gray-500">{{ $uploadedRequiredCount }}/{{ $requiredCount }} required documents uploaded</p>
            </div>
        </div>

        <div class="space-y-4 p-4 md:hidden">
            @foreach ($documentTypes as $docType => $docInfo)
                @php
                    $uploadedDoc = $uploadedDocuments->get($docType);
                    $status = $uploadedDoc?->status ?? 'not_uploaded';
                    $statusLabel = $status === 'not_uploaded' ? 'Not uploaded' : ($uploadedDoc?->status_label ?: ucfirst($status));
                    $statusClass = match ($status) {
                        'verified' => 'bg-emerald-50 text-emerald-800',
                        'returned', 'rejected' => 'bg-red-50 text-red-700',
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
                <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm" data-document-type="{{ $docType }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-gray-800">{{ $docInfo['title'] }}</h2>
                            <p class="mt-1 text-xs leading-5 text-gray-500">{{ $docInfo['desc'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold {{ $docInfo['required'] ? 'text-red-600' : 'text-gray-500' }}">{{ $docInfo['required'] ? 'Required' : 'Optional' }}</span>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm">
                        <span class="text-gray-500">Status</span>
                        <span data-document-status class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                        <span class="text-gray-500">Uploaded</span>
                        <span data-document-date class="text-right text-gray-700">{{ $uploadedDoc?->date_uploaded?->format('M d, Y h:i A') ?? '—' }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                        <span class="text-gray-500">File</span>
                        <span data-document-filename class="max-w-[70%] truncate text-right text-gray-700" title="{{ $uploadedDoc?->displayFilename() }}">{{ $uploadedDoc?->displayFilename() ?? '—' }}</span>
                    </div>
                    <p data-document-remarks class="mt-3 rounded-md px-3 py-2 text-xs {{ $uploadedDoc?->isReturned() ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-600' }}">{{ $remarks }}</p>
                    @unless ($uploadedDoc?->isVerified())
                        <div class="mt-4">
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600" for="document-{{ $docType }}">Choose file</label>
                            <input id="document-{{ $docType }}" form="student-documents-form" type="file" name="documents[{{ $docType }}]" accept=".pdf,.jpg,.jpeg,.png" class="student-document-file {{ $fieldClass }}">
                        </div>
                    @endunless
                    <div class="mt-4 flex flex-wrap gap-2" data-document-actions data-document-layout="mobile">
                        @if ($uploadedDoc?->file_path)
                            <a href="{{ route('student.documents.view', $uploadedDoc) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">View document</a>
                            @unless ($uploadedDoc->isVerified())
                                <form action="{{ route('student.documents.delete', $uploadedDoc) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50" onclick="return confirm('Are you sure you want to delete this document?')">Delete</button>
                                </form>
                            @endunless
                        @endif
                        @unless ($uploadedDoc?->isVerified())
                            <button form="student-documents-form" type="submit" data-upload-document="{{ $docType }}" class="inline-flex items-center justify-center rounded-md bg-[#296374] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#214e5c]">{{ $uploadedDoc ? 'Replace document' : 'Upload document' }}</button>
                        @endunless
                    </div>
                </article>
            @endforeach
        </div>

        <div class="hidden overflow-x-auto md:block">
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
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}" data-document-type="{{ $docType }}">
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
                                <span data-document-status class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td data-document-date class="border border-gray-200 px-3 py-2.5 align-top whitespace-nowrap text-gray-700">
                                {{ $uploadedDoc?->date_uploaded?->format('M d, Y h:i A') ?? '—' }}
                            </td>
                            <td data-document-remarks class="border border-gray-200 px-3 py-2.5 align-top text-xs {{ $uploadedDoc?->isReturned() ? 'text-red-700' : 'text-gray-600' }}">
                                {{ $remarks }}
                            </td>
                            <td class="border border-gray-200 px-3 py-2.5 align-top">
                                <p data-document-filename class="mb-2 max-w-[14rem] truncate text-xs text-gray-600" title="{{ $uploadedDoc?->displayFilename() }}">{{ $uploadedDoc?->displayFilename() ?? 'No file uploaded' }}</p>
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
                                <div class="flex items-center justify-center gap-1" data-document-actions data-document-layout="desktop">
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
                                            data-upload-document="{{ $docType }}"
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

        <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500">Each file must be a PDF, JPG, or PNG and 15MB or smaller.</p>
                @if ($canUploadAny)
                    <button
                        id="student-documents-submit-all"
                        form="student-documents-form"
                        type="submit"
                        class="inline-flex w-full shrink-0 items-center justify-center rounded-md px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90 sm:w-auto"
                        style="background-color: #296374;"
                    >
                        Submit All
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div id="studentDocumentsSuccessToast" role="status" aria-live="polite" class="fixed right-4 top-24 z-[120] flex w-[calc(100%-2rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl sm:right-5">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ session('success') }}</span>
        <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" data-dismiss-documents-toast aria-label="Close notification">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
@endif

<script>
    (function () {
        const maxBytes = {{ \App\Http\Requests\Student\StoreStudentDocumentsRequest::MAX_FILE_SIZE_KILOBYTES * 1024 }};
        const maxMegabytes = {{ \App\Http\Requests\Student\StoreStudentDocumentsRequest::MAX_FILE_SIZE_MEGABYTES }};
        const form = document.getElementById('student-documents-form');
        const errorBox = document.getElementById('student-documents-client-error');
        const errorText = document.getElementById('student-documents-client-error-text');
        const successToast = document.getElementById('studentDocumentsSuccessToast');
        let successToastTimer;
        const dismissSuccessToast = () => document.getElementById('studentDocumentsSuccessToast')?.remove();
        const showSuccessToast = (message) => {
            let toast = document.getElementById('studentDocumentsSuccessToast');

            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'studentDocumentsSuccessToast';
                toast.setAttribute('role', 'status');
                toast.setAttribute('aria-live', 'polite');
                toast.className = 'fixed right-4 top-24 z-[120] flex w-[calc(100%-2rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl sm:right-5';
                toast.innerHTML = '<svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span></span><button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" aria-label="Close notification"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>';
                toast.querySelector('button')?.addEventListener('click', dismissSuccessToast);
                document.body.appendChild(toast);
            }

            toast.querySelector('span').textContent = message;
            window.clearTimeout(successToastTimer);
            successToastTimer = window.setTimeout(dismissSuccessToast, 4000);
        };

        successToast?.querySelector('[data-dismiss-documents-toast]')?.addEventListener('click', dismissSuccessToast);
        if (successToast) {
            successToastTimer = window.setTimeout(dismissSuccessToast, 4000);
        }

        if (! form) {
            return;
        }

        const showError = (message) => {
            if (errorBox && errorText) {
                errorText.textContent = message;
                errorBox.hidden = false;
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        const hideError = () => {
            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.hidden = true;
            }
        };

        const fileIsTooLarge = (file) => file && file.size > maxBytes;

        const updateDocumentDetails = (documentType, documentDetails) => {
            document.querySelectorAll(`[data-document-type="${documentType}"]`).forEach((container) => {
                const status = container.querySelector('[data-document-status]');
                const date = container.querySelector('[data-document-date]');
                const remarks = container.querySelector('[data-document-remarks]');
                const filename = container.querySelector('[data-document-filename]');

                if (status) {
                    status.textContent = documentDetails.status_label;
                    status.classList.remove('bg-emerald-50', 'text-emerald-800', 'bg-red-50', 'text-red-700', 'bg-amber-50', 'text-amber-800', 'bg-gray-100', 'text-gray-600');
                    status.classList.add('bg-amber-50', 'text-amber-800');
                }
                if (date) {
                    date.textContent = documentDetails.date_uploaded || '—';
                }
                if (remarks) {
                    remarks.textContent = documentDetails.remarks;
                    remarks.classList.remove('bg-red-50', 'text-red-700');
                    remarks.classList.add('bg-gray-50', 'text-gray-600');
                }
                if (filename) {
                    filename.textContent = documentDetails.filename || '—';
                    filename.title = documentDetails.filename || '';
                }
            });
        };

        const updateDocumentActions = (documentType, documentDetails) => {
            const csrfToken = form.querySelector('[name="_token"]').value;

            document.querySelectorAll(`[data-document-type="${documentType}"] [data-document-actions]`).forEach((actions) => {
                const actionClass = 'inline-flex rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]';
                const deleteClass = 'inline-flex rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600';

                actions.innerHTML = `<a href="${documentDetails.view_url}" target="_blank" rel="noopener noreferrer" class="${actionClass}" title="View Document" aria-label="View Document"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></a><form action="${documentDetails.delete_url}" method="POST"><input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="${deleteClass}" title="Delete" aria-label="Delete" onclick="return confirm('Are you sure you want to delete this document?')"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a1.995 1.995 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></form><button form="student-documents-form" type="submit" data-upload-document="${documentType}" class="${actionClass}" title="Replace Document" aria-label="Replace Document"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></button>`;
            });
        };

        const uploadSingleDocument = async (documentType, submitter) => {
            const input = Array.from(document.querySelectorAll('.student-document-file'))
                .find((field) => field.name === `documents[${documentType}]` && field.files && field.files.length > 0);

            if (!input) {
                showError('Choose a file before uploading this document.');
                return;
            }

            if (fileIsTooLarge(input.files[0])) {
                showError('Each document must be ' + maxMegabytes + 'MB or smaller.');
                return;
            }

            const uploadData = new FormData();
            uploadData.append('_token', form.querySelector('[name="_token"]').value);
            uploadData.append('doc_type', documentType);
            uploadData.append('document', input.files[0]);
            submitter.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: uploadData,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json();

                if (!response.ok) {
                    showError(result.message || 'Unable to upload this document.');
                    return;
                }

                input.value = '';
                hideError();
                if (result.document) {
                    updateDocumentDetails(documentType, result.document);
                    updateDocumentActions(documentType, result.document);
                }
                showSuccessToast(result.message || 'Document uploaded successfully.');
            } catch (error) {
                showError('Unable to upload this document. Please try again.');
            } finally {
                submitter.disabled = false;
            }
        };

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
            const selectedDocumentType = event.submitter?.dataset.uploadDocument;
            const fileInputs = Array.from(document.querySelectorAll('.student-document-file'));

            if (selectedDocumentType) {
                event.preventDefault();
                uploadSingleDocument(selectedDocumentType, event.submitter);
                return;
            }

            const oversized = fileInputs
                .some((input) => fileIsTooLarge(input.files && input.files[0]));

            if (oversized) {
                event.preventDefault();
                showError('Each document must be ' + maxMegabytes + 'MB or smaller.');
            }
        });
    })();
</script>
@endsection
