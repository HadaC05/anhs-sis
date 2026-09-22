<style>
    .app-sidebar, .app-main {
        transition: width .2s ease, margin-left .2s ease, transform .2s ease;
    }

    #sidebar-backdrop { display: none; }

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

    @media (max-width: 767px) {
        .app-sidebar {
            width: min(18rem, calc(100vw - 3.5rem));
            transform: translateX(-100%);
        }

        .app-main { margin-left: 0; }

        body.sidebar-mobile-open .app-sidebar { transform: translateX(0); }
        body.sidebar-mobile-open { overflow: hidden; }
        body.sidebar-mobile-open #sidebar-backdrop { display: block; }

        body.sidebar-collapsed .app-sidebar { width: min(18rem, calc(100vw - 3.5rem)); }
        body.sidebar-collapsed .app-main { margin-left: 0; }
        body.sidebar-collapsed .sidebar-user-details { display: block; }
        body.sidebar-collapsed .sidebar-link { justify-content: flex-start; gap: .75rem; padding-left: 1rem; padding-right: 1rem; }
        body.sidebar-collapsed .sidebar-link span { display: inline; }
        body.sidebar-collapsed .app-sidebar .absolute { display: block; }
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

        const isMobile = () => window.matchMedia('(max-width: 767px)').matches;
        const savedState = localStorage.getItem('sidebar-collapsed');
        if (savedState === 'true' && !isMobile()) document.body.classList.add('sidebar-collapsed');

        const updateToggle = () => {
            const mobile = isMobile();
            const open = mobile
                ? document.body.classList.contains('sidebar-mobile-open')
                : !document.body.classList.contains('sidebar-collapsed');

            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', mobile ? (open ? 'Close navigation menu' : 'Open navigation menu') : (open ? 'Collapse sidebar' : 'Expand sidebar'));
            toggle.title = toggle.getAttribute('aria-label');
        };

        toggle.addEventListener('click', () => {
            if (isMobile()) {
                document.body.classList.toggle('sidebar-mobile-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
            }
            updateToggle();
        });

        document.getElementById('sidebar-backdrop')?.addEventListener('click', () => {
            document.body.classList.remove('sidebar-mobile-open');
            updateToggle();
        });

        document.querySelectorAll('.app-sidebar .sidebar-link').forEach((link) => {
            link.addEventListener('click', () => {
                if (isMobile()) document.body.classList.remove('sidebar-mobile-open');
            });
        });

        window.addEventListener('resize', () => {
            if (!isMobile()) document.body.classList.remove('sidebar-mobile-open');
            updateToggle();
        });

        updateToggle();
    });
</script>
