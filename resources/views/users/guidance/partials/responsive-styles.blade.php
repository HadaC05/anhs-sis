<style>
    /* Scope shared dashboard and registration adjustments to the guidance workspace. */
    .guidance-content .grid > *,
    .guidance-content .flex > * {
        min-width: 0;
    }

    .guidance-content :is(input, select, textarea) {
        max-width: 100%;
    }

    .guidance-content .overflow-x-auto {
        max-width: 100%;
        overscroll-behavior-x: contain;
    }

    .guidance-content :is(h1, h2, h3, p) {
        overflow-wrap: anywhere;
    }

    .guidance-ui .guidance-modal {
        padding: 1rem;
        z-index: 100;
    }

    .guidance-ui .guidance-modal > div {
        max-height: calc(100vh - 2rem);
        max-height: calc(100dvh - 2rem);
        overflow-y: auto;
        overscroll-behavior-y: contain;
        margin-inline: auto;
    }

    .guidance-ui .guidance-section-grid {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr));
    }

    .guidance-content .xl\:grid-cols-5 {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr));
    }

    .guidance-content .sm\:grid-cols-5 {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 8rem), 1fr));
    }

    /* Keep dense forms usable when a desktop sidebar consumes part of the viewport. */
    @media (min-width: 768px) and (max-width: 1279px) {
        .guidance-content :is(.md\:grid-cols-4, .md\:grid-cols-5, .lg\:grid-cols-3, .lg\:grid-cols-4) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .guidance-content :is(.md\:grid-cols-4, .md\:grid-cols-5) > * {
            grid-column: auto;
        }
    }

    @media (max-width: 1279px) {
        .guidance-ui .guidance-detail-tabs,
        .guidance-ui .enrollment-progress:not(.hidden) {
            display: flex;
            overflow-x: auto;
        }

        .guidance-ui :is(.guidance-detail-step, .enrollment-step) {
            flex: 0 0 auto;
        }
    }

    @media (max-width: 639px) {
        .guidance-ui [data-test="staff-profile-dropdown"] {
            max-width: calc(100vw - 2rem);
        }

        .guidance-ui .guidance-filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        .guidance-ui .guidance-filters > * {
            width: 100%;
            min-width: 0;
        }

        .guidance-ui .guidance-filters :is(button, a, summary) {
            justify-content: center;
        }

        .guidance-content form.flex {
            flex-wrap: wrap;
        }

        .guidance-content :is(.p-6, .p-7) {
            padding: 1rem;
        }

        .guidance-content :is(.px-6, .px-7) {
            padding-inline: 1rem;
        }
    }
</style>
