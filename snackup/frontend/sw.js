// 🚀 SERVICE WORKER - Le Marvelous
// Cache offline pour performance optimale

const CACHE_NAME = 'marvelous-v1.0.0';
const CACHE_ASSETS = [
    '/template-v2/',
    '/template-v2/index.html',
    '/template-v2/cart.html',
    '/template-v2/css/style.css',
    '/template-v2/css/home.css',
    '/template-v2/css/pages.css',
    '/template-v2/js/config.js',
    '/template-v2/js/app.js',
    '/template-v2/js/products.js',
    '/template-v2/js/cart.js',
    '/template-v2/js/reviews.js',
    '/images/hero.jpg',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
];

// Installation - Mise en cache des assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(CACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activation - Nettoyer les anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch - Stratégie Network First avec fallback cache
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Ignorer les requêtes non-GET
    if (request.method !== 'GET') return;

    // Ignorer les requêtes API (toujours réseau)
    if (request.url.includes('/api/') || request.url.includes('/admin-panel-v2/')) {
        return;
    }

    event.respondWith(
        // Essayer le réseau d'abord
        fetch(request)
            .then((response) => {
                // Si succès, mettre en cache ET retourner
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Si échec réseau, utiliser le cache
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // Fallback pour pages HTML: retourner index.html
                    if (request.headers.get('accept').includes('text/html')) {
                        return caches.match('/template-v2/index.html');
                    }

                    // Sinon, retourner erreur réseau
                    return new Response('Offline - Resource not available', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({
                            'Content-Type': 'text/plain'
                        })
                    });
                });
            })
    );
});

// Messages (pour forcer mise à jour cache)
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.delete(CACHE_NAME).then(() => {
                return self.registration.update();
            })
        );
    }
});
