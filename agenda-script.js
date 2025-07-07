// Script pour l'agenda complet
document.addEventListener('DOMContentLoaded', function() {
    initializeAgenda();
    updateCurrentTime();
    
    // Mettre à jour l'heure toutes les minutes
    setInterval(updateCurrentTime, 60000);
});

function initializeAgenda() {
    // Animation d'entrée pour les colonnes de jours
    const dayColumns = document.querySelectorAll('.day-column');
    dayColumns.forEach((column, index) => {
        column.style.opacity = '0';
        column.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            column.style.transition = 'all 0.8s ease';
            column.style.opacity = '1';
            column.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animation pour les cours
    const courseItems = document.querySelectorAll('.agenda-course-item');
    courseItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 500 + (index * 50));
    });
    
    // Faire défiler vers le jour actuel
    scrollToCurrentDay();
}

function updateCurrentTime() {
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
    
    // Mettre à jour les statuts des cours dans l'agenda
    updateAgendaCourseStatuses(currentTime);
}

function updateAgendaCourseStatuses(currentTime) {
    const courseItems = document.querySelectorAll('.agenda-course-item');
    
    courseItems.forEach(item => {
        const timeSlot = item.querySelector('.course-time-slot');
        if (!timeSlot) return;
        
        const timeText = timeSlot.textContent.trim();
        const times = timeText.match(/(\d{2}:\d{2})/g);
        
        if (times && times.length >= 2) {
            const startTime = times[0];
            const endTime = times[1];
            
            // Vérifier si c'est aujourd'hui
            const dayColumn = item.closest('.day-column');
            const isToday = dayColumn && dayColumn.classList.contains('current-day');
            
            if (isToday) {
                // Supprimer les anciennes classes de statut
                item.classList.remove('current', 'upcoming', 'past');
                
                // Supprimer l'ancien indicateur live
                const oldLiveIndicator = item.querySelector('.live-indicator');
                if (oldLiveIndicator) {
                    oldLiveIndicator.remove();
                }
                
                if (currentTime >= startTime && currentTime <= endTime) {
                    // Cours en cours
                    item.classList.add('current');
                    
                    // Ajouter l'indicateur live
                    const liveIndicator = document.createElement('div');
                    liveIndicator.className = 'live-indicator';
                    liveIndicator.innerHTML = '<i class="fas fa-circle"></i>';
                    item.appendChild(liveIndicator);
                    
                } else if (currentTime < startTime) {
                    // Cours à venir
                    item.classList.add('upcoming');
                } else {
                    // Cours terminé
                    item.classList.add('past');
                }
            }
        }
    });
}

function scrollToCurrentDay() {
    const currentDayColumn = document.querySelector('.day-column.current-day');
    if (currentDayColumn) {
        setTimeout(() => {
            currentDayColumn.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }, 1000);
    }
}

function printSchedule() {
    // Préparer la page pour l'impression
    const originalTitle = document.title;
    document.title = 'Emploi du temps - ' + originalTitle;
    
    // Masquer les éléments non nécessaires pour l'impression
    const elementsToHide = document.querySelectorAll('.header-navigation, .header-actions, .schedule-summary');
    elementsToHide.forEach(element => {
        element.style.display = 'none';
    });
    
    // Lancer l'impression
    window.print();
    
    // Restaurer l'affichage après l'impression
    setTimeout(() => {
        document.title = originalTitle;
        elementsToHide.forEach(element => {
            element.style.display = '';
        });
    }, 1000);
}

// Fonction pour afficher les détails d'un cours au clic
function showCourseDetails(courseElement) {
    const subject = courseElement.querySelector('.course-subject-name').textContent;
    const timeSlot = courseElement.querySelector('.course-time-slot').textContent;
    const room = courseElement.querySelector('.course-room-mini').textContent.replace(/.*\s/, '');
    const teacher = courseElement.querySelector('.course-teacher-mini').textContent.replace(/.*\s/, '');
    
    const modal = document.createElement('div');
    modal.className = 'course-detail-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>${subject}</h3>
                <button class="modal-close" onclick="this.closest('.course-detail-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="course-detail-item">
                    <i class="fas fa-clock"></i>
                    <span>Horaire: ${timeSlot}</span>
                </div>
                <div class="course-detail-item">
                    <i class="fas fa-door-open"></i>
                    <span>Salle: ${room}</span>
                </div>
                <div class="course-detail-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Professeur: ${teacher}</span>
                </div>
            </div>
        </div>
    `;
    
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    document.body.appendChild(modal);
}

// Ajouter les événements de clic sur les cours
document.addEventListener('click', function(e) {
    const courseItem = e.target.closest('.agenda-course-item');
    if (courseItem) {
        showCourseDetails(courseItem);
    }
});

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // P pour imprimer
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        printSchedule();
    }
    
    // Échap pour fermer les modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.course-detail-modal');
        modals.forEach(modal => modal.remove());
    }
});

// Styles pour le modal des détails de cours
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 90%;
        max-height: 80vh;
        overflow: hidden;
        animation: modalSlideIn 0.3s ease;
    }
    
    .modal-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #3b82f6 0%, #22c55e 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: background 0.3s ease;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .modal-body {
        padding: 2rem;
    }
    
    .course-detail-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 1.1rem;
    }
    
    .course-detail-item:last-child {
        border-bottom: none;
    }
    
    .course-detail-item i {
        color: #3b82f6;
        width: 20px;
        text-align: center;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
`;
document.head.appendChild(modalStyles);