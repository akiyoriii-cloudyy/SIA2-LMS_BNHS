const CACHE_NAME = 'mobile-attendance-v4';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                './mobile-attendance.html',
                './mobile-attendance.css',
                './mobile-attendance.js',
                './mobile-sw.js',
            ]);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const isAppShell = /mobile-attendance\.(html|css|js)$/.test(url.pathname);
    if (isAppShell) {
        event.respondWith(
            fetch(event.request).then((r) => {
                const copy = r.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                return r;
            }).catch(() => caches.match(event.request))
        );
        return;
    }
    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
