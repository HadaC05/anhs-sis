@extends('users.teacher.layout')
@section('title', 'Teacher Remarks')
@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'observed-values'])
@php $activePeriod = collect($periods)->firstWhere('key', $editablePeriodKey); @endphp
@push('toasts')
    <x-password-reset-toasts test-prefix="teacher-remarks" />
@endpush
<div id="teacher-remarks" class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 p-5">
        <h2 class="text-lg font-bold text-gray-800">Teacher Remarks</h2>
        <p class="mt-2 text-sm text-gray-500">Record remarks for the active term or review saved remarks across all terms.</p>
    </div>
    <div role="tablist" aria-label="Teacher remarks views" class="flex gap-2 border-b border-gray-200 px-5 pt-4">
        <button type="button" role="tab" id="remarks-input-tab" aria-controls="remarks-input-panel" aria-selected="true" tabindex="0" class="remarks-tab border-b-2 px-4 py-3 text-sm font-semibold">Active Term{{ $activePeriod ? ' - '.$activePeriod['label'] : '' }}</button>
        <button type="button" role="tab" id="remarks-overview-tab" aria-controls="remarks-overview-panel" aria-selected="false" tabindex="-1" class="remarks-tab border-b-2 px-4 py-3 text-sm font-semibold">Overview</button>
    </div>
    <div id="remarks-input-panel" role="tabpanel" aria-labelledby="remarks-input-tab" tabindex="0">
        @if($activePeriod)
            <p class="px-5 py-4 text-sm text-gray-500">These remarks appear on the updated SF9. Maximum 240 characters per learner.</p>
            <form method="POST" action="{{ route('teacher.advisory.comments.store', $section) }}">
                @csrf
                <input type="hidden" name="grading_period" value="{{ $editablePeriodKey }}">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr><th class="p-4 text-left">Learner</th><th class="p-4 text-left">{{ $activePeriod['label'] }} Remarks</th></tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($enrollments as $enrollment)
                                @php $comment = $enrollment->sf9Comments->firstWhere('grading_period', $editablePeriodKey)?->comment; @endphp
                                <tr>
                                    <th scope="row" class="p-4 text-left align-top"><span class="block">{{ $enrollment->student?->last_name }}, {{ $enrollment->student?->first_name }}</span><span class="text-xs font-normal text-gray-500">{{ $enrollment->student?->lrn }}</span></th>
                                    <td class="min-w-64 p-4 align-top">
                                        <label for="comment-{{ $enrollment->enrollment_ID }}" class="sr-only">{{ $enrollment->student?->last_name }}, {{ $enrollment->student?->first_name }} - {{ $activePeriod['label'] }}</label>
                                        <textarea id="comment-{{ $enrollment->enrollment_ID }}" name="comments[{{ $enrollment->enrollment_ID }}]" rows="1" maxlength="240" class="w-full rounded-lg border border-gray-300 p-2 text-sm" placeholder="Enter teacher remarks">{{ old('comments.'.$enrollment->enrollment_ID, $comment) }}</textarea>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="p-6 text-center text-gray-500">No enrolled learners.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($enrollments->isNotEmpty())<div class="flex justify-end border-t border-gray-200 p-4"><button class="rounded-lg bg-[#296374] px-5 py-3 text-sm font-semibold text-white" type="submit">Save Remarks</button></div>@endif
            </form>
        @else
            <p class="p-5 text-sm text-amber-700">No grading term is open. Use Overview to view saved remarks.</p>
        @endif
    </div>
    <div id="remarks-overview-panel" role="tabpanel" aria-labelledby="remarks-overview-tab" tabindex="0" hidden>
        <p class="px-5 py-4 text-sm text-gray-500">Saved remarks for all configured terms. Unsaved changes stay in the Active Term tab until you save them.</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="p-4 text-left">Learner</th>@foreach($periods as $period)<th class="p-4 text-left">{{ $period['label'] }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($enrollments as $enrollment)
                        @php $comments = $enrollment->sf9Comments->keyBy('grading_period'); @endphp
                        <tr>
                            <th scope="row" class="p-4 text-left align-top"><span class="block">{{ $enrollment->student?->last_name }}, {{ $enrollment->student?->first_name }}</span><span class="text-xs font-normal text-gray-500">{{ $enrollment->student?->lrn }}</span></th>
                            @foreach($periods as $period)
                                <td class="min-w-64 p-4 align-top"><p class="whitespace-normal break-words text-gray-600">{{ $comments->get($period['key'])?->comment ?: 'Not recorded' }}</p></td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($periods) + 1 }}" class="p-6 text-center text-gray-500">No enrolled learners.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
    #teacher-remarks .remarks-tab[aria-selected="true"] { border-color: #296374; color: #296374; }
    #teacher-remarks .remarks-tab[aria-selected="false"] { border-color: transparent; color: #64748b; }
    #teacher-remarks [role="tabpanel"][hidden] { display: none; }
</style>
<script>
    (() => {
        const tabs = Array.from(document.querySelectorAll('#teacher-remarks [role="tab"]'));
        function activate(tab) {
            tabs.forEach(item => {
                const selected = item === tab;
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
                document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
            });
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = tabs.length - 1;
                else return;
                event.preventDefault();
                activate(tabs[next]);
                tabs[next].focus();
            });
        });
    })();
</script>
@endsection
