@extends('users.student.layout')

@section('title', 'SHS Progression')

@section('content')
    @php
        $currentGrade = $currentEnrollment?->getRelation('gradeLevel')?->grade_label;
        $currentSemester = $currentEnrollment?->gradingSemester?->label;
        $targetSemesterNumber = $targetSemester === \App\Models\GradingSemester::FIRST ? '1' : '2';
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#296374]">Senior High School</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                @if ($completed)
                    Senior High School Completed
                @elseif ($canComplete)
                    Complete Grade 12
                @elseif ($targetGradeLabel)
                    {{ $targetGradeLabel }} Semester {{ $targetSemesterNumber }} Enrollment
                @else
                    Senior High School Progression
                @endif
            </h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Advance one semester at a time after the school confirms that your current semester is complete.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold">The request could not be processed.</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if ($completed)
            <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                <div class="bg-emerald-50 px-5 py-6 sm:px-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-emerald-950">Completed Senior High School</h2>
                            <p class="mt-1 text-sm text-emerald-800">Your Grade 12 completion is recorded in your permanent enrollment history.</p>
                        </div>
                    </div>
                </div>
            </section>
        @elseif ($canComplete)
            <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-slate-900">Your Grade 12 requirements are complete</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Confirm below to mark your record as having completed Grade 12 and Senior High School.</p>
                <form method="POST" action="{{ route('student.second-semester-enrollment.store') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                        Confirm Grade 12 Completion
                    </button>
                </form>
            </section>
        @elseif ($eligible)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid gap-4 border-b border-slate-100 pb-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">School Year</p><p class="mt-1 font-semibold text-slate-900">{{ $targetYear?->school_year }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grade & Semester</p><p class="mt-1 font-semibold text-slate-900">{{ $targetGradeLabel }} · {{ \App\Support\SecondSemesterEnrollment::semesterLabel($targetSemester) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Track</p><p class="mt-1 font-semibold text-slate-900">{{ $track?->name ?? '—' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cluster</p><p class="mt-1 font-semibold text-slate-900">{{ $currentEnrollment?->cluster?->name ?? '—' }}</p></div>
                </div>

                <form method="POST" action="{{ route('student.second-semester-enrollment.store') }}" class="mt-6" id="semester-two-enrollment-form">
                    @csrf
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Choose your electives</h2>
                            <p class="mt-1 text-sm text-slate-600">Select exactly {{ $requiredElectiveCount === 2 ? 'two different electives' : 'one elective' }} for {{ $track?->name }}.</p>
                        </div>
                        <p id="elective-count" class="mt-2 text-sm font-semibold text-[#296374] sm:mt-0">0 of {{ $requiredElectiveCount }} selected</p>
                    </div>

                    <div class="mt-5 space-y-5">
                        @foreach ($electives->groupBy(fn ($subject) => $subject->cluster?->name ?: 'Other electives') as $clusterName => $clusterElectives)
                            <fieldset>
                                <legend class="mb-2 text-sm font-bold text-slate-700">{{ $clusterName }}</legend>
                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach ($clusterElectives as $elective)
                                        @php($checked = in_array((string) $elective->subject_ID, array_map('strval', old('elective_ids', [])), true))
                                        <label class="elective-card flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-[#296374]/50 hover:bg-[#296374]/5">
                                            <input type="checkbox" name="elective_ids[]" value="{{ $elective->subject_ID }}" class="elective-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-[#296374] focus:ring-[#296374]" @checked($checked)>
                                            <span><span class="block text-xs font-bold uppercase tracking-wide text-[#296374]">{{ $elective->code }}</span><span class="mt-0.5 block text-sm font-semibold text-slate-900">{{ $elective->title }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-500">Your completed semester grades remain unchanged in your enrollment history.</p>
                        <button type="submit" id="submit-enrollment" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-[#296374] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#214f5d] disabled:cursor-not-allowed disabled:opacity-50">
                            Confirm Semester {{ $targetSemesterNumber }} Enrollment
                        </button>
                    </div>
                </form>
            </section>
        @else
            <section class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900">Enrollment is not available yet</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $reason }}</p>
                        @if ($currentEnrollment)
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Current: {{ $currentGrade }} · {{ $currentSemester }} · {{ $currentEnrollment->academicYear?->school_year }}</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>

    @if (session('status'))
        <div id="success-toast" class="fixed right-4 top-24 z-[100] flex max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-xl" role="status">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</div>
            <div class="pt-0.5"><p class="font-bold text-slate-900">Request successful</p><p class="mt-0.5">{{ session('status') }}</p></div>
            <button type="button" class="ml-2 text-slate-400 hover:text-slate-700" onclick="this.closest('#success-toast').remove()" aria-label="Dismiss notification">×</button>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = [...document.querySelectorAll('.elective-checkbox')];
            const counter = document.getElementById('elective-count');
            const submit = document.getElementById('submit-enrollment');
            const required = {{ (int) $requiredElectiveCount }};
            if (!checkboxes.length || !counter || !submit) return;

            const updateSelection = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
                checkboxes.forEach((checkbox) => checkbox.disabled = !checkbox.checked && selected >= required);
                counter.textContent = `${selected} of ${required} selected`;
                submit.disabled = selected !== required;
            };
            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
            updateSelection();
        });
    </script>
@endpush
