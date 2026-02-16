self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('mobile-attendance-v1').then((cache) => {
            return cache.addAll(['./mobile-attendance.html']);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
