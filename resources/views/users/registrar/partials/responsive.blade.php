<style>
    .registrar-layout { --registrar-header-height: 5rem; }
    .registrar-topbar { height: var(--registrar-header-height); }
    .registrar-topbar-inner { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; height: 100%; padding-inline: clamp(1rem, 2vw, 2rem); }
    .registrar-brand { display: flex; align-items: center; flex: 1; min-width: 0; }
    .registrar-brand .sidebar-toggle { flex-shrink: 0; width: 2.75rem; height: 2.75rem; }
    .registrar-brand-logo { display: block; width: auto; height: 3rem; max-width: calc(100% - 3.5rem); object-fit: contain; object-position: left center; }
    .registrar-topbar-actions { display: flex; align-items: center; flex-shrink: 0; gap: clamp(.5rem, 1.5vw, 1.5rem); }
    .registrar-topbar-actions > * { flex-shrink: 0; }
    .registrar-topbar-actions [data-test="notification-bell"],
    .registrar-topbar-actions [data-test="staff-profile-menu"] { width: 2.75rem; height: 2.75rem; }
    .registrar-shell { padding-top: var(--registrar-header-height); }
    .registrar-layout .app-sidebar,
    .registrar-layout #sidebar-backdrop { top: var(--registrar-header-height); }
    .registrar-layout .app-sidebar { overscroll-behavior-y: contain; }
    .registrar-topbar [data-test="notification-dropdown"],
    .registrar-topbar [data-test="staff-profile-dropdown"] { max-height: calc(100vh - var(--registrar-header-height) - 1rem); max-height: calc(100dvh - var(--registrar-header-height) - 1rem); overflow-y: auto; overscroll-behavior-y: contain; overflow-wrap: anywhere; }
    .registrar-topbar [data-test="notification-dropdown"] { display: flex; flex-direction: column; }
    .registrar-topbar [data-test="notification-dropdown"] > div:first-child { flex-shrink: 0; }
    .registrar-topbar [data-test="notification-dropdown"] > div:last-child { min-height: 0; overscroll-behavior-y: contain; }
    @media (max-width: 639px) {
        .registrar-layout { --registrar-header-height: 4rem; }
        .registrar-topbar-inner { gap: .5rem; padding-inline: .75rem; }
        .registrar-brand .sidebar-toggle { margin-right: .5rem; }
        .registrar-brand-logo { height: 2.5rem; max-width: calc(100% - 3.25rem); }
        .registrar-topbar-actions { gap: .25rem; }
        .registrar-topbar-actions > * { position: static; }
        .registrar-topbar [data-test="notification-dropdown"],
        .registrar-topbar [data-test="staff-profile-dropdown"] { position: fixed; top: var(--registrar-header-height); right: .75rem; width: min(24rem, calc(100% - 1.5rem)); max-width: calc(100% - 1.5rem); margin-top: .5rem; }
        .registrar-content { padding-block: 1.5rem; }
        .registrar-content [role="dialog"] { overflow-y: auto; }
    }
    .registrar-content { container-type: inline-size; overflow-wrap: anywhere; }
    .registrar-content .grid > *,
    .registrar-content .flex > div { min-width: 0; }
    .registrar-content select,
    .registrar-content input { min-width: 0; max-width: 100%; }
    .registrar-content .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
    .registrar-content .registrar-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); }
    .registrar-content .registrar-filters > * { min-width: 0; width: 100%; }
    .registrar-filters > a, .registrar-filters > button { justify-content: center; }
    .registrar-content .registrar-filters details > div { width: min(18rem, 100%); }

    @container (max-width: 1050px) {
        .registrar-content [class~="xl:grid-cols-4"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .registrar-content [class~="xl:grid-cols-5"] { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .registrar-content [class~="xl:grid-cols-2"],
        .registrar-content [class~="xl:grid-cols-[minmax(280px,420px)_1fr]"] { grid-template-columns: minmax(0, 1fr); }
    }
    @container (max-width: 700px) {
        .registrar-content [class~="xl:grid-cols-5"],
        .registrar-content [class~="xl:grid-cols-3"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .registrar-content [class~="lg:grid-cols-2"],
        .registrar-content [class~="lg:grid-cols-3"],
        .registrar-content [class~="md:grid-cols-2"] { grid-template-columns: minmax(0, 1fr); }
        .registrar-content [class~="lg:col-span-2"] { grid-column: auto; }
        .registrar-content [class~="sm:flex-row"],
        .registrar-content [class~="lg:flex-row"],
        .registrar-content [class~="xl:flex-row"] { flex-direction: column; align-items: stretch; }
        .registrar-content form.flex { flex-wrap: wrap; }
        .registrar-content form.flex select { width: 100%; }
    }
    @container (max-width: 420px) {
        .registrar-content [class~="xl:grid-cols-4"],
        .registrar-content [class~="xl:grid-cols-5"],
        .registrar-content [class~="xl:grid-cols-3"],
        .registrar-content [class~="sm:grid-cols-2"],
        .registrar-content [class~="sm:grid-cols-3"] { grid-template-columns: minmax(0, 1fr); }
        .registrar-content .p-6 { padding: 1rem; }
        .registrar-content .tab-btn { flex: 1; padding: .75rem; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.registrar-layout .app-sidebar');
        const syncNavigation = () => {
            sidebar.inert = window.matchMedia('(max-width: 1023px)').matches
                && !document.body.classList.contains('sidebar-mobile-open');
        };
        new MutationObserver(syncNavigation).observe(document.body, { attributes: true, attributeFilter: ['class'] });
        window.addEventListener('resize', syncNavigation);
        document.addEventListener('keydown', (event) => {
            const profileMenu = document.querySelector('.registrar-topbar details');
            if (event.key === 'Escape' && profileMenu?.open) {
                profileMenu.open = false;
                profileMenu.querySelector('summary').focus();
            }
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-mobile-open')) {
                document.getElementById('sidebar-backdrop')?.click();
                document.getElementById('sidebar-toggle').focus();
            }
        });
        document.addEventListener('click', (event) => {
            const profileMenu = document.querySelector('.registrar-topbar details');
            if (profileMenu?.open && !profileMenu.contains(event.target)) profileMenu.open = false;
        });
        document.querySelectorAll('.registrar-content .overflow-x-auto').forEach((region) => {
            region.tabIndex = 0;
        });
        syncNavigation();
    });
</script>
