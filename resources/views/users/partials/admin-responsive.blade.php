<style>
    .admin-shell .app-main { min-width: 0; }
    .admin-shell .admin-content { width: 100%; }
    .admin-shell .admin-content :where(.grid, .flex) > * { min-width: 0; }
    .admin-shell .admin-content :where(h1, h2, h3, p, label) { overflow-wrap: anywhere; }
    .admin-shell .admin-content .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
    .admin-shell .admin-content :where(input, select, textarea) { max-width: 100%; }
    .admin-shell [data-test="staff-profile-dropdown"] { max-width: calc(100vw - 2rem); }
    .admin-shell :where(button, a, summary):focus-visible { outline: 2px solid #296374; outline-offset: 3px; }
    .admin-shell header :where(button, a, summary):focus-visible { outline-color: white; }
    .admin-shell dialog { max-height: calc(100dvh - 2rem); }
    .admin-shell div[role="dialog"] { overflow-y: auto; overscroll-behavior: contain; padding: 1rem; }
    .admin-shell div[role="dialog"] > div {
        flex-shrink: 0; max-height: calc(100dvh - 2rem); overflow-y: auto; margin-block: auto;
    }
    @media (max-width: 639px) {
        .admin-shell .admin-content { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .admin-shell .admin-content :where(input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea) { font-size: 1rem; min-height: 2.75rem; }
        .admin-shell .admin-content :where(button, summary), .admin-shell .admin-content table a { min-height: 2.75rem; }
        .admin-shell .admin-content form[method="GET" i] {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem;
        }
        .admin-shell .admin-content form[method="GET" i] > div { grid-column: 1 / -1; min-width: 0; }
        .admin-shell .admin-content form[method="GET" i] :where(input:not([type="hidden"]), select) {
            width: 100%; min-width: 0; min-height: 2.75rem; font-size: 1rem;
        }
        .admin-shell .admin-content form[method="GET" i] > :where(button, a) { min-height: 2.75rem; justify-content: center; }
        .admin-shell div[role="dialog"] :where(input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea) { font-size: 1rem; }
        .admin-shell div[role="dialog"] :where(button, select, input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"])) { min-height: 2.75rem; }
        .admin-shell div[role="dialog"] :where(.px-6) { padding-left: 1rem; padding-right: 1rem; }
        .admin-shell div[role="dialog"] .justify-end { flex-wrap: wrap; }
        .admin-shell #curriculumSuccessToast { left: 1rem; right: 1rem; width: auto; max-width: none; }
    }
    @media (max-width: 379px) {
        .admin-shell .admin-content form[method="GET" i] { grid-template-columns: minmax(0, 1fr); }
        .admin-shell div[role="dialog"] .grid-cols-2 { grid-template-columns: minmax(0, 1fr); }
    }
    @media (prefers-reduced-motion: reduce) {
        .admin-shell .app-sidebar, .admin-shell .app-main { transition: none; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const mobile = window.matchMedia('(max-width: 1023px)');
    const syncAccessibility = () => { sidebar.inert = mobile.matches && !document.body.classList.contains('sidebar-mobile-open'); };
    new MutationObserver(syncAccessibility).observe(document.body, {attributes: true, attributeFilter: ['class']});
    mobile.addEventListener('change', syncAccessibility);
    syncAccessibility();
    document.addEventListener('keydown', event => {
        if (!mobile.matches || !document.body.classList.contains('sidebar-mobile-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            toggle.click();
            toggle.focus();
        } else if (event.key === 'Tab') {
            const links = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'));
            const focusable = [toggle, ...links];
            const index = focusable.indexOf(document.activeElement);
            if (event.shiftKey && index <= 0) { event.preventDefault(); focusable.at(-1).focus(); }
            else if (!event.shiftKey && (index === focusable.length - 1 || index === -1)) { event.preventDefault(); toggle.focus(); }
        }
    });
    document.querySelectorAll('.admin-content .overflow-x-auto').forEach(region => {
        if (!region.querySelector('table')) return;
        region.tabIndex = 0;
        region.setAttribute('role', 'region');
        region.setAttribute('aria-label', 'Scrollable data table');
    });
});
</script>
