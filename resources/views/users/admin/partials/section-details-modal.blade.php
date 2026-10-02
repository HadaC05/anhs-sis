@if($selectedSection)
@php
    $closeUrl = route($managementRoutePrefix.'section-config.index', request()->except('section'));
    $importPending = $latestImport && in_array($latestImport->status, ['queued', 'processing'], true);
    $result = $latestImport?->result ?? [];
    $studentGroups = $enrollments->groupBy(function ($enrollment) {
        return match (strtolower(trim((string) $enrollment->student?->sex))) {
            'male' => 'Male',
            'female' => 'Female',
            default => 'Unspecified',
        };
    });
    $studentNumber = 0;
@endphp
<div id="sectionDetailsModal" role="dialog" aria-modal="true" aria-labelledby="sectionDetailsTitle" style="z-index:10000" class="fixed inset-0 flex items-center justify-center bg-slate-900/70 p-4">
    <div class="flex w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" style="max-height:calc(100dvh - 2rem)">
        <div class="flex shrink-0 items-start justify-between gap-4 px-6 py-4 text-white" style="background:#296374">
            <div>
                <h2 id="sectionDetailsTitle" class="text-xl font-bold">{{ $selectedSection->name }} — Students</h2>
                <p class="mt-1 text-sm">{{ preg_replace('/\D+/', '', $selectedSection->grade_level) }} · {{ $selectedSection->academicYear?->school_year }} · {{ $enrollments->count() }} students</p>
                <p class="mt-1 text-sm">Adviser: {{ $selectedSection->adviser?->last_name ? $selectedSection->adviser->last_name.', '.$selectedSection->adviser->first_name : 'Unassigned' }}</p>
            </div>
            <a id="closeSectionDetails" href="{{ $closeUrl }}" class="rounded-lg border border-white/40 px-3 py-2 text-sm font-bold">Close</a>
        </div>
        <div class="overflow-y-auto p-4 sm:p-6">
            @if($importPending)
                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm" aria-live="polite">
                    @if($importPending)
                        <p id="sectionImportProgress">{{ ucfirst($latestImport->status) }}: {{ $latestImport->processed_students }} / {{ $latestImport->total_students ?? '—' }} students processed.</p>
                        <progress id="sectionImportBar" class="mt-2 w-full" value="{{ $latestImport->processed_students }}" max="{{ max(1, $latestImport->total_students ?? 1) }}" aria-label="Students processed"></progress>
                        <p id="sectionImportConnection" class="mt-2"></p>
                    @endif
                </div>
            @endif
            <div class="mb-4 flex flex-wrap justify-end gap-2">
                <button type="button" id="sectionImportTrigger" @disabled($importPending) class="rounded-lg px-4 py-2 text-sm font-bold text-white disabled:opacity-50" style="background:#296374">Import Students</button>
                <a href="{{ route($managementRoutePrefix.'section-config.sf1', $selectedSection) }}" class="inline-flex items-center justify-center rounded-lg border border-[#296374] bg-white px-4 py-2 text-sm font-bold text-[#296374] transition hover:bg-[#eef5f7]" title="Download the complete active class register">Export SF1 Excel</a>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-left text-[13px]">
                    <thead class="bg-gray-100 text-gray-600"><tr><th class="p-3">No.</th><th class="p-3">Student</th><th class="p-3">LRN</th><th class="p-3">Sex</th></tr></thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach(['Male', 'Female', 'Unspecified'] as $group)
                        @if($studentGroups->has($group))
                            <tr style="background:#e8f1f3;color:#245566"><th scope="rowgroup" colspan="4" class="px-3 py-2 text-xs font-bold uppercase tracking-wide">{{ $group === 'Unspecified' ? 'Sex unspecified' : $group.' students' }} ({{ $studentGroups[$group]->count() }})</th></tr>
                            @foreach($studentGroups[$group] as $enrollment)
                        <tr><td class="p-3">{{ ++$studentNumber }}</td><td class="p-3 font-semibold">{{ $enrollment->student?->last_name }}, {{ $enrollment->student?->first_name }} {{ $enrollment->student?->middle_name }} {{ $enrollment->student?->suffix }}</td><td class="whitespace-nowrap p-3">{{ $enrollment->student?->lrn ?? '—' }}</td><td class="p-3">{{ ucfirst($enrollment->student?->sex ?? 'Unspecified') }}</td></tr>
                            @endforeach
                        @endif
                    @endforeach
                    @if($enrollments->isEmpty())
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">No students enrolled in this section yet.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="sectionImportModal" role="dialog" aria-modal="true" aria-labelledby="sectionImportTitle" class="fixed inset-0 hidden items-center justify-center bg-slate-900/70 p-4" style="z-index:10005" data-open="{{ $errors->has('class_list') && ! $importPending ? 'true' : 'false' }}">
    <div class="w-full max-w-md overflow-y-auto rounded-xl bg-white p-5 shadow-2xl" style="max-height:calc(100dvh - 2rem)">
        <div class="mb-4 flex items-center justify-between"><h2 id="sectionImportTitle" class="text-base font-bold text-gray-800">Import Students</h2><button type="button" id="sectionImportClose" class="text-sm font-semibold text-gray-500">Close</button></div>
        <form method="POST" action="{{ route($managementRoutePrefix.'section-config.import', $selectedSection) }}" enctype="multipart/form-data" id="sectionStudentImportForm">
            @csrf
            <input id="sectionClassList" name="class_list" type="file" required accept=".csv,.txt,.xlsx,.pdf" class="sr-only" aria-label="Student class list file">
            <div id="sectionImportDropzone" class="cursor-pointer rounded-xl border-2 border-dashed px-6 py-10 text-center" style="border-color:#4bb87873;background:#f8fcfb">
                <svg class="mx-auto h-11 w-11 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4M4 13h16v4H4z"/></svg>
                <p class="mt-4 text-sm font-medium" style="color:#4bb878">Drag and drop a file here</p>
                <p class="mt-1 text-xs text-gray-400">? OR ?</p>
                <button type="button" id="sectionImportBrowse" class="mt-4 inline-flex h-9 items-center rounded-md px-5 text-xs font-bold text-white shadow-sm" style="background:#4bb878">Browse Files</button>
                <p id="sectionImportFileName" class="mt-4 break-words text-xs font-medium text-gray-600" aria-live="polite">CSV, XLSX, TXT, or text-based SF-1 PDF ? Max 15 MB</p>
            </div>
            <div id="sectionImportSubmitRow" class="mt-4 hidden justify-center gap-2">
                <button type="submit" class="rounded-lg px-4 py-2 text-xs font-bold text-white" style="background:#296374">Import File</button>
                <button type="button" id="sectionImportCancel" class="rounded-lg border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('sectionDetailsModal');
    document.body.appendChild(modal);
    const panel = document.getElementById('sectionImportModal');
    document.body.appendChild(panel);
    const trigger = document.getElementById('sectionImportTrigger');
    const input = document.getElementById('sectionClassList');
    const browse = document.getElementById('sectionImportBrowse');
    const dropzone = document.getElementById('sectionImportDropzone');
    const submitRow = document.getElementById('sectionImportSubmitRow');
    const fileName = document.getElementById('sectionImportFileName');
    function openImport() {
        panel.classList.remove('hidden'); panel.classList.add('flex');
        modal.inert = true;
        browse.focus();
    }
    function closeImport() {
        panel.classList.add('hidden'); panel.classList.remove('flex');
        modal.inert = false;
        trigger.focus();
    }
    function selectFile() {
        const file = input.files[0];
        fileName.textContent = file ? file.name : 'CSV, XLSX, TXT, or text-based SF-1 PDF ? Max 15 MB';
        submitRow.classList.toggle('hidden', !file);
        submitRow.classList.toggle('flex', !!file);
    }
    trigger.addEventListener('click', openImport);
    document.getElementById('sectionImportClose').addEventListener('click', closeImport);
    document.getElementById('sectionImportCancel').addEventListener('click', () => { input.value = ''; selectFile(); closeImport(); });
    panel.addEventListener('click', event => { if (event.target === panel) closeImport(); });
    browse.addEventListener('click', event => { event.stopPropagation(); input.click(); });
    dropzone.addEventListener('click', () => input.click());
    input.addEventListener('change', selectFile);
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, event => {
        event.preventDefault();
        dropzone.style.background = ['dragenter', 'dragover'].includes(type) ? '#edf9f3' : '#f8fcfb';
    }));
    dropzone.addEventListener('drop', event => {
        if (!event.dataTransfer.files.length) return;
        const transfer = new DataTransfer();
        transfer.items.add(event.dataTransfer.files[0]);
        input.files = transfer.files;
        selectFile();
    });
    const close = document.getElementById('closeSectionDetails');
    document.body.style.overflow = 'hidden';
    close.focus();
    if (panel.dataset.open === 'true') openImport();
    modal.addEventListener('click', event => { if (event.target === modal) close.click(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (!panel.classList.contains('hidden')) closeImport(); else close.click();
            return;
        }
        if (event.key !== 'Tab') return;
        const activeModal = panel.classList.contains('hidden') ? modal : panel;
        const items = [...activeModal.querySelectorAll('a, button:not(:disabled), input:not(:disabled)')].filter(item => item.getClientRects().length);
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    document.getElementById('sectionStudentImportForm').addEventListener('submit', event => {
        const button = event.target.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Uploading…';
    });
    @if($importPending)
    async function poll() {
        try {
            const response = await fetch(@json(route($managementRoutePrefix.'section-config.import-status', [$selectedSection, $latestImport])), {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('Unable to check progress.');
            const data = await response.json();
            if (['completed', 'failed'].includes(data.status)) { window.location.reload(); return; }
            document.getElementById('sectionImportProgress').textContent = `${data.status}: ${data.processed_students} / ${data.total_students ?? '—'} students processed · ${data.enrolled_students} enrolled · ${data.skipped_students} skipped`;
            const bar = document.getElementById('sectionImportBar');
            bar.max = Math.max(1, data.total_students ?? 1);
            bar.value = data.processed_students;
            document.getElementById('sectionImportConnection').textContent = data.waiting_for_worker ? 'Waiting for the import worker. Please check the worker if this continues.' : '';
        } catch (error) {
            document.getElementById('sectionImportConnection').textContent = 'Could not check progress. Retrying…';
        }
        setTimeout(poll, 3000);
    }
    setTimeout(poll, 1500);
    @endif
});
</script>
@endif
