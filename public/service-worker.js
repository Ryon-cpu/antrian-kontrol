const CACHE_NAME = 'antrian-pasien-v1';
const OFFLINE_URL = '/offline.html';
const ICON_URL = '/icons/antrian-192.png';
const LARGE_ICON_URL = '/icons/antrian-512.png';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll([OFFLINE_URL, ICON_URL, LARGE_ICON_URL]))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin || event.request.method !== 'GET') {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(async () => (
                (await caches.match(OFFLINE_URL)) ?? Response.error()
            )),
        );
        return;
    }

    if (requestUrl.pathname.startsWith('/build/') || requestUrl.pathname.startsWith('/icons/')) {
        event.respondWith((async () => {
            const cached = await caches.match(event.request);
            if (cached) {
                return cached;
            }

            const response = await fetch(event.request);
            if (response.ok) {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(event.request, response.clone());
            }
            return response;
        })());
    }
});

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = { body: event.data?.text() ?? '' };
    }

    const notificationUrl = new URL(payload.url ?? '/', self.location.origin);
    const safeUrl = notificationUrl.origin === self.location.origin
        ? `${notificationUrl.pathname}${notificationUrl.search}${notificationUrl.hash}`
        : '/';

    event.waitUntil(self.registration.showNotification(payload.title ?? 'Pembaruan antrian', {
        body: payload.body ?? 'Status antrian Anda telah diperbarui.',
        icon: ICON_URL,
        badge: ICON_URL,
        tag: payload.tag ?? 'antrian-pasien',
        data: { url: safeUrl },
        requireInteraction: Boolean(payload.requireInteraction),
        vibrate: payload.vibrate ?? [200],
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const destination = new URL(event.notification.data?.url ?? '/', self.location.origin);

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const existing = clients.find((client) => new URL(client.url).origin === self.location.origin);
            if (existing) {
                existing.navigate(destination.href);
                return existing.focus();
            }

            return self.clients.openWindow(destination.href);
        }),
    );
});
