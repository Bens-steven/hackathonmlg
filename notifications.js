// Système de notifications push pour EduConnect
class EduConnectNotifications {
    constructor() {
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.registration = null;
        this.subscription = null;
    }

    // Initialiser le système de notifications
    async init() {
        if (!this.isSupported) {
            console.warn('Les notifications push ne sont pas supportées');
            return false;
        }

        try {
            // Enregistrer le service worker
            this.registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker enregistré:', this.registration);

            // Attendre que le service worker soit prêt
            await navigator.serviceWorker.ready;
            
            return true;
        } catch (error) {
            console.error('Erreur lors de l\'initialisation:', error);
            return false;
        }
    }

    // Demander la permission pour les notifications
    async requestPermission() {
        if (!this.isSupported) return false;

        try {
            const permission = await Notification.requestPermission();
            console.log('Permission notifications:', permission);
            
            if (permission === 'granted') {
                await this.subscribeToPush();
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Erreur permission:', error);
            return false;
        }
    }

    // S'abonner aux notifications push
    async subscribeToPush() {
        if (!this.registration) return false;

        try {
            // Clé publique VAPID (mise à jour avec la vraie clé)
            const vapidPublicKey = 'BEl62iUYgUivxIkv69yViEuiBIa40HI0DLLuxazjqAKVXTdtkoTrZPPUi5ygP-5ysIxdPSwPV3TbVBNuIUvzNAI';
            
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
            });

            this.subscription = subscription;
            console.log('Abonnement push:', subscription);

            // Envoyer l'abonnement au serveur
            await this.sendSubscriptionToServer(subscription);
            
            return true;
        } catch (error) {
            console.error('Erreur abonnement push:', error);
            return false;
        }
    }

    // Envoyer l'abonnement au serveur
    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('save_push_subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subscription: subscription,
                    username: this.getCurrentUsername()
                })
            });

            const result = await response.json();
            console.log('Abonnement sauvegardé:', result);
        } catch (error) {
            console.error('Erreur sauvegarde abonnement:', error);
        }
    }

    // Convertir la clé VAPID
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Obtenir le nom d'utilisateur actuel
    getCurrentUsername() {
        // Récupérer depuis la session ou le DOM
        const usernameElement = document.querySelector('.username');
        if (usernameElement) {
            return usernameElement.textContent.replace('@', '');
        }
        return null;
    }

    // Tester une notification
    async testNotification() {
        if (!this.isSupported || Notification.permission !== 'granted') {
            console.warn('Notifications non autorisées');
            return;
        }

        try {
            await fetch('send_test_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: this.getCurrentUsername()
                })
            });
        } catch (error) {
            console.error('Erreur test notification:', error);
        }
    }

    // Vérifier le statut des notifications
    getNotificationStatus() {
        if (!this.isSupported) {
            return 'not-supported';
        }
        
        return Notification.permission;
    }

    // Afficher une notification locale (fallback)
    showLocalNotification(title, options = {}) {
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: options.body || '',
                icon: options.icon || '/photos/educonnect-icon.png',
                badge: '/photos/educonnect-badge.png',
                ...options
            });
        }
    }
}

// Instance globale
window.eduNotifications = new EduConnectNotifications();