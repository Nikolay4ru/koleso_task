// ===== Service Worker (sw.js) =====

self.addEventListener('push', function(event) {
    const options = {
        body: event.data ? event.data.text() : 'Входящий звонок',
        icon: '/icon-192x192.png',
        badge: '/icon-72x72.png',
        vibrate: [500, 300, 500],
        data: event.data ? JSON.parse(event.data.text()) : {},
        requireInteraction: true,
        actions: [
            {
                action: 'accept',
                title: 'Принять',
                icon: '/icon-accept.png'
            },
            {
                action: 'decline', 
                title: 'Отклонить',
                icon: '/icon-decline.png'
            }
        ],
        tag: 'incoming-call'
    };

    event.waitUntil(
        self.registration.showNotification('Входящий звонок', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    const action = event.action;
    const callData = event.notification.data;
    
    if (action === 'accept') {
        // Открываем окно мессенджера и принимаем звонок
        event.waitUntil(
            clients.openWindow('/messenger').then(client => {
                client.postMessage({
                    action: 'accept-call',
                    callId: callData.callId
                });
            })
        );
    } else if (action === 'decline') {
        // Отклоняем звонок через API
        event.waitUntil(
            fetch('/api/calls/' + callData.callId + '/decline', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + callData.token
                }
            })
        );
    } else {
        // Обычный клик - открываем мессенджер
        event.waitUntil(
            clients.openWindow('/messenger')
        );
    }
});