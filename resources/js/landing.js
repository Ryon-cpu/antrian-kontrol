import { createApp } from 'vue';
import LandingPage from './components/LandingPage.vue';

const landingRoot = document.querySelector('[data-landing-page]');

if (landingRoot) {
    const config = landingRoot.dataset.landingConfig;

    if (!config) {
        throw new Error('Landing page Vue configuration is missing.');
    }

    createApp(LandingPage, {
        config: JSON.parse(config),
    }).mount(landingRoot);
}

const installButton = document.getElementById('install-app');
let deferredInstallPrompt = null;

if (installButton) {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        installButton.classList.remove('hidden');
    });

    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        installButton.classList.add('hidden');
    }

    installButton.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            window.dispatchEvent(new CustomEvent('queue-feedback', {
                detail: {
                    message: 'Gunakan menu browser lalu pilih "Tambahkan ke layar utama" atau "Install app" untuk memasang aplikasi ini.',
                    isError: false,
                },
            }));
            return;
        }

        await deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.classList.add('hidden');
    });

    window.addEventListener('appinstalled', () => installButton.classList.add('hidden'));
}
