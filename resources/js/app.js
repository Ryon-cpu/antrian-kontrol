

import Alpine from 'alpinejs';
import { mountDashboardVue, unmountDashboardVue } from './vue-dashboard';

window.Alpine = Alpine;

Alpine.start();
mountDashboardVue();

let activeNavigation = null;

async function navigate(url, { pushState = true } = {}) {
    activeNavigation?.abort();

    const controller = new AbortController();
    activeNavigation = controller;
    document.documentElement.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url.href, {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`Navigation request failed with status ${response.status}`);
        }

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        const currentShell = document.querySelector('#app-shell');
        const nextShell = nextDocument.querySelector('#app-shell');

        if (!currentShell || !nextShell) {
            window.location.assign(response.url);
            return;
        }

        unmountDashboardVue();
        Alpine.destroyTree(currentShell);
        currentShell.replaceWith(nextShell);
        Alpine.initTree(nextShell);
        mountDashboardVue();

        document.title = nextDocument.title;

        const destination = new URL(response.url);
        if (pushState) {
            window.history.pushState({}, '', destination);
        }

        if (destination.hash) {
            document.getElementById(decodeURIComponent(destination.hash.slice(1)))?.scrollIntoView();
        } else {
            window.scrollTo(0, 0);
        }
    } catch (error) {
        if (controller.signal.aborted) {
            return;
        }

        console.error('In-app navigation failed; loading the requested page normally.', error);
        window.location.assign(url.href);
    } finally {
        if (activeNavigation === controller) {
            activeNavigation = null;
            document.documentElement.removeAttribute('aria-busy');
        }
    }
}

document.addEventListener('click', (event) => {
    if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
    ) {
        return;
    }

    const target = event.target instanceof Element ? event.target : event.target.parentElement;
    const link = target?.closest('a[href]');

    if (
        !link
        || link.hasAttribute('download')
        || (link.target && link.target !== '_self')
        || link.relList.contains('external')
        || !document.querySelector('#app-shell')
    ) {
        return;
    }

    const destination = new URL(link.href, window.location.href);

    if (
        destination.origin !== window.location.origin
        || (
            destination.pathname === window.location.pathname
            && destination.search === window.location.search
        )
    ) {
        return;
    }

    event.preventDefault();
    navigate(destination);
});

window.addEventListener('popstate', () => {
    navigate(new URL(window.location.href), { pushState: false });
});
