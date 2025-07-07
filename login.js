function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Fonction pour gérer les erreurs de connexion
function handleLoginError(errorType, errorMessage) {
    switch(errorType) {
        case 'ldap_connection':
            showNotification(errorMessage, 'error', true);
            break;
        case 'invalid_credentials':
            showCredentialsError(errorMessage);
            break;
        case 'no_group':
            showNotification(errorMessage, 'warning', false);
            break;
        default:
            showNotification(errorMessage, 'error', true);
    }
}

// Fonction pour afficher l'erreur d'identifiants
function showCredentialsError(message) {
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = `
        <div class="credentials-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span>${message}</span>
        </div>
    `;
    errorContainer.style.display = 'block';
    
    // Ajouter une classe d'erreur aux champs
    document.getElementById('username').classList.add('error');
    document.getElementById('password').classList.add('error');
    
    // Supprimer l'erreur quand l'utilisateur commence à taper
    document.getElementById('username').addEventListener('input', clearCredentialsError);
    document.getElementById('password').addEventListener('input', clearCredentialsError);
}

// Fonction pour effacer l'erreur d'identifiants
function clearCredentialsError() {
    const errorContainer = document.getElementById('error-container');
    errorContainer.style.display = 'none';
    
    document.getElementById('username').classList.remove('error');
    document.getElementById('password').classList.remove('error');
    
    // Supprimer les event listeners
    document.getElementById('username').removeEventListener('input', clearCredentialsError);
    document.getElementById('password').removeEventListener('input', clearCredentialsError);
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info', persistent = false) {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        ${!persistent ? '<button class="notification-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>' : ''}
    `;

    // Styles pour la notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        z-index: 1001;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-width: 300px;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
    `;

    document.body.appendChild(notification);

    // Suppression automatique après 8 secondes pour les notifications non persistantes
    if (!persistent) {
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 8000);
    }
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        success: '#22c55e',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    return colors[type] || '#3b82f6';
}

// Animation du bouton lors de la soumission améliorée
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loginBtn = document.getElementById('loginBtn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Effacer les erreurs précédentes
            clearCredentialsError();
            
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';
            
            // Simulation d'un délai de connexion pour l'effet visuel
            setTimeout(() => {
                // Le formulaire continuera sa soumission normale
            }, 500);
        });
    }
});

// Animation des inputs au focus améliorée
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.parentElement.classList.add('focused');
        
        // Effet de vibration subtile sur mobile
        if (window.navigator && window.navigator.vibrate) {
            window.navigator.vibrate(50);
        }
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.parentElement.classList.remove('focused');
    });
    
    // Animation lors de la saisie
    input.addEventListener('input', function() {
        if (this.value.length > 0) {
            this.classList.add('has-content');
        } else {
            this.classList.remove('has-content');
        }
    });
});

// Effet de frappe pour le titre amélioré
document.addEventListener('DOMContentLoaded', function() {
    const logo = document.querySelector('.logo');
    const subtitle = document.querySelector('.subtitle');
    const title = document.querySelector('h2');
    
    // Animation séquentielle des éléments
    if (logo) {
        logo.style.opacity = '0';
        logo.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            logo.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            logo.style.opacity = '1';
            logo.style.transform = 'translateY(0)';
        }, 200);
    }
    
    if (subtitle) {
        subtitle.style.opacity = '0';
        subtitle.style.transform = 'translateY(-15px)';
        setTimeout(() => {
            subtitle.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            subtitle.style.opacity = '1';
            subtitle.style.transform = 'translateY(0)';
        }, 400);
    }
    
    if (title) {
        title.style.opacity = '0';
        title.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            title.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            title.style.opacity = '1';
            title.style.transform = 'translateY(0)';
        }, 600);
    }
});

// Validation en temps réel des champs
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    
    function validateForm() {
        const isValid = usernameInput.value.trim().length > 0 && passwordInput.value.length > 0;
        
        if (isValid) {
            loginBtn.style.opacity = '1';
            loginBtn.style.transform = 'translateY(0)';
        } else {
            loginBtn.style.opacity = '0.7';
            loginBtn.style.transform = 'translateY(2px)';
        }
    }
    
    if (usernameInput && passwordInput && loginBtn) {
        usernameInput.addEventListener('input', validateForm);
        passwordInput.addEventListener('input', validateForm);
        
        // État initial
        validateForm();
    }
});

// Gestion du mode sombre automatique (si supporté par le système)
if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.body.style.background = 'linear-gradient(135deg, #1a237e 0%, #4a148c 100%)';
}