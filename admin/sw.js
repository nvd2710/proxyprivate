// sw.js
const CACHE_NAME = 'proxyprivate-v1';
const urlsToCache = [
    '/',
    '/assets/css/style.css',
    '/assets/js/main.js',
    '/assets/images/logo.png',
    '/assets/images/favicon.ico'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
    );
});
