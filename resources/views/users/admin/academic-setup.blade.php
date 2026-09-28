@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@section('title', 'Academic Setup - '.($setupTab === 'academic-year-config' ? 'Academic Years' : 'Grading Terms'))

@push('toasts')
    <x-password-reset-toasts test-prefix="academic-setup" />
@endpush

@section('content')
@php
    $managementRoutePrefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.';
@endphp

@include('users.partials.academic-setup-navigation', ['currentYear' => $yearData['currentYear']])

<section id="academic-year-config-panel" role="tabpanel" aria-labelledby="academic-year-config-tab" tabindex="0" @if ($setupTab !== 'academic-year-config') hidden @endif>
    @include('users.admin.academic-year-config', $yearData)
</section>
<section id="grading-term-config-panel" role="tabpanel" aria-labelledby="grading-term-config-tab" tabindex="0" @if ($setupTab !== 'grading-term-config') hidden @endif>
    @include('users.admin.grading-term-config', $termData)
</section>

<script>
    (() => {
        const tabs = Array.from(document.querySelectorAll('[data-academic-tab]'));
        const urls = new Map(tabs.map(tab => [tab.dataset.academicTab, tab.href]));

        function selectTab(tab, updateHistory = true) {
            tabs.forEach(item => {
                const selected = item === tab;
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
                document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
            });
            document.title = 'Academic Setup - ' + tab.textContent.trim() + ' | Agusan National High School';
            if (updateHistory) history.pushState(null, '', urls.get(tab.dataset.academicTab));
        }

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', event => {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
                event.preventDefault();
                if (tab.getAttribute('aria-selected') !== 'true') selectTab(tab);
            });
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
                if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
                if (event.key === 'Home') next = tabs[0];
                if (event.key === 'End') next = tabs[tabs.length - 1];
                if (event.key === ' ') next = tab;
                if (!next) return;
                event.preventDefault();
                next.focus();
                if (next.getAttribute('aria-selected') !== 'true') selectTab(next);
            });
        });

        const initialTab = tabs.find(tab => tab.getAttribute('aria-selected') === 'true');
        urls.set(initialTab.dataset.academicTab, window.location.href);
        window.addEventListener('popstate', () => {
            const tab = tabs.find(item => new URL(item.href).pathname === window.location.pathname);
            if (tab) selectTab(tab, false);
        });
    })();
</script>
@endsection
