@php($copyOpen = $errors->getBag('copySections')->any())
<div id="copySectionsModal" role="dialog" aria-modal="true" aria-labelledby="copySectionsTitle" data-open="{{ $copyOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[100] {{ $copyOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="flex max-h-[calc(100dvh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-4 bg-[#296374] px-6 py-4 text-white">
            <h2 id="copySectionsTitle" class="text-xl font-bold">Copy Sections</h2>
            <button type="button" onclick="closeCopySectionsModal()" aria-label="Close copy sections" class="rounded-lg px-3 py-1 hover:bg-white/10">&times;</button>
        </div>
        <form id="copySectionsForm" method="POST" action="{{ route($managementRoutePrefix.'section-config.copy') }}" class="overflow-y-auto p-6 space-y-4">
            @csrf
            <p class="text-sm text-gray-600">Copy section names, grades, curricula, clusters, rooms and capacities into another school year. Active and archived source sections are included; new sections will be active.</p>

            <div>
                <label for="copy_grade_level" class="mb-1 block text-sm font-semibold text-gray-700">Grade level</label>
                <select id="copy_grade_level" name="copy_grade_level" required class="{{ $fieldClass }} border-gray-200">
                    <option value="all">All grade levels</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level['value'] }}" @selected(old('copy_grade_level') === $level['value'])>{{ preg_replace('/\D+/', '', $level['label']) }}</option>
                    @endforeach
                </select>
            </div>
            @foreach (['source' => 'Source school year', 'target' => 'Destination school year'] as $key => $label)
                <div>
                    <label for="copy_{{ $key }}_year" class="mb-1 block text-sm font-semibold text-gray-700">{{ $label }}</label>
                    <select id="copy_{{ $key }}_year" name="{{ $key }}_SY_ID" required class="{{ $fieldClass }} border-gray-200">
                        <option value="">Select school year</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->SY_ID }}" @selected((string) old($key.'_SY_ID') === (string) $year->SY_ID)>{{ $year->school_year }}{{ $year->status ? ' (Active)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div>
                <label for="copy_adviser_mode" class="mb-1 block text-sm font-semibold text-gray-700">Advisers</label>
                <select id="copy_adviser_mode" name="adviser_mode" required class="{{ $fieldClass }} border-gray-200">
                    <option value="empty" @selected(old('adviser_mode', 'empty') === 'empty')>Leave advisers empty</option>
                    <option value="keep" @selected(old('adviser_mode') === 'keep')>Copy the same advisers</option>
                </select>
            </div>
            <p class="text-sm text-gray-500">Existing section names in the destination year will be skipped. Student enrollments and subject assignments are not copied. This does not change the active school year.</p>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeCopySectionsModal()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white">Copy Sections</button>
            </div>
        </form>
    </div>
</div>
<script>
    const copySectionsDialog = document.getElementById('copySectionsModal');
    document.body.appendChild(copySectionsDialog);
    let copySectionsOverflow = document.body.style.overflow;

    function openCopySectionsModal() {
        copySectionsOverflow = document.body.style.overflow;
        copySectionsDialog.classList.replace('hidden', 'flex');
        copySectionsDialog.dataset.open = 'true';
        document.body.style.overflow = 'hidden';
        document.getElementById('copy_grade_level').focus();
    }

    function closeCopySectionsModal() {
        copySectionsDialog.classList.replace('flex', 'hidden');
        copySectionsDialog.dataset.open = 'false';
        document.body.style.overflow = copySectionsOverflow;
        document.getElementById('copySectionsTrigger').focus();
    }

    copySectionsDialog.addEventListener('click', (event) => {
        if (event.target === copySectionsDialog) closeCopySectionsModal();
    });
    document.addEventListener('keydown', (event) => {
        if (copySectionsDialog.dataset.open !== 'true') return;
        if (event.key === 'Escape') closeCopySectionsModal();
        if (event.key === 'Tab') {
            const fields = Array.from(copySectionsDialog.querySelectorAll('button, select')).filter(field => !field.disabled);
            const first = fields[0];
            const last = fields[fields.length - 1];
            if (event.shiftKey && (document.activeElement === first || !copySectionsDialog.contains(document.activeElement))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !copySectionsDialog.contains(document.activeElement))) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    const copySourceYear = document.getElementById('copy_source_year');
    const copyTargetYear = document.getElementById('copy_target_year');
    function validateCopyYears() {
        copyTargetYear.setCustomValidity(copySourceYear.value && copySourceYear.value === copyTargetYear.value
            ? 'Choose a destination school year different from the source.' : '');
    }
    copySourceYear.addEventListener('change', validateCopyYears);
    copyTargetYear.addEventListener('change', validateCopyYears);
    validateCopyYears();
    document.getElementById('copySectionsForm').addEventListener('submit', function () {
        const submit = this.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Copying...';
    });
    if (copySectionsDialog.dataset.open === 'true') openCopySectionsModal();
</script>
