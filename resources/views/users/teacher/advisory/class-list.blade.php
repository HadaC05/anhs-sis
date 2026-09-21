@extends('users.teacher.layout')

@section('title', 'Advisory Class List')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'class-list'])

@if (session('status') || session('class_list_import_error') || $errors->has('class_list'))
    <div id="class-list-import-toasts" class="fixed right-5 top-5 z-[120] flex w-full max-w-sm flex-col gap-3" aria-live="polite">
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4 text-sm font-medium text-emerald-800 shadow-xl" role="status">
                <span>{{ session('status') }}</span>
                <button type="button" class="ml-auto text-emerald-700" data-dismiss-toast aria-label="Close notification">&times;</button>
            </div>
        @endif
        @if (session('class_list_import_error') || $errors->has('class_list'))
            <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-white p-4 text-sm font-medium text-red-800 shadow-xl" role="alert">
                <span>{{ session('class_list_import_error') ?? 'Failed to enroll or upload student(s). Please check the class capacity, enrollment status, and uploaded file.' }}</span>
                <button type="button" class="ml-auto text-red-700" data-dismiss-toast aria-label="Close notification">&times;</button>
            </div>
        @endif
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">
    <div class="flex flex-col gap-4 border-b border-gray-100 bg-gray-50/60 px-4 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
        <div class="min-w-0">
            <h2 class="text-base font-bold text-gray-800">Class List</h2>
            <form method="GET" action="{{ route('teacher.advisory.class-list.index', $section) }}" class="mt-3 flex flex-wrap items-center gap-2">
                <label class="sr-only" for="class-list-search">Search learners</label>
                <input id="class-list-search" type="search" name="search" value="{{ $search }}" placeholder="Search learner or LRN" class="h-9 w-52 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                <label class="sr-only" for="class-list-sex">Filter by sex</label>
                <select id="class-list-sex" name="sex" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All sexes</option>
                    <option value="male" @selected($sex === 'male')>Male</option>
                    <option value="female" @selected($sex === 'female')>Female</option>
                    <option value="unspecified" @selected($sex === 'unspecified')>Unspecified</option>
                </select>
                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-[#296374] px-3 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Filter</button>
                @if ($search !== '' || $sex !== '')
                    <a href="{{ route('teacher.advisory.class-list.index', $section) }}" class="inline-flex h-9 items-center rounded-lg px-2 text-xs font-semibold text-gray-500 hover:text-[#296374]">Clear</a>
                @endif
            </form>
        </div>
        <button type="button" id="import-students-trigger" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#296374] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-[#1f4e5c]">Import Students</button>
    </div>
    <div id="student-import-panel" class="{{ $errors->has('class_list') ? 'flex' : 'hidden' }} fixed inset-0 z-[100] items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between"><h2 class="text-base font-bold text-gray-800">Import Students</h2><button type="button" id="close-student-import" class="text-sm font-semibold text-gray-500 transition hover:text-gray-800">Close</button></div>
        <form method="POST" action="{{ route('teacher.advisory.class-list.import', $section) }}" enctype="multipart/form-data" id="student-import-form">
            @csrf
            <input id="class_list" name="class_list" type="file" accept=".csv,.txt,.xlsx,.pdf" required class="sr-only">
            <div id="student-import-dropzone" class="cursor-pointer rounded-xl border-2 border-dashed border-[#4bb878]/45 bg-[#f8fcfb] px-6 py-10 text-center transition hover:border-[#4bb878] hover:bg-[#f1faf6]">
                <svg class="mx-auto h-11 w-11 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 16V4m0 0L8 8m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 13h16v4H4z"></path></svg>
                <p class="mt-4 text-sm font-medium text-[#4bb878]">Drag and drop a file here</p>
                <p class="mt-1 text-xs text-gray-400">– OR –</p>
                <button type="button" id="browse-student-import" class="mt-4 inline-flex h-9 items-center rounded-md bg-[#4bb878] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#3aa468]">Browse Files</button>
                <p id="student-import-file-name" class="mt-4 text-xs font-medium text-gray-600">CSV, XLSX, TXT, or text-based SF-1 PDF · Max 15 MB</p>
            </div>
            @error('class_list')<p class="mt-2 text-center text-xs font-medium text-red-600">{{ $message }}</p>@enderror
            <div id="student-import-submit-row" class="mt-4 hidden justify-center gap-2"><button type="submit" class="inline-flex h-9 items-center rounded-lg bg-[#296374] px-4 text-xs font-bold text-white transition hover:bg-[#1f4e5c]">Import File</button><button type="button" id="cancel-student-import" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 hover:bg-gray-50">Cancel</button></div>
        </form>
        </div>
    </div>
    <div class="overflow-x-auto px-4 py-4 md:px-6"><form id="class-list-forms" method="POST" action="{{ route('teacher.advisory.sf9', $section) }}" target="_blank">@csrf<table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500"><tr><th class="w-12 px-4 py-3 text-center"><input type="checkbox" id="class-list-select-all" class="h-4 w-4 rounded border-gray-300 text-[#296374]"></th><th class="px-4 py-3">No.</th><th class="px-4 py-3">Learner</th><th class="px-4 py-3">LRN</th><th class="px-4 py-3">Forms</th></tr><tr class="bg-white"><td colspan="4" class="border-y border-gray-200 px-4 py-3 normal-case"><p class="text-xs text-gray-500">Select learners for bulk preview.</p></td><td class="border-y border-gray-200 px-4 py-3"><div class="flex gap-2"><button type="submit" class="inline-flex h-8 items-center rounded-lg bg-[#296374] px-3 text-xs font-bold text-white hover:bg-[#1f4e5c]">SF9</button><button type="submit" formaction="{{ route('teacher.advisory.sf10', $section) }}" class="inline-flex h-8 items-center rounded-lg bg-slate-600 px-3 text-xs font-bold text-white hover:bg-slate-700">SF10</button></div></td></tr></thead><tbody class="divide-y divide-gray-100">
        @php
            $currentSexGroup = null;
        @endphp
        @forelse($enrollments as $index => $enrollment)
            @php
                $student = $enrollment->student;
                $application = $student?->application;
                $name = $application ? trim($application->last_name.', '.$application->first_name.' '.$application->middle_name) : ($student?->name ?? 'N/A');
                $sexGroup = match (strtolower((string) $student?->sex)) {
                    'male' => 'Male learners',
                    'female' => 'Female learners',
                    default => 'Sex unspecified',
                };
            @endphp
            @if ($currentSexGroup !== $sexGroup)
                @php
                    $currentSexGroup = $sexGroup;
                @endphp
                <tr class="bg-[#296374]/10"><td colspan="5" class="border-y border-[#296374]/20 px-4 py-2 text-xs font-bold uppercase tracking-widest text-[#296374]">{{ $sexGroup }}</td></tr>
            @endif
            <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-center"><input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->enrollment_ID }}" class="class-list-checkbox h-4 w-4 rounded border-gray-300 text-[#296374]"></td><td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td><td class="px-4 py-3 font-semibold uppercase text-gray-800">{{ $name }}</td><td class="px-4 py-3 text-gray-600">{{ $student?->lrn ?? 'N/A' }}</td><td class="px-4 py-3"><div class="flex gap-2"><a href="{{ route('teacher.advisory.students.profile', ['section' => $section, 'enrollment' => $enrollment, 'search' => $search, 'sex' => $sex]) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-gray-700 hover:underline">Profile</a><a href="{{ route('teacher.advisory.sf9', ['section' => $section, 'enrollment_ID' => $enrollment->enrollment_ID]) }}" target="_blank" class="text-xs font-semibold text-[#296374] hover:underline">SF9</a><a href="{{ route('teacher.advisory.sf10', ['section' => $section, 'enrollment_ID' => $enrollment->enrollment_ID]) }}" target="_blank" class="text-xs font-semibold text-slate-600 hover:underline">SF10</a></div></td></tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No active learners are assigned to this advisory class.</td></tr>
        @endforelse
    </tbody></table></form></div>
</div>

<script>
    (function () {
        var trigger = document.getElementById('import-students-trigger');
        var panel = document.getElementById('student-import-panel');
        var dropzone = document.getElementById('student-import-dropzone');
        var input = document.getElementById('class_list');
        var browse = document.getElementById('browse-student-import');
        var fileName = document.getElementById('student-import-file-name');
        var submitRow = document.getElementById('student-import-submit-row');
        var cancel = document.getElementById('cancel-student-import');
        var close = document.getElementById('close-student-import');

        function showSelectedFile(file) {
            if (!file) return;
            fileName.textContent = file.name;
            submitRow.classList.remove('hidden');
            submitRow.classList.add('flex');
        }

        trigger.addEventListener('click', function () {
            panel.classList.remove('hidden');
            panel.classList.add('flex');
        });

        browse.addEventListener('click', function (event) {
            event.stopPropagation();
            input.click();
        });

        dropzone.addEventListener('click', function () {
            input.click();
        });

        input.addEventListener('change', function () {
            showSelectedFile(input.files[0]);
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.add('border-[#4bb878]', 'bg-[#edf9f3]');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.remove('border-[#4bb878]', 'bg-[#edf9f3]');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (!event.dataTransfer.files.length) return;
            input.files = event.dataTransfer.files;
            showSelectedFile(input.files[0]);
        });

        cancel.addEventListener('click', function () {
            input.value = '';
            panel.classList.add('hidden');
            panel.classList.remove('flex');
            submitRow.classList.add('hidden');
            submitRow.classList.remove('flex');
            fileName.textContent = 'CSV, XLSX, TXT, or text-based SF-1 PDF · Max 15 MB';
        });
        close.addEventListener('click', function () {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
        });

        var selectAll = document.getElementById('class-list-select-all');
        var formsPreview = document.getElementById('class-list-forms');
        var checkboxes = Array.from(document.querySelectorAll('.class-list-checkbox'));
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
            });
        }
        if (formsPreview) {
            formsPreview.addEventListener('submit', function (event) {
                if (!checkboxes.some(function (checkbox) { return checkbox.checked; })) {
                    event.preventDefault();
                    alert('Select at least one learner to preview forms.');
                }
            });
        }
    })();

    document.querySelectorAll('[data-dismiss-toast]').forEach(function (button) {
        button.addEventListener('click', function () { button.parentElement.remove(); });
    });
    setTimeout(function () { document.getElementById('class-list-import-toasts')?.remove(); }, 6000);
</script>
@endsection
