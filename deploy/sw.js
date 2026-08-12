/**
 * Service Worker - Horario UdeA PWA
 * Estrategia: Cache assets estáticos, Network-First para páginas PHP.
 * @version 1.0.0
 */
const CACHE_NAME = 'horario-udea-v1';
const STATIC_ASSETS = [
  '/css/app.css',
  '/js/app.js',
  '/icons/logo-app.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Assets estáticos: Cache-First
  if (url.pathname.match(/\.(css|js|png|jpg|svg|woff2?|json)$/) && !url.pathname.includes('api.php')) {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request).then(res => {
        const clone = res.clone();
        caches.open(CACHE_NAME).then(c => c.put(event.request, clone));
        return res;
      }))
    );
    return;
  }

  // Páginas PHP: Network-First
  event.respondWith(
    fetch(event.request).then(res => res).catch(() => caches.match(event.request))
  );
});
