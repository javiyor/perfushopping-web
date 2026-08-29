const CACHE_NAME = 'pf-admin-v2';
const PRECACHE_URLS = [
  '/admin/',
  '/assets/app.css',
  '/assets/app.js',
  '/assets/brand/logo-header.png',
  '/assets/brand/pwa-icon-192.png',
  '/assets/brand/pwa-icon-512.png',
  '/manifest.webmanifest'
];

function offlineHtmlResponse() {
  return new Response(
    '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sin conexión</title><body style="font-family:system-ui;padding:20px"><h3>Sin conexión</h3><p>No hay internet y este contenido no está en caché. Volvé a intentar cuando tengas señal.</p></body></html>',
    {
      status: 503,
      headers: { 'Content-Type': 'text/html; charset=UTF-8' }
    }
  );
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => Promise.resolve())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  const isAdminNav = req.mode === 'navigate' && url.pathname.startsWith('/admin');
  if (isAdminNav) {
    event.respondWith(
      fetch(req)
        .then((resp) => {
          if (resp && resp.ok) {
            const copy = resp.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return resp;
        })
        .catch(() => caches.match(req).then((hit) => hit || caches.match('/admin/') || offlineHtmlResponse()))
    );
    return;
  }

  if (url.pathname.startsWith('/assets/') || url.pathname === '/manifest.webmanifest') {
    event.respondWith(
      caches.match(req).then((hit) => {
        const networkFetch = fetch(req)
          .then((resp) => {
            if (resp && resp.ok) {
              const copy = resp.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
            }
            return resp;
          })
          .catch(() => hit || new Response('', { status: 503, statusText: 'offline' }));
        return hit || networkFetch;
      })
    );
  }
});
