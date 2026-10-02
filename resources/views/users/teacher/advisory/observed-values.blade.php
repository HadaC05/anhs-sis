@extends('users.teacher.layout')

@section('title', 'Observed Values')

@section('content')
@php
    $periodGroupLabel = \App\Models\GradingTerm::periodGroupLabel($periods);
    $groupedStatements = collect($statements)->groupBy('core_value');
@endphp

@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'observed-values'])

@push('toasts')
<div id="observed-values-toasts" class="fixed right-4 top-24 z-[120] flex flex-col gap-3" style="width: min(28rem, calc(100vw - 2rem));" aria-live="polite"></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('status'))
            window.showObservedToast(@json(session('status')), 'success');
        @endif
        @if($errors->any() || session('error'))
            window.showObservedToast(@json($errors->first() ?: session('error')), 'error');
        @endif
    });
</script>
@endpush

<div class="overflow-hidden rounded-xl border border-[#296374]/35 bg-[#eef5f7] shadow-md shadow-[#296374]/10">

    @if($isObservedLocked)<div class="border-b border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800 lg:px-6">This grading term is closed for teacher input.</div>@endif

    <div class="border-b border-gray-100 px-4 py-4 lg:px-6">
        <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
            @foreach($groupedStatements as $coreValue => $groupStatements)
                <button type="button" data-core-filter="{{ $coreValue }}" class="core-filter rounded-lg border px-4 py-3 text-left text-sm font-bold transition {{ $activeCoreValue === $coreValue ? 'border-[#296374] bg-[#296374] text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-[#296374]/40 hover:bg-[#296374]/5' }}"><span class="block">{{ $coreValue }}</span><span class="mt-0.5 block text-xs font-medium {{ $activeCoreValue === $coreValue ? 'text-white/75' : 'text-gray-400' }}">{{ $groupStatements->count() }} statements</span></button>
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-600">@foreach($markings as $code => $label)<span class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1"><strong class="text-[#296374]">{{ $code }}</strong> — {{ $label }}</span>@endforeach</div>
    </div>

    <form method="POST" action="{{ route('teacher.advisory.observed-values.store', $section) }}" id="observed-values-form">
        @csrf
        <input type="hidden" name="active_core" id="active-core-input" value="{{ $activeCoreValue }}">
        @foreach($groupedStatements as $coreValue => $groupStatements)
            <section data-core-panel="{{ $coreValue }}" class="{{ $activeCoreValue === $coreValue ? '' : 'hidden' }}">
                @if(! $isObservedLocked)
                    <div class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-slate-50 px-4 py-3 lg:px-6" data-bulk-controls data-period="{{ $editablePeriodKey }}">
                        <label class="block min-w-0 flex-1"><span class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Bulk statement</span><select data-bulk-statement class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700"><option value="">Select statement</option>@foreach($groupStatements as $statement)<option value="{{ $statement['key'] }}">{{ $statement['statement'] }}</option>@endforeach</select></label>
                        <label class="block"><span class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Marking</span><select data-bulk-marking class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700"><option value="">Select marking</option>@foreach($markings as $code => $label)<option value="{{ $code }}">{{ $code }} - {{ $label }}</option>@endforeach</select></label>
                        <p class="w-full text-xs text-gray-500">Choose a statement, marking, and learners, then save them as recorded observed values for the current grading term.</p>
                    </div>
                @endif
                <div class="overflow-x-auto"><table class="w-full min-w-[980px] border-collapse border border-gray-300 text-left"><thead class="bg-[#296374] text-xs font-bold uppercase tracking-wider text-white"><tr><th rowspan="2" class="w-12 border border-[#1f4e5c] px-4 py-3 text-center"><label class="inline-flex cursor-pointer items-center" title="Select all learners"><input type="checkbox" data-select-all-core="{{ $coreValue }}" class="h-4 w-4 rounded border-white/50 text-[#296374]" @disabled($isObservedLocked)><span class="sr-only">Select all learners</span></label></th><th rowspan="2" class="min-w-52 border border-[#1f4e5c] px-4 py-3 lg:px-6">Learner</th>@foreach($groupStatements as $statement)<th colspan="{{ count($periods) }}" class="min-w-48 border border-[#1f4e5c] px-4 py-3 text-center normal-case leading-5">{{ $statement['statement'] }}</th>@endforeach</tr><tr>@foreach($groupStatements as $statement)@foreach($periods as $period)<th class="border border-[#1f4e5c] px-3 py-2 text-center">{{ $period['label'] }}</th>@endforeach@endforeach</tr></thead><tbody class="divide-y divide-gray-200">
                    @php $currentSexGroup = null; @endphp
                    @forelse($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $name = $student ? trim($student->last_name.', '.$student->first_name.' '.$student->middle_name) : 'N/A';
                            $studentValues = $observedValues->get($enrollment->enrollment_ID, collect());
                            $sexGroup = match (strtolower((string) $student?->sex)) { 'male' => 'Male learners', 'female' => 'Female learners', default => 'Sex unspecified' };
                        @endphp
                        @if($currentSexGroup !== $sexGroup)
                            @php $currentSexGroup = $sexGroup; @endphp
                            <tr class="bg-[#296374]/10"><td colspan="{{ 2 + ($groupStatements->count() * count($periods)) }}" class="border-y border-[#296374]/20 px-4 py-2 text-xs font-bold uppercase tracking-widest text-[#296374]">{{ $sexGroup }}</td></tr>
                        @endif
                        <tr class="transition hover:bg-slate-50"><td class="border border-gray-200 px-4 py-3 text-center"><input type="checkbox" value="{{ $enrollment->enrollment_ID }}" data-learner-checkbox data-core="{{ $coreValue }}" class="h-4 w-4 rounded border-gray-300 text-[#296374]" @disabled($isObservedLocked)></td><td class="border border-gray-200 px-4 py-3 lg:px-6"><p class="text-sm font-semibold text-gray-800">{{ $name }}</p></td>@foreach($groupStatements as $statement)@php $statementValues = $studentValues->get($statement['key'], collect()); @endphp @foreach($periods as $period)@php $record = $statementValues->get($period['key']); $isPeriodLocked = in_array($period['key'], $lockedPeriodKeys ?? [], true); @endphp<td class="border border-gray-200 px-3 py-3 text-center"><select name="markings[{{ $enrollment->enrollment_ID }}][{{ $statement['key'] }}][{{ $period['key'] }}]" class="w-16 rounded-md border border-gray-300 bg-white px-1 py-1.5 text-xs font-semibold text-gray-700 focus:border-[#296374] focus:outline-none focus:ring-2 focus:ring-[#296374]/20" @disabled($isObservedLocked || $isPeriodLocked)><option value="">—</option>@foreach($markings as $code => $label)<option value="{{ $code }}" @selected($record?->marking === $code)>{{ $code }}</option>@endforeach</select>@if($isPeriodLocked)<p class="mt-1 text-[9px] font-semibold uppercase text-slate-500">Locked</p>@endif</td>@endforeach@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ 2 + ($groupStatements->count() * count($periods)) }}" class="px-6 py-12 text-center text-gray-500">No students found for this advisory section.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @endforeach
        @if($enrollments->isNotEmpty() && ! $isObservedLocked)<div class="teacher-actions flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-4 py-4 lg:px-6"><button type="submit" class="inline-flex h-10 items-center rounded-lg bg-[#296374] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1f4e5c]">Save Observed Values</button></div>@endif
    </form>
</div>

<div id="unsaved-core-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-xl bg-white shadow-2xl"><div class="border-b border-gray-200 px-5 py-4"><h3 class="text-base font-bold text-gray-800">Unsaved progress</h3></div><div class="px-5 py-4 text-sm leading-6 text-gray-600">You have unsaved markings. Save your progress before changing to another core value?</div><div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-4"><button type="button" id="stay-on-core" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700">Stay</button><button type="button" id="discard-core-changes" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700">Discard</button><button type="button" id="save-core-progress" class="h-9 rounded-lg bg-[#296374] px-3 text-sm font-bold text-white">Save Progress</button></div></div></div>

<script>
    (function () {
        var form = document.getElementById('observed-values-form');
        var activeInput = document.getElementById('active-core-input');
        var filters = Array.from(document.querySelectorAll('[data-core-filter]'));
        var panels = Array.from(document.querySelectorAll('[data-core-panel]'));
        var modal = document.getElementById('unsaved-core-modal');
        var pendingCore = null;
        var isDirty = false;

        function activateCore(core) {
            panels.forEach(function (panel) { panel.classList.toggle('hidden', panel.dataset.corePanel !== core); });
            filters.forEach(function (filter) {
                var active = filter.dataset.coreFilter === core;
                filter.className = 'core-filter rounded-lg border px-4 py-3 text-left text-sm font-bold transition ' + (active ? 'border-[#296374] bg-[#296374] text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-[#296374]/40 hover:bg-[#296374]/5');
                filter.querySelector('span:last-child').className = 'mt-0.5 block text-xs font-medium ' + (active ? 'text-white/75' : 'text-gray-400');
            });
            activeInput.value = core;
        }

        form.addEventListener('change', function (event) { if (event.target.matches('select')) isDirty = true; });
        form.addEventListener('submit', function (event) {
            if (!applyBulkMarkings()) { event.preventDefault(); return; }
            if (pendingCore) activeInput.value = pendingCore;
            isDirty = false;
        });
        window.addEventListener('beforeunload', function (event) {
            if (!isDirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
        filters.forEach(function (filter) {
            filter.addEventListener('click', function () {
                var core = filter.dataset.coreFilter;
                if (core === activeInput.value) return;
                if (!isDirty) { activateCore(core); return; }
                pendingCore = core;
                modal.classList.remove('hidden'); modal.classList.add('flex');
            });
        });
        Array.from(document.querySelectorAll('[data-select-all-core]')).forEach(function (selectAll) {
            selectAll.addEventListener('change', function () {
                var core = selectAll.dataset.selectAllCore;
                Array.from(document.querySelectorAll('[data-learner-checkbox]')).forEach(function (checkbox) {
                    if (checkbox.dataset.core === core) checkbox.checked = selectAll.checked;
                });
            });
        });
        Array.from(document.querySelectorAll('[data-learner-checkbox]')).forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var core = checkbox.dataset.core;
                var learners = Array.from(document.querySelectorAll('[data-learner-checkbox]')).filter(function (input) { return input.dataset.core === core; });
                var selectAll = document.querySelector('[data-select-all-core="' + core + '"]');
                if (selectAll) {
                    var selected = learners.filter(function (input) { return input.checked; }).length;
                    selectAll.checked = selected > 0 && selected === learners.length;
                    selectAll.indeterminate = selected > 0 && selected < learners.length;
                }
            });
        });
        window.showObservedToast = function (message, type) {
            var container = document.getElementById('observed-values-toasts');
            var toast = document.createElement('div');
            toast.className = 'flex items-start gap-3 rounded-lg border bg-white p-4 text-sm font-medium shadow-xl ' + (type === 'error' ? 'border-red-200 text-red-800' : 'border-emerald-200 text-emerald-800');
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
            var text = document.createElement('span');
            text.textContent = message;
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'ml-auto shrink-0';
            close.setAttribute('aria-label', 'Close notification');
            close.textContent = '\u00d7';
            close.addEventListener('click', function () { toast.remove(); });
            toast.append(text, close);
            container.appendChild(toast);
            if (type !== 'error') {
                var timer = setTimeout(function () { toast.remove(); }, 8000);
                toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
                toast.addEventListener('focusin', function () { clearTimeout(timer); });
            }
        };

        function applyBulkMarkings() {
            var updates = [];
            for (var panel of panels) {
                var controls = panel.querySelector('[data-bulk-controls]');
                if (!controls) continue;
                var statement = controls.querySelector('[data-bulk-statement]').value;
                var marking = controls.querySelector('[data-bulk-marking]').value;
                if (!statement && !marking) continue;
                var learners = Array.from(panel.querySelectorAll('[data-learner-checkbox]:checked'));
                if (!statement || !marking || !learners.length || !controls.dataset.period) {
                    modal.classList.add('hidden'); modal.classList.remove('flex');
                    pendingCore = null;
                    activateCore(panel.dataset.corePanel);
                    window.showObservedToast('Select a statement, a marking, and at least one learner for an open grading term.', 'error');
                    return false;
                }
                learners.forEach(function (learner) {
                    var name = 'markings[' + learner.value + '][' + statement + '][' + controls.dataset.period + ']';
                    var input = form.elements.namedItem(name);
                    if (input && !input.disabled) updates.push({ input: input, marking: marking });
                });
            }
            updates.forEach(function (update) { update.input.value = update.marking; });
            return true;
        }
        document.getElementById('stay-on-core').addEventListener('click', function () { modal.classList.add('hidden'); modal.classList.remove('flex'); pendingCore = null; });
        document.getElementById('discard-core-changes').addEventListener('click', function () { isDirty = false; activateCore(pendingCore); modal.classList.add('hidden'); modal.classList.remove('flex'); pendingCore = null; });
        document.getElementById('save-core-progress').addEventListener('click', function () { form.requestSubmit(); });
    })();
</script>
@endsection
