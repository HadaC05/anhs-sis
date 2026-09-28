<style>
    .registrar-content { container-type: inline-size; overflow-wrap: anywhere; }
    .registrar-content .grid > *,
    .registrar-content .flex > div { min-width: 0; }
    .registrar-content select,
    .registrar-content input { min-width: 0; max-width: 100%; }
    .registrar-content .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
    .registrar-layout [data-test="staff-profile-dropdown"] { max-width: calc(100vw - 2rem); overflow-wrap: anywhere; }
    .registrar-content .registrar-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); }
    .registrar-content .registrar-filters > * { min-width: 0; width: 100%; }
    .registrar-filters > a, .registrar-filters > button { justify-content: center; }
    .registrar-content .registrar-filters details > div { width: min(18rem, 100%); }

    @container (max-width: 1050px) {
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
            sidebar.inert = window.matchMedia('(max-width: 767px)').matches
                && !document.body.classList.contains('sidebar-mobile-open');
        };
        new MutationObserver(syncNavigation).observe(document.body, { attributes: true, attributeFilter: ['class'] });
        window.addEventListener('resize', syncNavigation);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-mobile-open')) {
                document.getElementById('sidebar-backdrop')?.click();
                document.getElementById('sidebar-toggle').focus();
            }
        });
        document.querySelectorAll('.registrar-content .overflow-x-auto').forEach((region) => {
            region.tabIndex = 0;
        });
        syncNavigation();
    });
</script>
