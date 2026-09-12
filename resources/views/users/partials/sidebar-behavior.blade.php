<style>
    .app-sidebar, .app-main {
        transition: width .2s ease, margin-left .2s ease;
    }

    .app-sidebar {
        width: 18rem;
    }

    .app-main {
        margin-left: 18rem;
    }

    .sidebar-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        margin-right: .75rem;
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: .5rem;
        color: #fff;
        transition: background-color .2s ease;
    }

    .sidebar-toggle:hover, .sidebar-toggle:focus-visible {
        background-color: rgba(255, 255, 255, .12);
        outline: none;
    }

    .sidebar-user-label {
        font-size: .625rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #6b7280;
    }

    .sidebar-user-icon {
        display: none !important;
    }

    body.sidebar-collapsed .app-sidebar {
        width: 5.5rem;
    }

    body.sidebar-collapsed .app-main {
        margin-left: 5.5rem;
    }

    body.sidebar-collapsed .sidebar-user-details,
    body.sidebar-collapsed .app-sidebar nav > div > p {
        display: none;
    }

    body.sidebar-collapsed .app-sidebar > div:first-child,
    body.sidebar-collapsed .app-sidebar > div:nth-child(2) {
        padding-left: .75rem;
        padding-right: .75rem;
    }

    body.sidebar-collapsed .sidebar-link {
        justify-content: center;
        gap: 0;
        padding-left: .75rem;
        padding-right: .75rem;
    }

    body.sidebar-collapsed .sidebar-link span,
    body.sidebar-collapsed .app-sidebar .absolute {
        display: none;
    }

    @media (max-width: 640px) {
        .app-sidebar { width: 5.5rem; }
        .app-main { margin-left: 5.5rem; }
        .sidebar-user-details, .app-sidebar nav > div > p { display: none; }
        .app-sidebar > div:first-child, .app-sidebar > div:nth-child(2) { padding-left: .75rem; padding-right: .75rem; }
        .sidebar-link { justify-content: center; gap: 0; padding-left: .75rem; padding-right: .75rem; }
        .sidebar-link span, .app-sidebar .absolute { display: none; }
        body.sidebar-collapsed .app-sidebar { width: 18rem; }
        body.sidebar-collapsed .app-main { margin-left: 18rem; }
        body.sidebar-collapsed .sidebar-user-details, body.sidebar-collapsed .app-sidebar nav > div > p { display: block; }
        body.sidebar-collapsed .sidebar-link { justify-content: flex-start; gap: .75rem; padding-left: 1rem; padding-right: 1rem; }
        body.sidebar-collapsed .sidebar-link span { display: inline; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('sidebar-toggle');
        if (!toggle) return;

        document.querySelectorAll('.app-sidebar .sidebar-link').forEach((link) => {
            const label = link.querySelector('span')?.textContent.trim();
            if (label) {
                link.title = label;
                link.setAttribute('aria-label', label);
            }
        });

        const savedState = localStorage.getItem('sidebar-collapsed');
        if (savedState === 'true') document.body.classList.add('sidebar-collapsed');

        const updateToggle = () => {
            const collapsed = document.body.classList.contains('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        };

        toggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
            updateToggle();
        });

        updateToggle();
    });
</script>
