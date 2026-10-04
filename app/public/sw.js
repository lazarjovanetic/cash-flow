// Minimalni service worker: omogućava instalaciju (PWA) i čuva statične fajlove.
// Stranice se uvek učitavaju sa servera da bi podaci bili tačni.
const CACHE = 'dugovanja-static-v1';
const ASSETS = ['css/app.css', 'js/app.js', 'js/alpine.min.js', 'icons/icon.svg', 'icons/icon-192.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS.map(a => new URL(a, self.registration.scope)))).then(() => self.skipWaiting()));
});
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET' || url.origin !== location.origin || !/\.(css|js|svg|png)$/.test(url.pathname)) return;
  e.respondWith(fetch(e.request).then(r => {
    const copy = r.clone();
    caches.open(CACHE).then(c => c.put(e.request, copy));
    return r;
  }).catch(() => caches.match(e.request, { ignoreSearch: true })));
});
