/**
 * Service Worker - Horario UdeA PWA
 * Estrategia: Cache-First con Network Fallback
 * Cachea todos los assets estáticos para funcionamiento offline.
 * 
 * @version 3
 * @author Luis Cabezas - Inteligencia.com.co
 */

const CACHE_NAME = 'horario-udea-v4';

/** Lista de assets a pre-cachear durante la instalación */
const ASSETS = [
  './',
  './index.html',
  './data/data.json',
  './manifest.json',
  './icons/logo-app.png',
  './js/app.js',
  './js/data.js',
  './js/views.js'
];

/**
 * Evento Install: Pre-cachea todos los assets definidos.
 * skipWaiting() activa el SW inmediatamente sin esperar tabs abiertos.
 */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
  self.skipWaiting();
});

/**
 * Evento Activate: Limpia caches antiguos de versiones previas.
 * clients.claim() toma control de todas las pestañas abiertas.
 */
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

/**
 * Evento Fetch: Estrategia Cache-First.
 * 1. Busca en cache → si existe, responde desde cache.
 * 2. Si no existe, hace fetch a la red y cachea la respuesta.
 * 3. Si la red falla, devuelve lo que haya en cache.
 */
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        if (response && response.status === 200 && response.type === 'basic') {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => cached);
    })
  );
});
