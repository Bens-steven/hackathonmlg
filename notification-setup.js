// Script d'initialisation des notifications pour EduConnect
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🔔 Initialisation du système de notifications...');
    
    // Vérifier si on est sur une page élève
    const isStudentPage = document.body.classList.contains('student-page') || 
                         window.location.pathname.includes('eleve.php');
    
    if (!isStudentPage) {
        console.log('📱 Page non-élève, notifications désactivées');
        return;
    }

    // Initialiser le système de notifications
    const notificationSystem = window.eduNotifications;
    
    if (!notificationSystem) {
        console.error('❌ Système de notifications non trouvé');
        return;
    }

    // Initialiser
    const initialized = await notificationSystem.init();
    if (!initialized) {
        console.warn('⚠️ Impossible d\'initialiser les notifications');
        return;
    }

    // Créer le bouton de notification dans l'interface
    createNotificationButton();
    
    // Vérifier le statut actuel
    updateNotificationStatus();
    
    // Auto-demander la permission après 5 secondes (non intrusif)
    setTimeout(async () => {
        if (notificationSystem.getNotificationStatus() === 'default') {
            showNotificationPrompt();
        }
    }, 5000);
});

// Créer le bouton de notification
function createNotificationButton() {
    const headerActions = document.querySelector('.header-actions');
    if (!headerActions) return;

    const notifBtn = document.createElement('button');
    notifBtn.id = 'notification-btn';
    notifBtn.className = 'btn btn-secondary';
    notifBtn.innerHTML = `
        <i class="fas fa-bell"></i>
        <span class="btn-text">Notifications</span>
        <span class="notification-status" id="notif-status"></span>
    `;
    
    notifBtn.addEventListener('click', handleNotificationClick);
    
    // Insérer avant le bouton de déconnexion
    const logoutBtn = headerActions.querySelector('a[href="logout.php"]');
    if (logoutBtn) {
        headerActions.insertBefore(notifBtn, logoutBtn);
    } else {
        headerActions.appendChild(notifBtn);
    }
}

// Gérer le clic sur le bouton notification
async function handleNotificationClick() {
    const status = window.eduNotifications.getNotificationStatus();
    
    if (status === 'default') {
        const granted = await window.eduNotifications.requestPermission();
        if (granted) {
            showNotificationSuccess('Notifications activées ! 🎉');
        } else {
            showNotificationError('Notifications refusées 😞');
        }
    } else if (status === 'granted') {
        // Tester une notification
        await window.eduNotifications.testNotification();
        showNotificationSuccess('Notification de test envoyée ! 📱');
    } else {
        showNotificationError('Notifications bloquées. Activez-les dans les paramètres de votre navigateur.');
    }
    
    updateNotificationStatus();
}

// Mettre à jour le statut visuel
function updateNotificationStatus() {
    const statusElement = document.getElementById('notif-status');
    const btnElement = document.getElementById('notification-btn');
    
    if (!statusElement || !btnElement) return;
    
    const status = window.eduNotifications.getNotificationStatus();
    
    switch (status) {
        case 'granted':
            statusElement.innerHTML = '<i class="fas fa-check-circle" style="color: #22c55e;"></i>';
            btnElement.title = 'Notifications activées - Cliquer pour tester';
            btnElement.classList.remove('btn-warning', 'btn-danger');
            btnElement.classList.add('btn-success');
            break;
            
        case 'denied':
            statusElement.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
            btnElement.title = 'Notifications bloquées';
            btnElement.classList.remove('btn-success', 'btn-warning');
            btnElement.classList.add('btn-danger');
            break;
            
        case 'default':
            statusElement.innerHTML = '<i class="fas fa-question-circle" style="color: #f59e0b;"></i>';
            btnElement.title = 'Cliquer pour activer les notifications';
            btnElement.classList.remove('btn-success', 'btn-danger');
            btnElement.classList.add('btn-warning');
            break;
            
        case 'not-supported':
            statusElement.innerHTML = '<i class="fas fa-ban" style="color: #9ca3af;"></i>';
            btnElement.title = 'Notifications non supportées';
            btnElement.disabled = true;
            break;
    }
}

// Afficher une invite pour les notifications
function showNotificationPrompt() {
    const prompt = document.createElement('div');
    prompt.className = 'notification-prompt';
    prompt.innerHTML = `
        <div class="prompt-content">
            <div class="prompt-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="prompt-text">
                <h4>Restez informé !</h4>
                <p>Activez les notifications pour recevoir les nouvelles annonces et devoirs directement sur votre téléphone.</p>
            </div>
            <div class="prompt-actions">
                <button class="btn btn-primary btn-sm" onclick="acceptNotifications()">
                    <i class="fas fa-check"></i> Activer
                </button>
                <button class="btn btn-secondary btn-sm" onclick="dismissNotificationPrompt()">
                    <i class="fas fa-times"></i> Plus tard
                </button>
            </div>
        </div>
    `;
    
    // Styles pour l'invite
    prompt.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        border: 1px solid rgba(59, 130, 246, 0.2);
        z-index: 1000;
        max-width: 350px;
        animation: slideInUp 0.3s ease;
    `;
    
    document.body.appendChild(prompt);
    
    // Auto-masquer après 10 secondes
    setTimeout(() => {
        if (prompt.parentElement) {
            dismissNotificationPrompt();
        }
    }, 10000);
}

// Accepter les notifications
async function acceptNotifications() {
    const granted = await window.eduNotifications.requestPermission();
    if (granted) {
        showNotificationSuccess('Notifications activées ! Vous recevrez maintenant les nouvelles annonces et devoirs. 🎉');
    } else {
        showNotificationError('Impossible d\'activer les notifications. Vérifiez les paramètres de votre navigateur.');
    }
    dismissNotificationPrompt();
    updateNotificationStatus();
}

// Masquer l'invite
function dismissNotificationPrompt() {
    const prompt = document.querySelector('.notification-prompt');
    if (prompt) {
        prompt.style.animation = 'slideOutDown 0.3s ease';
        setTimeout(() => prompt.remove(), 300);
    }
}

// Fonctions utilitaires pour les messages
function showNotificationSuccess(message) {
    showNotificationMessage(message, 'success');
}

function showNotificationError(message) {
    showNotificationMessage(message, 'error');
}

function showNotificationMessage(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification-message notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#22c55e' : '#ef4444'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes slideOutDown {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(100px); opacity: 0; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100px); opacity: 0; }
    }
    
    .prompt-content {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .prompt-icon {
        text-align: center;
        font-size: 2rem;
        color: #3b82f6;
    }
    
    .prompt-text h4 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
        font-size: 1.2rem;
        font-weight: 700;
    }
    
    .prompt-text p {
        margin: 0;
        color: #6b7280;
        line-height: 1.5;
    }
    
    .prompt-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .notification-status {
        margin-left: 0.5rem;
    }
`;
document.head.appendChild(style);

// Rendre les fonctions globales
window.acceptNotifications = acceptNotifications;
window.dismissNotificationPrompt = dismissNotificationPrompt;