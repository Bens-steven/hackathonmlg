// Script spécifique pour la gestion d'emploi du temps

document.addEventListener('DOMContentLoaded', function() {
    initializeScheduleForm();
    initializeNotifications();
    initializeTableAnimations();
    initializeTodayPreview();
});

function initializeScheduleForm() {
    const form = document.getElementById('scheduleForm');
    const matiereSelect = document.getElementById('matiere');
    const professeurDisplay = document.getElementById('professeur_display');
    const heureDebutInput = document.getElementById('heure_debut');
    const heureFinInput = document.getElementById('heure_fin');

    // Validation des heures en temps réel
    if (heureDebutInput && heureFinInput) {
        heureDebutInput.addEventListener('change', validateTimeRange);
        heureFinInput.addEventListener('change', validateTimeRange);
    }

    // Mise à jour du professeur quand la matière change
    if (matiereSelect && professeurDisplay) {
        matiereSelect.addEventListener('change', function() {
            if (this.value) {
                // Simulation de la récupération du professeur
                // En réalité, cela se fait côté serveur via LDAP
                professeurDisplay.value = 'Chargement...';
                
                // Effet visuel de chargement
                setTimeout(() => {
                    professeurDisplay.value = 'Professeur assigné automatiquement';
                }, 500);
            } else {
                professeurDisplay.value = '';
            }
        });
    }

    // Animation de soumission du formulaire
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('.btn-add-course');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout en cours...';
                submitBtn.disabled = true;
                form.classList.add('loading');
            }
        });
    }
}

function validateTimeRange() {
    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;
    
    if (heureDebut && heureFin) {
        if (heureDebut >= heureFin) {
            showScheduleNotification('L\'heure de début doit être antérieure à l\'heure de fin', 'warning');
            document.getElementById('heure_fin').focus();
            return false;
        }
        
        // Vérifier la durée minimale (30 minutes)
        const debut = new Date('2000-01-01 ' + heureDebut);
        const fin = new Date('2000-01-01 ' + heureFin);
        const dureeMinutes = (fin - debut) / (1000 * 60);
        
        if (dureeMinutes < 30) {
            showScheduleNotification('La durée du cours doit être d\'au moins 30 minutes', 'warning');
            return false;
        }
        
        if (dureeMinutes > 240) {
            showScheduleNotification('La durée du cours ne peut pas dépasser 4 heures', 'warning');
            return false;
        }
    }
    
    return true;
}

function resetForm() {
    const form = document.getElementById('scheduleForm');
    if (form) {
        form.reset();
        document.getElementById('professeur_display').value = '';
        
        // Animation de reset
        form.style.opacity = '0.5';
        setTimeout(() => {
            form.style.opacity = '1';
        }, 200);
        
        showScheduleNotification('Formulaire réinitialisé', 'info');
    }
}

function initializeNotifications() {
    // Auto-masquer les notifications après 5 secondes
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    });
}

function showScheduleNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const iconMap = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-suppression après 4 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 4000);
}

function initializeTableAnimations() {
    // Animation au survol des lignes du tableau
    const tableRows = document.querySelectorAll('.schedule-row');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.zIndex = '10';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.zIndex = '1';
        });
    });
    
    // Animation des badges au clic
    const badges = document.querySelectorAll('.day-badge, .subject-name, .room-number');
    badges.forEach(badge => {
        badge.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
}

function initializeTodayPreview() {
    // Animation des cours du jour
    const todayCourses = document.querySelectorAll('.course-preview-today');
    todayCourses.forEach((course, index) => {
        course.style.opacity = '0';
        course.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            course.style.transition = 'all 0.5s ease';
            course.style.opacity = '1';
            course.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Fonction pour afficher/masquer le tableau des cours
function toggleScheduleTable() {
    const tableCard = document.getElementById('scheduleTableCard');
    
    if (tableCard.style.display === 'none' || tableCard.style.display === '') {
        // Afficher le tableau
        tableCard.style.display = 'block';
        tableCard.classList.remove('hide');
        tableCard.classList.add('show');
        
        // Scroll vers le tableau
        setTimeout(() => {
            tableCard.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 100);
        
        showScheduleNotification('Tableau des cours affiché', 'info');
    } else {
        // Masquer le tableau
        tableCard.classList.remove('show');
        tableCard.classList.add('hide');
        
        setTimeout(() => {
            tableCard.style.display = 'none';
        }, 500);
        
        showScheduleNotification('Tableau des cours masqué', 'info');
    }
}

// Fonction pour confirmer la suppression avec modal
function confirmDelete(courseId, className, courseName) {
    const modal = document.createElement('div');
    modal.className = 'delete-modal';
    modal.innerHTML = `
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirmer la suppression</h3>
            </div>
            <div class="delete-modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le cours de <strong>${courseName}</strong> ?</p>
                <p class="warning-text">Cette action est irréversible.</p>
            </div>
            <div class="delete-modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <a href="supprimer_edt.php?id=${courseId}&classe=${encodeURIComponent(className)}" 
                   class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    const modal = document.querySelector('.delete-modal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl + N pour nouveau cours
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        document.getElementById('jour').focus();
        showScheduleNotification('Mode ajout de cours activé', 'info');
    }
    
    // Echap pour fermer les modals et notifications
    if (e.key === 'Escape') {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => notification.remove());
        closeDeleteModal();
    }
    
    // Ctrl + R pour réinitialiser le formulaire
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        resetForm();
    }
    
    // Ctrl + T pour afficher/masquer le tableau
    if (e.ctrlKey && e.key === 't') {
        e.preventDefault();
        toggleScheduleTable();
    }
});

// Validation en temps réel du formulaire
document.addEventListener('input', function(e) {
    if (e.target.matches('#salle')) {
        // Formatage automatique du nom de salle
        let value = e.target.value.toUpperCase();
        // Supprimer les caractères non alphanumériques sauf les espaces et tirets
        value = value.replace(/[^A-Z0-9\s\-]/g, '');
        e.target.value = value;
    }
});

// Amélioration de l'UX avec des tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.getAttribute('data-tooltip');
    tooltip.style.cssText = `
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8rem;
        z-index: 1000;
        pointer-events: none;
        animation: fadeIn 0.2s ease;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Initialiser les tooltips au chargement
document.addEventListener('DOMContentLoaded', initializeTooltips);

// Mise à jour de l'heure en temps réel dans l'aperçu du jour
function updateCurrentTime() {
    const timeElements = document.querySelectorAll('.current-time');
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    timeElements.forEach(element => {
        element.textContent = timeString;
    });
}

// Mettre à jour l'heure toutes les minutes
setInterval(updateCurrentTime, 60000);
updateCurrentTime(); // Appel initial