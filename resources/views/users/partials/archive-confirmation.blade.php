<dialog id="recordActionConfirmation" aria-labelledby="recordActionTitle" aria-describedby="recordActionMessage" class="m-auto w-[calc(100%-2rem)] max-w-md overflow-y-auto rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/60">
    <div class="p-6">
        <h2 id="recordActionTitle" class="text-lg font-bold text-gray-900"></h2>
        <p id="recordActionMessage" class="mt-3 break-words text-sm leading-6 text-gray-600"></p>
    </div>
    <div class="flex flex-wrap justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <button type="button" data-cancel-record-action autofocus class="min-h-11 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
        <button type="button" data-confirm-record-action class="min-h-11 rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white hover:opacity-90"></button>
    </div>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('recordActionConfirmation');
    const confirm = dialog.querySelector('[data-confirm-record-action]');
    let pendingForm = null;
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('form[data-confirm-action]')) return;
        event.preventDefault();
        pendingForm = form;
        const action = form.dataset.confirmAction;
        document.getElementById('recordActionTitle').textContent = action + ' record?';
        document.getElementById('recordActionMessage').textContent = form.dataset.confirmMessage
            || action + ' “' + (form.dataset.confirmName || 'this record') + '”?';
        confirm.textContent = action;
        confirm.disabled = false;
        dialog.showModal();
    });
    dialog.querySelector('[data-cancel-record-action]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { pendingForm = null; });
    confirm.addEventListener('click', () => {
        if (!pendingForm || confirm.disabled) return;
        confirm.disabled = true;
        HTMLFormElement.prototype.submit.call(pendingForm);
    });
})();
</script>
