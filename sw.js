// Service Worker pour les notifications push
self.addEventListener('install', function(event) {
    console.log('Service Worker installé');
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    console.log('Service Worker activé');
    event.waitUntil(self.clients.claim());
});

// Écouter les messages push
self.addEventListener('push', function(event) {
    console.log('Notification push reçue:', event);
    
    let data = {};
    if (event.data) {
        data = event.data.json();
    }
    
    const title = data.title || 'EduConnect';
    const options = {
        body: data.body || 'Nouvelle notification',
        icon: data.icon || '/photos/educonnect-icon.png',
        badge: '/photos/educonnect-badge.png',
        image: data.image,
        data: data.url || '/',
        actions: [
            {
                action: 'open',
                title: 'Ouvrir',
                icon: '/photos/open-icon.png'
            },
            {
                action: 'close',
                title: 'Fermer',
                icon: '/photos/close-icon.png'
            }
        ],
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200],
        tag: data.tag || 'educonnect-notification'
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Gérer les clics sur les notifications
self.addEventListener('notificationclick', function(event) {
    console.log('Clic sur notification:', event);
    
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        const urlToOpen = event.notification.data || '/';
        
        event.waitUntil(
            clients.matchAll({
                type: 'window',
                includeUncontrolled: true
            }).then(function(clientList) {
                // Si une fenêtre est déjà ouverte, la focus
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Sinon, ouvrir une nouvelle fenêtre
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
        );
    }
});

// Gérer la fermeture des notifications
self.addEventListener('notificationclose', function(event) {
    console.log('Notification fermée:', event);
});