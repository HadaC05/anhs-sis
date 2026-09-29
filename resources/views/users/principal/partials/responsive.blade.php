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
    .principal-content .curriculum-panels table { overflow-wrap: normal; }
    .principal-content #curriculaTabPanel table { min-width: 48rem; }
    .principal-content #curriculaTabPanel th:first-child { min-width: 12rem; }
    .principal-content #curriculaTabPanel th:nth-child(2) { min-width: 16rem; }
    .principal-content .curriculum-panels td { overflow-wrap: anywhere; }
    .principal-content .curriculum-panels .overflow-x-auto:focus-visible { outline: 2px solid #296374; outline-offset: -2px; }

    @container (max-width: 1050px) {
        .principal-dashboard [class~="xl:grid-cols-5"],
        .principal-content .proficiency-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .principal-dashboard [class~="xl:grid-cols-2"],
        .principal-dashboard [class~="xl:grid-cols-[minmax(280px,420px)_1fr]"] { grid-template-columns: minmax(0, 1fr); }
    }
    @container (max-width: 700px) {
        .principal-content .curriculum-panels form[method="GET"] { display: grid; grid-template-columns: minmax(0, 1fr); }
        .principal-content .curriculum-panels form[method="GET"] > :not([type="hidden"]) { width: 100%; min-width: 0; justify-content: center; }
        .principal-content .curriculum-panels > div > .flex { flex-direction: column; align-items: stretch; }
        .principal-content .curriculum-panels > div > .flex > button { justify-content: center; }
        .principal-content .curriculum-panels :where(button, select, input:not([type="hidden"]), table a) { min-height: 2.75rem; }
        .principal-content .curriculum-panels table :where(button, a) { min-width: 2.75rem; }
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
        .principal-content #curriculumSuccessToast { left: 1rem; right: 1rem; width: auto; max-width: none; }
        .principal-content .curriculum-panels :where(select, input:not([type="hidden"])) { font-size: 1rem; }
        .principal-content .section-details-dialog > div:first-child { flex-direction: column; gap: .75rem; padding: 1rem; }
        .principal-content .section-details-dialog > div:first-child > div:last-child { width: 100%; justify-content: space-between; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.principal-content .curriculum-panels .overflow-x-auto').forEach((region) => {
            if (!region.querySelector('table')) return;
            region.tabIndex = 0;
            region.setAttribute('role', 'region');
            region.setAttribute('aria-label', 'Scrollable curriculum table');
        });
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
