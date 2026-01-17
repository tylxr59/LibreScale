// LibreScale Service Worker
const CACHE_NAME = 'librescale-v1';
const urlsToCache = [
  './',
  './index.php',
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

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch new
        return response || fetch(event.request);
      })
      .catch(() => {
        // Return offline page if available
        return caches.match('./index.php');
      })
  );
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
