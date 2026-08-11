/**
 * Service Worker - Horario UdeA PWA
 * Estrategia: Network-Only (solo habilita la instalación como app)
 * No cachea contenido — el sitio requiere conexión a internet.
 * El SW existe únicamente para que el navegador permita instalar la PWA.
 * 
 * @version 5
 * @author Luis Cabezas - Inteligencia.com.co
 */

// No pre-cachea nada
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', () => self.clients.claim());

/**
 * Todas las peticiones van directo a la red.
 * Si no hay conexión, el navegador muestra su error estándar.
 */
self.addEventListener('fetch', event => {
  event.respondWith(fetch(event.request));
});
