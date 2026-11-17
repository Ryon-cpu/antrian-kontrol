import { createApp } from 'vue';
import { Toaster, toast } from 'vue-sonner';

const app = document.getElementById('companion-queue-app');
const toastRoot = document.getElementById('queue-toast-root');

if (toastRoot) {
    createApp(Toaster, {
        position: 'top-right',
        richColors: true,
        closeButton: true,
    }).mount(toastRoot);
}

if (app) {
    const tokenKey = 'antrian_device_token';
    const feedback = document.getElementById('queue-feedback');
    const queueStatus = document.getElementById('queue-status');
    const noQueue = document.getElementById('no-queue');
    const noQueueMessage = document.getElementById('no-queue-message');
    const refreshButton = document.getElementById('refresh-queue');
    const enableNotificationsButton = document.getElementById('enable-notifications');
    const registerAnotherLink = document.getElementById('register-another');
    const turnDialog = document.getElementById('turn-dialog');
    const turnMessage = document.getElementById('turn-message');
    const closeTurnDialogButton = document.getElementById('close-turn-dialog');
    const vapidPublicKey = app.dataset.vapidPublicKey;
    let serviceWorkerRegistration = null;
    let pushSubscriptionActive = false;
    let lastCallSignature = null;
    let requestInProgress = false;

    const showFeedback = (message, isError = false) => {
        feedback.textContent = message;
        if (isError) {
            toast.error(message);
            return;
        }

        toast.success(message);
    };

    const statusLabels = {
        waiting: 'Menunggu giliran',
        called: 'Nomor Anda sedang dipanggil',
        done: 'Pemeriksaan selesai',
        cancelled: 'Antrian dibatalkan',
    };

    const renderQueue = (data) => {
        const { entry, ahead, now_serving: nowServing, practice } = data;
        document.getElementById('my-number').textContent = entry.number_label;
        document.getElementById('my-status').textContent = statusLabels[entry.status] ?? 'Status antrian diperbarui';
        document.getElementById('now-serving').textContent = nowServing ?? '--';
        document.getElementById('practice-status').textContent = practice.message;

        if (entry.status === 'called' || ahead === 0) {
            document.getElementById('ahead-count').textContent = 'Anda sedang dipanggil. Silakan menuju ruang pemeriksaan.';
        } else {
            document.getElementById('ahead-count').textContent = `Masih ada ${ahead} antrian di depan Anda.`;
        }

        queueStatus.classList.remove('hidden');
        queueStatus.classList.add('flex');
        noQueue.classList.add('hidden');
        noQueue.classList.remove('flex');
        registerAnotherLink.classList.toggle('hidden', ['waiting', 'called'].includes(entry.status));
        registerAnotherLink.classList.toggle('flex', !['waiting', 'called'].includes(entry.status));
        enableNotificationsButton.classList.toggle('hidden', !['waiting', 'called'].includes(entry.status));

        if (entry.status === 'called') {
            const signature = `${entry.number_label}:${entry.call_count}`;
            if (signature !== lastCallSignature) {
                lastCallSignature = signature;
                turnMessage.textContent = `Nomor ${entry.number_label} sedang dipanggil. Mohon segera menuju ruang periksa.`;
                if (!turnDialog.open) {
                    turnDialog.showModal();
                }

                if (!pushSubscriptionActive
                    && 'Notification' in window
                    && document.visibilityState === 'hidden'
                    && Notification.permission === 'granted') {
                    serviceWorkerRegistration?.showNotification('Giliran Anda', {
                        body: turnMessage.textContent,
                        icon: '/icons/antrian-192.png',
                        tag: `antrian-${signature}`,
                        data: { url: '/antrian' },
                        requireInteraction: true,
                        vibrate: [300, 150, 300],
                    });
                }
            }
        } else {
            lastCallSignature = null;
        }
    };

    const showMissingQueue = (message) => {
        queueStatus.classList.add('hidden');
        queueStatus.classList.remove('flex');
        noQueue.classList.remove('hidden');
        noQueue.classList.add('flex');
        noQueueMessage.textContent = message;
    };

    const refreshQueue = async () => {
        const token = window.localStorage.getItem(tokenKey);
        if (!token) {
            showMissingQueue('Token perangkat tidak ditemukan di browser ini. Kembali ke halaman pendaftaran untuk mendaftar atau memulihkan antrian.');
            return;
        }
        if (requestInProgress) {
            return;
        }

        requestInProgress = true;
        try {
            const response = await fetch('/api/companion/queue', {
                headers: { Accept: 'application/json', 'X-Device-Token': token },
            });
            const data = await response.json();

            if (response.status === 401) {
                window.localStorage.removeItem(tokenKey);
                showMissingQueue('Perangkat ini tidak dikenali. Kembali ke halaman pendaftaran untuk membuat antrian baru.');
                return;
            }
            if (response.status === 404) {
                showMissingQueue(data.message ?? 'Data antrian untuk perangkat ini tidak ditemukan hari ini.');
                return;
            }
            if (!response.ok) {
                throw new Error(data.message ?? 'Status antrian belum dapat dimuat.');
            }

            renderQueue(data);
            feedback.classList.add('hidden');
        } catch (error) {
            showFeedback(error.message || 'Koneksi gagal. Status akan dicoba kembali otomatis.', true);
        } finally {
            requestInProgress = false;
        }
    };

    const decodeVapidKey = (key) => {
        const padded = `${key}${'='.repeat((4 - key.length % 4) % 4)}`;
        const decoded = window.atob(padded.replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(decoded, (character) => character.charCodeAt(0));
    };

    const registerServiceWorker = async () => {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) {
            return null;
        }
        serviceWorkerRegistration = await navigator.serviceWorker.register('/service-worker.js');
        return serviceWorkerRegistration;
    };

    const enablePush = async () => {
        if (!('Notification' in window) || !window.isSecureContext || !('PushManager' in window)) {
            showFeedback('Browser ini tidak mendukung Web Push. Halaman tetap memperbarui status otomatis.', true);
            return;
        }
        if (!vapidPublicKey) {
            showFeedback('Notifikasi belum dikonfigurasi oleh pengelola. Halaman tetap memperbarui status otomatis.', true);
            return;
        }

        const permission = Notification.permission === 'default'
            ? await Notification.requestPermission()
            : Notification.permission;
        if (permission !== 'granted') {
            showFeedback('Izin notifikasi tidak diberikan. Halaman tetap memperbarui status otomatis.', true);
            return;
        }

        const registration = serviceWorkerRegistration ?? await registerServiceWorker();
        if (!registration) {
            showFeedback('Service worker tidak tersedia. Halaman tetap memperbarui status otomatis.', true);
            return;
        }
        const readyRegistration = await navigator.serviceWorker.ready;
        let subscription = await readyRegistration.pushManager.getSubscription();
        if (!subscription) {
            subscription = await readyRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeVapidKey(vapidPublicKey),
            });
        }

        const token = window.localStorage.getItem(tokenKey);
        const response = await fetch('/api/companion/push-subscription', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Device-Token': token,
            },
            body: JSON.stringify(subscription.toJSON()),
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message ?? 'Langganan notifikasi belum dapat disimpan.');
        }

        pushSubscriptionActive = true;
        showFeedback('Notifikasi giliran berhasil diaktifkan.');
    };

    refreshButton.addEventListener('click', refreshQueue);
    enableNotificationsButton.addEventListener('click', async () => {
        enableNotificationsButton.disabled = true;
        try {
            await enablePush();
        } catch (error) {
            showFeedback(error.message || 'Notifikasi belum dapat diaktifkan.', true);
        } finally {
            enableNotificationsButton.disabled = false;
        }
    });
    closeTurnDialogButton.addEventListener('click', () => turnDialog.close());

    registerServiceWorker().catch(() => {
        showFeedback('Service worker tidak dapat diaktifkan. Status tetap diperbarui saat halaman terbuka.', true);
    });
    refreshQueue();
    window.setInterval(refreshQueue, 10000);
}
