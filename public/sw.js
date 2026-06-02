const CACHE = 'bingo-v1';
const SHELL = ['/offline.html', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    const isHtml = req.mode === 'navigate'
        || (req.headers.get('accept') || '').includes('text/html');

    if (isHtml) {
        const isPublicShare = url.pathname.startsWith('/b/');
        event.respondWith(
            fetch(req)
                .then((res) => {
                    if (isPublicShare && res.ok) {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(req, clone));
                    }
                    return res;
                })
                .catch(() =>
                    caches.match(req).then((cached) => cached || caches.match('/offline.html'))
                )
        );
        return;
    }

    const isCacheable = url.pathname.startsWith('/assets/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/logo.png'
        || url.pathname === '/manifest.json';

    if (isCacheable) {
        event.respondWith(
            caches.match(req).then((cached) =>
                cached || fetch(req).then((res) => {
                    if (res.ok) {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(req, clone));
                    }
                    return res;
                })
            )
        );
    }
});
