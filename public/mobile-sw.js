self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('mobile-attendance-v2').then((cache) => {
            return cache.addAll([
                './mobile-attendance.html',
                './mobile-attendance.css',
                './mobile-attendance.js',
                './mobile-sw.js',
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
