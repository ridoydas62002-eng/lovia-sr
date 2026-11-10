const CACHE_NAME = 'lovia-cache-v1';
const urlsToCache = [
  '/lovia_app/index.php',
  '/lovia_app/assets/css/style.css',
  '/lovia_app/register.php'
];

// ইনস্টল করার সময় ফাইলগুলো ক্যাশ করা
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// ফেচ বা অফলাইন মোডের জন্য রিকোয়েস্ট হ্যান্ডেল করা
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        return response || fetch(event.request);
      })
  );
});