// LibreScale Service Worker
const CACHE_NAME = 'librescale-v2';
const urlsToCache = [

  './styles.css',
  './app.js',
  './MaterialSymbolsOutlined.woff2',
  './favicon.ico'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Fetch event - network-first for HTML, cache-first for assets
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  const requestURL = new URL(event.request.url);
  const isHTMLRequest = event.request.headers.get('accept')?.includes('text/html') || 
                        requestURL.pathname.endsWith('.php') ||
                        requestURL.pathname.endsWith('/');

  if (isHTMLRequest) {
    // Network-first strategy for HTML pages
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          // Fallback to cache if offline
          return caches.match(event.request);
        })
    );
  } else {
    // Cache-first strategy for static assets
    event.respondWith(
      caches.match(event.request)
        .then(response => {
          return response || fetch(event.request);
        })
    );
  }
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
