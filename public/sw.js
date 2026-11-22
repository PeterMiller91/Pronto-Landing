/* Pronto Pronto – Service Worker: schnelle Wiederkehr + Speisekarte auch ohne Netz */
'use strict';

const VERSION = 'pp-v1';
const CORE = [
  './',
  './assets/css/site.css',
  './assets/js/site.js',
  './assets/img/margherita.jpg',
  './assets/img/icon-192.png',
  './assets/fonts/karla-latin-400-normal.woff2',
  './assets/fonts/karla-latin-700-normal.woff2',
  './assets/fonts/playfair-display-latin-800-normal.woff2',
  './assets/fonts/playfair-display-latin-600-italic.woff2'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(VERSION).then((c) => c.addAll(CORE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;            // Google Maps etc. nie anfassen
  if (/\/(admin|setup\.php)/.test(url.pathname)) return;        // Backend immer live

  // Seiten: zuerst Netz (aktuelle Preise & Öffnungsstatus), offline die letzte Version
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok) {
            const copy = res.clone();
            caches.open(VERSION).then((c) => c.put(url.pathname === new URL('./', self.location).pathname ? './' : req, copy));
          }
          return res;
        })
        .catch(() => caches.match(req, { ignoreSearch: true }).then((r) => r || caches.match('./')))
    );
    return;
  }

  // Dateien (CSS, JS, Schriften, Bilder): aus dem Speicher, im Hintergrund aktualisieren
  if (/\/(assets|uploads)\//.test(url.pathname)) {
    event.respondWith(
      caches.open(VERSION).then((cache) =>
        cache.match(req).then((cached) => {
          const network = fetch(req).then((res) => {
            if (res.ok) cache.put(req, res.clone());
            return res;
          }).catch(() => cached);
          return cached || network;
        })
      )
    );
  }
});
