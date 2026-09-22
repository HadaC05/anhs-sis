@php
    $label = $label ?? 'Ungraded';
    $classes = match ($label) {
        'Submitted' => 'bg-violet-100 text-violet-700',
        'Approved' => 'bg-emerald-100 text-emerald-700',
        'Released' => 'bg-slate-200 text-slate-700',
        'Rejected' => 'bg-rose-100 text-rose-700',
        'Draft' => 'bg-amber-100 text-amber-700',
        'In progress' => 'bg-sky-100 text-sky-700',
        'No subjects' => 'bg-gray-100 text-gray-500',
        default => 'bg-gray-100 text-gray-500',
    };
@endphp
<span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $classes }}">{{ $label }}</span>
