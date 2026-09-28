<style>
    .teacher-layout .app-sidebar { background-color: #fff; }
    .teacher-content { overflow-wrap: anywhere; }
    .teacher-content .grid > *,
    .teacher-content .flex > div { min-width: 0; }
    .teacher-content .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
    .teacher-content table { overflow-wrap: normal; }
    .teacher-content [data-grade-panel] table { min-width: 40rem; }
    .teacher-content select { min-width: 0; max-width: 100%; }
    .teacher-content form[method="GET"] { flex-wrap: wrap; }
    .teacher-layout [role="dialog"] > div {
        max-height: calc(100dvh - 2rem);
        overflow-y: auto;
        overflow-wrap: anywhere;
    }
    .teacher-layout [data-test="teacher-profile-dropdown"] {
        max-width: calc(100vw - 2rem);
        overflow-wrap: anywhere;
    }
    .teacher-content #class-list-import-toasts { max-width: calc(100vw - 2.5rem); }

    @media (max-width: 1279px) {
        .teacher-content [class~="lg:grid-cols-12"] { grid-template-columns: minmax(0, 1fr); }
        .teacher-content [class~="lg:grid-cols-12"] > * { grid-column: auto; }
        .teacher-content [class~="lg:grid-cols-4"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .teacher-content [class~="lg:flex-row"] { flex-direction: column; align-items: stretch; }
        .teacher-content .teacher-advisory-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .teacher-advisory-tabs > a { border-bottom: 1px solid #2963744d; }
        .teacher-advisory-tabs > a:last-child { grid-column: span 2; border-bottom: 0; }
    }
    @media (max-width: 639px) {
        .teacher-layout [data-test="notification-dropdown"],
        .teacher-layout [data-test="teacher-profile-dropdown"] {
            position: fixed;
            top: 5rem;
            right: 1rem;
            width: calc(100vw - 2rem);
            max-height: calc(100dvh - 6rem);
            overflow-y: auto;
        }
        .teacher-content form[method="GET"] { width: 100%; }
        .teacher-content form[method="GET"] > div,
        .teacher-content form[method="GET"] > select,
        .teacher-content form[method="GET"] > input:not([type="hidden"]) { width: 100%; min-width: 0; }
        .teacher-content form[method="GET"] select,
        .teacher-content form[method="GET"] input:not([type="hidden"]) { width: 100%; min-width: 0; }
        .teacher-content .teacher-actions { flex-direction: column; align-items: stretch; }
        .teacher-content .teacher-actions > button { justify-content: center; height: auto; min-height: 2.75rem; }
        .teacher-content .p-6 { padding: 1rem; }
        .teacher-content .px-6 { padding-left: 1rem; padding-right: 1rem; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('teacher-sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        const syncNavigation = () => {
            sidebar.inert = window.matchMedia('(max-width: 1023px)').matches
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
