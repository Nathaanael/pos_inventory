/**
 * Self-destructing Service Worker.
 * This will unregister the service worker and force a reload for clients to clear offline caching.
 */

self.addEventListener('install', function(e) {
    self.skipWaiting();
});

self.addEventListener('activate', function(e) {
    self.registration.unregister()
        .then(function() {
            return self.clients.matchAll();
        })
        .then(function(clients) {
            clients.forEach(client => {
                if (client.url && "navigate" in client) {
                    client.navigate(client.url);
                }
            });
        });
});
