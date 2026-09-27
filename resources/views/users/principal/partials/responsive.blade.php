<style>
    /* Keep shared reports responsive without changing other staff layouts. */
    .principal-content { container-type: inline-size; overflow-wrap: anywhere; }
    .principal-content .grid > *,
    .principal-content .flex > div { min-width: 0; }
    .principal-content select { min-width: 0; max-width: 100%; }
    .principal-content .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
    .principal-layout [data-test="staff-profile-dropdown"] { max-width: calc(100vw - 2rem); overflow-wrap: anywhere; }
    .principal-content .principal-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr)); }
    .principal-content .principal-filters > * { min-width: 0; width: 100%; }
    .principal-filters > a, .principal-filters > button { justify-content: center; }
    .principal-content .section-details-dialog { width: calc(100% - 2rem); max-height: 85dvh; overflow-wrap: anywhere; }
    .principal-content .section-details-dialog > div:first-child { z-index: 1; }

    @container (max-width: 1050px) {
        .principal-dashboard [class~="xl:grid-cols-5"],
        .principal-content .proficiency-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .principal-dashboard [class~="xl:grid-cols-2"],
        .principal-dashboard [class~="xl:grid-cols-[minmax(280px,420px)_1fr]"] { grid-template-columns: minmax(0, 1fr); }
    }
    @container (max-width: 700px) {
        .principal-dashboard [class~="xl:grid-cols-5"],
        .principal-content .proficiency-summary,
        .principal-dashboard [class~="sm:grid-cols-5"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .principal-content .proficiency-results,
        .principal-content [class~="xl:grid-cols-12"] { grid-template-columns: minmax(0, 1fr); }
        .principal-content [class~="xl:grid-cols-12"] > * { grid-column: auto; }
        .principal-dashboard [class~="sm:flex-row"] { flex-direction: column; align-items: stretch; }
        .principal-dashboard [class~="lg:flex-row"],
        .principal-dashboard [class~="xl:flex-row"] { flex-direction: column; align-items: stretch; }
        .principal-dashboard form { flex-wrap: wrap; max-width: 100%; }
        .principal-dashboard form select { width: 100%; }
    }
    @container (max-width: 420px) {
        .principal-content [class~="xl:grid-cols-6"] > .whitespace-nowrap { flex-wrap: wrap; white-space: normal; }
        .principal-dashboard [class~="xl:grid-cols-5"],
        .principal-content .proficiency-summary,
        .principal-dashboard [class~="sm:grid-cols-3"] { grid-template-columns: minmax(0, 1fr); }
        .principal-dashboard .p-6 { padding: 1rem; }
    }
    @media (max-width: 639px) {
        .principal-content .section-details-dialog > div:first-child { flex-direction: column; gap: .75rem; padding: 1rem; }
        .principal-content .section-details-dialog > div:first-child > div:last-child { width: 100%; justify-content: space-between; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('principal-sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        const syncNavigation = () => {
            sidebar.inert = window.matchMedia('(max-width: 767px)').matches
                && !document.body.classList.contains('sidebar-mobile-open');
        };
        new MutationObserver(syncNavigation).observe(document.body, { attributes: true, attributeFilter: ['class'] });
        window.addEventListener('resize', syncNavigation);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-mobile-open')) {
                document.getElementById('sidebar-backdrop').click();
                toggle.focus();
            }
        });
        syncNavigation();
    });
</script>
