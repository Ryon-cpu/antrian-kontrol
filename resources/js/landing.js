const app = document.getElementById('queue-app');
const form = document.getElementById('registration-form');

if (app && form) {
    const tokenKey = 'antrian_device_token';
    const fieldset = document.getElementById('registration-fields');
    const submitButton = document.getElementById('submit-registration');
    const feedback = document.getElementById('queue-feedback');
    const installButton = document.getElementById('install-app');
    const practiceOpen = app.dataset.practiceOpen === 'true';
    const queueFull = app.dataset.queueFull === 'true';
    let deferredInstallPrompt = null;

    const showFeedback = (message, isError = false) => {
        feedback.replaceChildren(document.createTextNode(message));
        feedback.className = 'mt-6 rounded-md border px-4 py-3 text-base';
        feedback.classList.add(...(isError
            ? ['border-red-300', 'bg-red-50', 'text-red-800']
            : ['border-blue-200', 'bg-blue-50', 'text-blue-900']));

        feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const updateFormState = () => {
        fieldset.disabled = !practiceOpen || queueFull;
        submitButton.disabled = fieldset.disabled;
    };

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
            showFeedback('Gunakan menu browser lalu pilih "Tambahkan ke layar utama" atau "Install app" untuk memasang aplikasi ini.');
            return;
        }

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.classList.add('hidden');
    });

    window.addEventListener('appinstalled', () => installButton.classList.add('hidden'));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) {
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Memproses pendaftaran...';

        try {
            const token = window.localStorage.getItem(tokenKey);
            const payload = Object.fromEntries(new FormData(form).entries());
            payload.patient_age = Number(payload.patient_age);

            const response = await fetch('/api/companion/register', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(token ? { 'X-Device-Token': token } : {}),
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Pendaftaran belum berhasil.');
            }

            window.localStorage.setItem(tokenKey, data.device_token);
            form.reset();
            window.location.assign('/antrian');
        } catch (error) {
            showFeedback(error.message || 'Koneksi gagal. Periksa jaringan lalu coba lagi.', true);
        } finally {
            submitButton.textContent = 'Daftarkan pasien';
            updateFormState();
        }
    });

    updateFormState();
}
