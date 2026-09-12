@extends('users.admin.layout')

@section('title', 'Grading Term Configuration')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $addTermModalOpen = old('_form') === 'add_term' && $errors->any();
    $maxTermsModalOpen = old('_form') === 'max_terms' && $errors->any();
    $editTermModalOpen = old('_form') === 'edit_term' && $errors->any();
    $activeTermKeys = collect($configuredPeriods)->pluck('key')->values();
    $currentEditableKey = collect($openPeriods)->last()['key'] ?? null;
    $atTermLimit = $terms->count() >= $settings->max_terms;
    $currentTermLabel = collect($openPeriods)->last()['label'] ?? 'Term 1';
    $activeTermCount = $terms->filter(fn ($term) => $term->isActive())->count();
    $editingTermId = old('_form') === 'edit_term' ? old('term_id') : null;
    $editFormAction = $editingTermId
        ? route('admin.grading-term-config.update', ['term' => $editingTermId])
        : '#';
    $isJuniorHighTab = $activeTab === 'junior_high';
    $currentSeniorHighLabel = $currentSeniorHighPeriod['label'] ?? 'First Semester · Term 1';
    $currentSeniorHighSemesterLabel = $currentSeniorHighPeriod['semester_label'] ?? 'First Semester';
    $currentSeniorHighTermLabel = $currentSeniorHighPeriod['term_label'] ?? 'Term 1';
    $lockedSeniorHighPeriodKeys = $lockedSeniorHighPeriodKeys ?? [];
@endphp

<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Grading Terms</h1>
        <p class="mt-1 text-sm text-gray-500">Manage junior high terms and senior high semesters and terms.</p>
    </div>
    <div class="relative {{ $isJuniorHighTab ? '' : 'hidden' }}" id="gradingTermSettings">
        <button type="button" id="gradingTermSettingsButton" onclick="toggleGradingTermSettingsMenu()"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-[#296374]/30 hover:text-[#296374]"
            aria-haspopup="true" aria-expanded="false" aria-controls="gradingTermSettingsMenu" title="Term settings">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span class="sr-only">Term settings</span>
        </button>
        <div id="gradingTermSettingsMenu" role="menu" class="absolute right-0 z-20 mt-2 hidden w-56 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
            <button type="button" role="menuitem" onclick="openAddTermModal()"
                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 {{ $atTermLimit ? 'cursor-not-allowed opacity-50' : '' }}"
                @disabled($atTermLimit)>
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add new term
            </button>
            <button type="button" role="menuitem" onclick="openMaxTermsModal()"
                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit maximum terms
            </button>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any() && ! in_array(old('_form'), ['add_term', 'max_terms', 'edit_term'], true))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-6 border-b border-gray-200">
    <nav class="flex gap-1" aria-label="Grading term tabs">
        <a href="{{ route('admin.grading-term-config.index', ['tab' => 'junior_high']) }}"
            class="relative px-4 py-3 text-sm font-bold transition {{ $isJuniorHighTab ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
            Junior High School
            @if ($isJuniorHighTab)
                <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374]"></span>
            @endif
        </a>
        <a href="{{ route('admin.grading-term-config.index', ['tab' => 'senior_high']) }}"
            class="relative px-4 py-3 text-sm font-bold transition {{ ! $isJuniorHighTab ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
            Senior High School
            @if (! $isJuniorHighTab)
                <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374]"></span>
            @endif
        </a>
    </nav>
</div>

@if ($isJuniorHighTab)
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Current Term</p>
        <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $currentTermLabel }}</p>
        <p class="mt-1 text-xs text-gray-500">Teachers enter grades for this term</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Maximum Terms</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($settings->max_terms) }}</p>
        <p class="mt-1 text-xs text-gray-500">Allowed terms for grading</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Active Terms</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $activeTermCount }} / {{ $terms->count() }}</p>
        <p class="mt-1 text-xs text-gray-500">Status follows the maximum terms limit</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Terms</h2>
            <p class="mt-1 text-sm text-gray-500">Set the current grading term from a row. Earlier terms lock automatically.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] border-collapse text-left">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                    <th class="border-r border-gray-200 px-5 py-4">Key</th>
                    <th class="border-r border-gray-200 px-5 py-4">Label</th>
                    <th class="border-r border-gray-200 px-5 py-4">Order</th>
                    <th class="border-r border-gray-200 px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($terms as $term)
                    @php
                        $activeIndex = $activeTermKeys->search($term->key);
                        $termNumber = $activeIndex === false ? null : $activeIndex + 1;
                        $isCurrentTerm = $term->isActive() && $term->key === $currentEditableKey;
                    @endphp
                    <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                        <td class="border-r border-gray-100 px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $term->key }}</p>
                            @if ($isCurrentTerm)
                                <p class="mt-0.5 text-xs font-semibold text-[#296374]">Current grading term</p>
                            @endif
                        </td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $term->label }}</td>
                        <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $term->sort_order }}</td>
                        <td class="border-r border-gray-100 px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $term->isActive() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                {{ $term->status?->name ?? ($term->isActive() ? 'Active' : 'Inactive') }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick='openEditTermModal(@json(["term_ID" => $term->term_ID, "label" => $term->label, "sort_order" => $term->sort_order]))'
                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @if ($term->isActive() && $termNumber !== null && ! $isCurrentTerm)
                                    <form action="{{ route('admin.grading-term-config.open-term.update') }}" method="POST" class="inline"
                                        onsubmit="return confirm('Set {{ $term->label }} as the current grading term? Teachers will enter grades for this term, and earlier terms will be locked.');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="open_terms_count" value="{{ $termNumber }}">
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50">
                                            Set as Active
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">No terms configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Current Semester</p>
        <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $currentSeniorHighSemesterLabel }}</p>
        <p class="mt-1 text-xs text-gray-500">Active senior high semester</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Current Term</p>
        <p class="mt-2 text-2xl font-bold text-[#296374]">{{ $currentSeniorHighTermLabel }}</p>
        <p class="mt-1 text-xs text-gray-500">Teachers enter grades for this term</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Current Period</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ $currentSeniorHighLabel }}</p>
        <p class="mt-1 text-xs text-gray-500">2 semesters, 3 terms each</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-lg font-bold text-gray-900">Active Semester</h2>
            <p class="mt-1 text-sm text-gray-500">Select the semester whose Senior High subjects are currently in session.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[420px] border-collapse text-left">
                <thead><tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600"><th class="border-r border-gray-200 px-5 py-4">Semester</th><th class="border-r border-gray-200 px-5 py-4">Availability</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @foreach ($seniorHighSemesters as $semester)
                        @php $isCurrentSemester = (int) $settings->semester_ID === (int) $semester->semester_ID; @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $semester->label }} @if ($isCurrentSemester)<p class="mt-0.5 text-xs font-semibold text-[#296374]">Active semester</p>@endif</td>
                            <td class="border-r border-gray-100 px-5 py-4"><span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">{{ $semester->status?->name ?? 'Available' }}</span></td>
                            <td class="px-5 py-4 text-right">@if (! $isCurrentSemester)<form action="{{ route('admin.grading-term-config.senior-high.semester.update') }}" method="POST" class="inline" onsubmit="return confirm('Set {{ $semester->label }} as the active Senior High semester?');">@csrf @method('PUT')<input type="hidden" name="semester_ID" value="{{ $semester->semester_ID }}"><button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50">Set Active</button></form>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg shadow-gray-200/70">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-lg font-bold text-gray-900">Active Term</h2>
            <p class="mt-1 text-sm text-gray-500">Select the current term independently of the active semester.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[420px] border-collapse text-left">
                <thead><tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600"><th class="border-r border-gray-200 px-5 py-4">Term</th><th class="border-r border-gray-200 px-5 py-4">Status</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @foreach ($seniorHighTerms as $term)
                        @php $isCurrentTerm = (int) $settings->term_ID === (int) $term->term_ID; @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $term->label }} @if ($isCurrentTerm)<p class="mt-0.5 text-xs font-semibold text-[#296374]">Active term</p>@endif</td>
                            <td class="border-r border-gray-100 px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $term->isActive() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $term->status?->name ?? ($term->isActive() ? 'Active' : 'Inactive') }}</span></td>
                            <td class="px-5 py-4 text-right">@if (! $isCurrentTerm)<form action="{{ route('admin.grading-term-config.senior-high.term.update') }}" method="POST" class="inline" onsubmit="return confirm('Set {{ $term->label }} as the active Senior High term?');">@csrf @method('PUT')<input type="hidden" name="term_ID" value="{{ $term->term_ID }}"><button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50">Set Active</button></form>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endif

<div id="addTermModal" role="dialog" aria-modal="true" aria-labelledby="addTermModalTitle" data-open="{{ $addTermModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $addTermModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-lg overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="addTermModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add new term</h3>
                </div>
                <button type="button" onclick="closeAddTermModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form action="{{ route('admin.grading-term-config.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="add_term">

            <div class="space-y-4 px-6 py-5">
                @if ($addTermModalOpen)
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="term_label" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Term label <span class="text-red-500">*</span></label>
                    <input id="term_label" name="label" type="text" value="{{ old('_form') === 'add_term' ? old('label') : 'Term '.($terms->count() + 1) }}" required maxlength="50"
                        class="{{ $fieldClass }} {{ $addTermModalOpen && $errors->has('label') ? 'border-red-300' : 'border-gray-200' }}">
                    <p class="mt-1 text-xs text-gray-500">This name appears on teacher grade sheets.</p>
                    @error('label')
                        @if (old('_form') === 'add_term')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeAddTermModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;" @disabled($atTermLimit)>
                    Add Term
                </button>
            </div>
        </form>
    </div>
</div>

<div id="maxTermsModal" role="dialog" aria-modal="true" aria-labelledby="maxTermsModalTitle" data-open="{{ $maxTermsModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $maxTermsModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-lg overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="maxTermsModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Edit maximum terms</h3>
                </div>
                <button type="button" onclick="closeMaxTermsModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form action="{{ route('admin.grading-term-config.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="max_terms">

            <div class="space-y-4 px-6 py-5">
                @if ($maxTermsModalOpen)
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="max_terms" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Allowed terms <span class="text-red-500">*</span></label>
                    <input id="max_terms" name="max_terms" type="number" min="2" max="12" value="{{ old('_form') === 'max_terms' ? old('max_terms') : $settings->max_terms }}" required
                        class="{{ $fieldClass }} {{ $maxTermsModalOpen && $errors->has('max_terms') ? 'border-red-300' : 'border-gray-200' }}">
                    <p class="mt-1 text-xs text-gray-500">Minimum: 2. Extra terms stay saved but become inactive when they are beyond this limit.</p>
                    @error('max_terms')
                        @if (old('_form') === 'max_terms')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeMaxTermsModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Limit
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editTermModal" role="dialog" aria-modal="true" aria-labelledby="editTermModalTitle" data-open="{{ $editTermModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $editTermModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4 pt-24">
    <div class="mx-auto w-full max-w-lg overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="editTermModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Edit term</h3>
                </div>
                <button type="button" onclick="closeEditTermModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="editTermForm" action="{{ $editFormAction }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit_term">
            <input type="hidden" id="edit_term_id" name="term_id" value="{{ old('term_id') }}">

            <div class="space-y-4 px-6 py-5">
                @if ($editTermModalOpen)
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="edit_term_label" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Term label <span class="text-red-500">*</span></label>
                    <input id="edit_term_label" name="label" type="text" value="{{ old('_form') === 'edit_term' ? old('label') : '' }}" required maxlength="50"
                        class="{{ $fieldClass }} {{ $editTermModalOpen && $errors->has('label') ? 'border-red-300' : 'border-gray-200' }}">
                    @error('label')
                        @if (old('_form') === 'edit_term')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="edit_term_sort_order" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Order <span class="text-red-500">*</span></label>
                    <input id="edit_term_sort_order" name="sort_order" type="number" min="1" max="99" value="{{ old('_form') === 'edit_term' ? old('sort_order') : '' }}" required
                        class="{{ $fieldClass }} {{ $editTermModalOpen && $errors->has('sort_order') ? 'border-red-300' : 'border-gray-200' }}">
                    <p class="mt-1 text-xs text-gray-500">Lower numbers appear first. Active status follows this order up to the maximum terms limit.</p>
                    @error('sort_order')
                        @if (old('_form') === 'edit_term')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeEditTermModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Term
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeGradingTermSettingsMenu() {
        const menu = document.getElementById('gradingTermSettingsMenu');
        const button = document.getElementById('gradingTermSettingsButton');

        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }

    function toggleGradingTermSettingsMenu() {
        const menu = document.getElementById('gradingTermSettingsMenu');
        const button = document.getElementById('gradingTermSettingsButton');
        const isOpen = !menu.classList.contains('hidden');

        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    function openAddTermModal() {
        if ({{ $atTermLimit ? 'true' : 'false' }}) {
            return;
        }

        closeGradingTermSettingsMenu();
        openModal('addTermModal');
        document.getElementById('term_label').focus();
    }

    function closeAddTermModal() {
        closeModal('addTermModal');
    }

    function openMaxTermsModal() {
        closeGradingTermSettingsMenu();
        openModal('maxTermsModal');
        document.getElementById('max_terms').focus();
    }

    function closeMaxTermsModal() {
        closeModal('maxTermsModal');
    }

    function openEditTermModal(term) {
        const form = document.getElementById('editTermForm');
        const updateRouteTemplate = '{{ route('admin.grading-term-config.update', ['term' => '__TERM__']) }}';

        form.action = updateRouteTemplate.replace('__TERM__', term.term_ID);
        document.getElementById('edit_term_id').value = term.term_ID;
        document.getElementById('edit_term_label').value = term.label || '';
        document.getElementById('edit_term_sort_order').value = term.sort_order || '';
        openModal('editTermModal');
        document.getElementById('edit_term_label').focus();
    }

    function closeEditTermModal() {
        closeModal('editTermModal');
    }

    document.addEventListener('click', function (event) {
        const settings = document.getElementById('gradingTermSettings');
        if (!settings || !settings.contains(event.target)) {
            closeGradingTermSettingsMenu();
        }
    });

    ['addTermModal', 'maxTermsModal', 'editTermModal'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (event) {
            if (event.target === this) {
                closeModal(id);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (document.getElementById('addTermModal').getAttribute('data-open') === 'true') {
            closeAddTermModal();
            return;
        }

        if (document.getElementById('maxTermsModal').getAttribute('data-open') === 'true') {
            closeMaxTermsModal();
            return;
        }

        if (document.getElementById('editTermModal').getAttribute('data-open') === 'true') {
            closeEditTermModal();
            return;
        }

        closeGradingTermSettingsMenu();
    });
</script>
@endsection
