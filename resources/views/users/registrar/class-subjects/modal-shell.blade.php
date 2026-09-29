<dialog id="subjects-modal" aria-labelledby="subjects-modal-title" class="m-auto max-h-[85vh] w-[95vw] max-w-6xl overflow-y-auto rounded-xl bg-white p-0 text-gray-700 shadow-xl backdrop:bg-black/50">
    <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-5 py-4">
        <h2 id="subjects-modal-title" class="text-lg font-bold text-gray-800">Class subjects</h2>
        <button type="button" data-close-subjects class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">Close</button>
    </div>
    <p id="subjects-modal-message" role="status" class="px-5 py-3 text-sm" hidden></p>
    <div id="subjects-modal-content" class="p-2" aria-live="polite"></div>
</dialog>
<script>
(() => {
    const modal = document.getElementById('subjects-modal');
    const content = document.getElementById('subjects-modal-content');
    const message = document.getElementById('subjects-modal-message');
    let sectionUrl;
    let sectionTemplate;
    let loadController;
    let saving = false;
    let previousOverflow;

    function showMessage(text, error = false) {
        message.textContent = text;
        message.hidden = false;
        message.classList.toggle('text-red-700', error);
        message.classList.toggle('text-emerald-700', !error);
    }

    async function loadSubjects(openTermId = null) {
        loadController?.abort();
        loadController = new AbortController();
        const response = await fetch(sectionUrl, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: loadController.signal });
        if (!response.ok || response.redirected) throw new Error('Could not load subjects. Close the modal and try again.');
        const html = await response.text();
        sectionTemplate.innerHTML = html;
        content.replaceChildren(sectionTemplate.content.cloneNode(true));
        const progress = content.querySelector('[data-section-progress]');
        if (progress) {
            const sectionId = progress.dataset.sectionProgress;
            const submitted = document.querySelector(`[data-submitted-grades="${sectionId}"]`);
            const expected = document.querySelector(`[data-expected-grades="${sectionId}"]`);
            if (submitted) submitted.textContent = Number(progress.dataset.submitted).toLocaleString('en-US');
            if (expected) expected.textContent = Number(progress.dataset.expected).toLocaleString('en-US');
        }

        if (openTermId) {
            const row = document.getElementById(openTermId);
            if (row) row.hidden = false;
            const toggle = content.querySelector(`[data-toggle-terms="${openTermId}"]`);
            toggle?.setAttribute('aria-expanded', 'true');
            toggle?.focus();
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-subjects-url]');
        if (!button) return;
        sectionUrl = button.dataset.subjectsUrl;
        sectionTemplate = document.getElementById(button.dataset.subjectsTemplate);
        document.getElementById('subjects-modal-title').textContent = `${button.dataset.sectionName} - Subjects`;
        message.hidden = true;
        content.replaceChildren(sectionTemplate.content.cloneNode(true));
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        modal.showModal();
    });

    modal.querySelector('[data-close-subjects]').addEventListener('click', () => { if (!saving) modal.close(); });
    modal.addEventListener('cancel', (event) => { if (saving) event.preventDefault(); });
    modal.addEventListener('close', () => {
        loadController?.abort();
        document.body.style.overflow = previousOverflow;
    });
    content.addEventListener('click', async (event) => {
        const recordsButton = event.target.closest('[data-view-grades]');
        if (recordsButton) {
            const parentId = recordsButton.dataset.openTerms;
            if (parentId) {
                document.getElementById(parentId).hidden = false;
                content.querySelector(`[data-toggle-terms="${parentId}"]`)?.setAttribute('aria-expanded', 'true');
            }
            const row = document.getElementById(recordsButton.dataset.recordsTarget);
            const target = row.querySelector('[data-records-content]');
            row.hidden = false;
            recordsButton.disabled = true;
            target.textContent = 'Loading grade records...';
            try {
                const response = await fetch(recordsButton.dataset.viewGrades, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok || response.redirected) throw new Error('Could not load grade records. Click View grades to try again.');
                target.innerHTML = await response.text();
            } catch (error) {
                target.textContent = error.message;
            } finally {
                recordsButton.disabled = false;
            }
            return;
        }
        const button = event.target.closest('[data-toggle-terms]');
        if (!button) return;
        const row = document.getElementById(button.dataset.toggleTerms);
        row.hidden = !row.hidden;
        button.setAttribute('aria-expanded', String(!row.hidden));
    });
    content.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-unlock-term]');
        if (!form) return;
        event.preventDefault();
        if (saving || !confirm(`Unlock ${form.dataset.termLabel}? Locked grades will return to draft for teacher editing.`)) return;
        saving = true;
        const buttons = modal.querySelectorAll('button');
        buttons.forEach(button => button.disabled = true);
        message.hidden = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || response.redirected || !data.message) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Unable to unlock this term. Please try again.');
            showMessage(data.message);
            try { await loadSubjects(form.closest('tr[id]').id); }
            catch { showMessage(`${data.message} Reload the page to refresh the statuses.`, true); }
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            saving = false;
            buttons.forEach(button => button.disabled = false);
        }
    });
})();
</script>
