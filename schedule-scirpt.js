// Script pour l'emploi du temps amélioré
document.addEventListener('DOMContentLoaded', function() {
    initializeSchedule();
    updateCurrentTime();
    
    // Mettre à jour l'heure toutes les minutes
    setInterval(updateCurrentTime, 60000);
});

function initializeSchedule() {
    // Animation d'entrée pour les cours
    const courseItems = document.querySelectorAll('.course-item');
    courseItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Mise en évidence du cours actuel
    highlightCurrentCourse();
}

function updateCurrentTime() {
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
    
    // Mettre à jour les statuts des cours
    updateCourseStatuses(currentTime);
}

function updateCourseStatuses(currentTime) {
    const courseItems = document.querySelectorAll('.course-item');
    
    courseItems.forEach(item => {
        const timeRange = item.querySelector('.time-range');
        if (!timeRange) return;
        
        const timeText = timeRange.textContent.trim();
        const times = timeText.match(/(\d{2}:\d{2})/g);
        
        if (times && times.length >= 2) {
            const startTime = times[0];
            const endTime = times[1];
            
            // Supprimer les anciennes classes de statut
            item.classList.remove('current', 'upcoming', 'past');
            
            const statusElement = item.querySelector('.course-status');
            const statusIcon = statusElement.querySelector('i');
            
            if (currentTime >= startTime && currentTime <= endTime) {
                // Cours en cours
                item.classList.add('current');
                statusElement.classList.remove('upcoming', 'past');
                statusElement.classList.add('current');
                statusElement.innerHTML = '<i class="fas fa-play-circle"></i> En cours';
            } else if (currentTime < startTime) {
                // Cours à venir
                item.classList.add('upcoming');
                statusElement.classList.remove('current', 'past');
                statusElement.classList.add('upcoming');
                statusElement.innerHTML = '<i class="fas fa-clock"></i> À venir';
            } else {
                // Cours terminé
                item.classList.add('past');
                statusElement.classList.remove('current', 'upcoming');
                statusElement.classList.add('past');
                statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Terminé';
            }
        }
    });
}

function highlightCurrentCourse() {
    const currentCourse = document.querySelector('.course-item.current');
    if (currentCourse) {
        // Faire défiler vers le cours actuel
        currentCourse.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // Ajouter un effet de pulsation
        currentCourse.style.animation = 'pulse-highlight 3s ease-in-out';
    }
}

// Fonction pour afficher une notification de changement de cours
function showCourseNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `course-notification course-notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-bell"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #3b82f6 0%, #22c55e 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Vérifier les changements de cours toutes les minutes
function checkCourseChanges() {
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
    
    const courseItems = document.querySelectorAll('.course-item');
    
    courseItems.forEach(item => {
        const timeRange = item.querySelector('.time-range');
        if (!timeRange) return;
        
        const timeText = timeRange.textContent.trim();
        const times = timeText.match(/(\d{2}:\d{2})/g);
        
        if (times && times.length >= 2) {
            const startTime = times[0];
            const endTime = times[1];
            const subject = item.querySelector('.course-subject').textContent;
            
            // Notification 5 minutes avant le début du cours
            const startMinutes = parseInt(startTime.split(':')[0]) * 60 + parseInt(startTime.split(':')[1]);
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            
            if (currentMinutes === startMinutes - 5) {
                showCourseNotification(`Le cours de ${subject} commence dans 5 minutes`, 'warning');
            }
            
            // Notification au début du cours
            if (currentTime === startTime) {
                showCourseNotification(`Le cours de ${subject} commence maintenant`, 'success');
            }
        }
    });
}

// Démarrer la vérification des changements de cours
setInterval(checkCourseChanges, 60000);

// Animation CSS pour la mise en évidence
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse-highlight {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        50% {
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
    }
`;
document.head.appendChild(style);