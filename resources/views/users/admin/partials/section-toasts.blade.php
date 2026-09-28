@php
    $sectionNotices = [];
    foreach (['success' => 'success', 'status' => 'success', 'error' => 'error', 'warning' => 'warning'] as $key => $type) {
        if (session($key)) $sectionNotices[] = ['type' => $type, 'message' => session($key)];
    }
    foreach ($errors->getBags() as $bag) {
        foreach (array_unique($bag->all()) as $message) $sectionNotices[] = ['type' => 'error', 'message' => $message];
    }
    if ($latestImport && in_array($latestImport->status, ['completed', 'failed'], true)) {
        $result = $latestImport->result ?? [];
        $message = $latestImport->status === 'failed'
            ? ($latestImport->failure_message ?: 'Student import failed. Please try again.')
            : 'Student import complete. Enrollments added: '.($result['createdEnrollments'] ?? 0).'. Already enrolled here: '.($result['existingEnrollments'] ?? 0).'. Skipped: '.($result['skippedStudents'] ?? 0).'.';
        $sectionNotices[] = [
            'type' => $latestImport->status === 'failed' ? 'error' : (($result['failedEnrollments'] ?? false) ? 'warning' : 'success'),
            'message' => $message,
            'key' => 'section-import-'.$latestImport->id.'-'.$latestImport->status,
        ];
    }
@endphp
@if($sectionNotices)
<div id="sectionToasts" class="fixed right-4 top-4 flex max-h-[calc(100dvh-2rem)] flex-col gap-3 overflow-y-auto" style="z-index:10010;width:min(24rem,calc(100vw - 2rem))">
    @foreach($sectionNotices as $notice)
        <div role="{{ $notice['type'] === 'success' ? 'status' : 'alert' }}" data-section-toast data-key="{{ $notice['key'] ?? '' }}" class="flex items-start gap-3 rounded-xl border bg-white p-4 text-sm shadow-xl {{ $notice['type'] === 'success' ? 'border-emerald-200 text-emerald-800' : ($notice['type'] === 'warning' ? 'border-amber-200 text-amber-800' : 'border-red-200 text-red-800') }}">
            <div class="min-w-0 flex-1 break-words"><p class="font-bold">{{ ucfirst($notice['type']) }}</p><p class="mt-1">{{ $notice['message'] }}</p></div>
            <button type="button" aria-label="Close notification" class="rounded px-1 text-xl leading-none">&times;</button>
        </div>
    @endforeach
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('sectionToasts');
    document.body.appendChild(container);
    container.querySelectorAll('[data-section-toast]').forEach(toast => {
        const key = toast.dataset.key;
        try { if (key && sessionStorage.getItem(key)) { toast.remove(); return; } } catch (_) {}
        let timer;
        const dismiss = () => {
            clearTimeout(timer);
            try { if (key) sessionStorage.setItem(key, '1'); } catch (_) {}
            toast.remove();
            if (!container.children.length) container.remove();
        };
        const pause = () => clearTimeout(timer);
        const resume = () => { pause(); if (!toast.matches(':hover') && !toast.contains(document.activeElement)) timer = setTimeout(dismiss, 10000); };
        toast.querySelector('button').addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', resume);
        resume();
    });
});
</script>
@endif
