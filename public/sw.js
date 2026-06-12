// Bumper VERSION à chaque modification du SW ou du SHELL.
// Les assets AssetMapper (/assets/*) sont hashés et immuables : ils n'exigent
// jamais de bump, l'ancien cache est purgé à l'activation de la nouvelle version.
const VERSION = 'v3';
const CACHE = `bingo-${VERSION}`;
// `/pwa/` et pas `/icons/` : Apache (mutualisé) a un Alias global `/icons/`
// vers ses propres icônes d'auto-index, qui masque tout dossier du même nom.
const SHELL = [
    '/offline.html',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
    '/pwa/apple-touch-icon.png',
    '/logo.png',
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL))
    );
    // Pas de skipWaiting() ici : la page décide via le message SKIP_WAITING
    // (toast « Nouvelle version disponible »).
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
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

    // Ressources non hashées : stale-while-revalidate, pour que les mises à
    // jour d'icônes/manifest se propagent sans bump de VERSION.
    const isMutable = url.pathname.startsWith('/pwa/')
        || url.pathname === '/logo.png'
        || url.pathname === '/manifest.json';

    if (isMutable) {
        event.respondWith(
            caches.match(req).then((cached) => {
                const refetch = fetch(req)
                    .then((res) => {
                        if (res.ok) {
                            const clone = res.clone();
                            caches.open(CACHE).then((c) => c.put(req, clone));
                        }
                        return res;
                    })
                    .catch(() => cached);
                return cached || refetch;
            })
        );
        return;
    }

    // App shell (assets hashés, immuables) + photos des cases : cache-first.
    const isImmutable = url.pathname.startsWith('/assets/')
        || url.pathname.startsWith('/uploads/');

    if (isImmutable) {
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
