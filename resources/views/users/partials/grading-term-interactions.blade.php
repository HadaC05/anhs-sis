<dialog id="closeGradingConfirmation" aria-labelledby="closeGradingTitle" aria-describedby="closeGradingMessage" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/60">
    <div class="p-6">
        <h3 id="closeGradingTitle" class="text-lg font-bold text-gray-900"></h3>
        <p id="closeGradingMessage" class="mt-3 text-sm leading-6 text-gray-600"></p>
    </div>
    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <button type="button" id="cancelCloseGrading" autofocus class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
        <button type="button" id="confirmCloseGrading" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700">Confirm close</button>
    </div>
</dialog>

<script>
(() => {
    const dropdowns = Array.from(document.querySelectorAll('[data-grading-dropdown]')).map(details => ({
        details, trigger: details.querySelector('summary'), menu: details.querySelector('summary + div'),
    }));
    let active = null;

    function positionMenu() {
        if (!active) return;
        const rect = active.trigger.getBoundingClientRect();
        const menu = active.menu;
        menu.style.maxHeight = Math.max(100, window.innerHeight - 24) + 'px';
        const width = menu.offsetWidth;
        const height = menu.offsetHeight;
        const top = rect.bottom + 6 + height <= window.innerHeight - 12
            ? rect.bottom + 6 : Math.max(12, rect.top - height - 6);
        menu.style.top = top + 'px';
        menu.style.left = Math.max(12, Math.min(rect.right - width, window.innerWidth - width - 12)) + 'px';
    }

    function closeDropdown() {
        if (!active) return;
        const previous = active;
        active = null;
        previous.details.open = false;
        previous.trigger.setAttribute('aria-expanded', 'false');
        previous.menu.removeAttribute('style');
        previous.details.appendChild(previous.menu);
    }

    dropdowns.forEach(item => {
        item.trigger.setAttribute('aria-expanded', 'false');
        item.trigger.addEventListener('click', event => {
            event.preventDefault();
            const wasOpen = active === item;
            closeDropdown();
            if (wasOpen) return;
            active = item;
            item.details.open = true;
            item.trigger.setAttribute('aria-expanded', 'true');
            // Move outside scroll containers so the card and table cannot clip it.
            document.body.appendChild(item.menu);
            Object.assign(item.menu.style, {position: 'fixed', zIndex: '200', margin: '0', right: 'auto', overflowY: 'auto'});
            positionMenu();
        });
        item.details.addEventListener('toggle', () => {
            if (!item.details.open && active === item) closeDropdown();
        });
        item.menu.addEventListener('click', event => {
            if (event.target.closest('button, a')) closeDropdown();
        });
    });
    document.addEventListener('click', event => {
        if (active && !active.trigger.contains(event.target) && !active.menu.contains(event.target)) closeDropdown();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && active) {
            const trigger = active.trigger;
            closeDropdown();
            trigger.focus();
        } else if (event.key === 'ArrowDown' && active && document.activeElement === active.trigger) {
            event.preventDefault();
            active.menu.querySelector('button, a')?.focus();
        }
    });
    window.addEventListener('resize', positionMenu);
    document.addEventListener('scroll', positionMenu, true);

    const dialog = document.getElementById('closeGradingConfirmation');
    const confirm = document.getElementById('confirmCloseGrading');
    let pendingForm = null;
    document.querySelectorAll('[data-confirm-close]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            closeDropdown();
            pendingForm = form;
            document.getElementById('closeGradingTitle').textContent = form.dataset.confirmTitle;
            document.getElementById('closeGradingMessage').textContent = form.dataset.confirmClose;
            confirm.disabled = false;
            dialog.showModal();
        });
    });
    document.getElementById('cancelCloseGrading').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { pendingForm = null; });
    confirm.addEventListener('click', () => {
        if (!pendingForm || confirm.disabled) return;
        confirm.disabled = true;
        HTMLFormElement.prototype.submit.call(pendingForm);
    });
})();
</script>
