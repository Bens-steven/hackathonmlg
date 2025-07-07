// Fonctions globales pour les modals (DOIVENT être en dehors de DOMContentLoaded)
function showSubmitModal(devoirId, titre) {
    const modal = document.getElementById('submitModal');
    const titleElem = document.getElementById('modal-title');
    const idInput = document.getElementById('modal-devoir-id');

    if (modal && titleElem && idInput) {
        titleElem.textContent = "Rendre le devoir : " + titre;
        idInput.value = devoirId;
        modal.style.display = 'flex';
    } else {
        console.error('Erreur : le modal de rendu est introuvable.');
    }
}

function closeSubmitModal() {
    const modal = document.getElementById('submitModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let editMode = false;

    // Initialisation au chargement de la page
    initializePhotoUpload();
    initializeAnimations();
    initializeSubjectColors();

    // Gestion de l'upload de photo
    function initializePhotoUpload() {
        const photoInput = document.getElementById('photoInput');
        const currentPhoto = document.getElementById('currentPhoto');
        const profileImage = document.getElementById('profileImage');
        const uploadBtn = document.getElementById('uploadBtn');

        if (photoInput) {
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Vérification du type de fichier
                    if (!file.type.match('image.*')) {
                        showNotification('Veuillez sélectionner un fichier image valide.', 'error');
                        return;
                    }

                    // Vérification de la taille (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('La taille du fichier ne doit pas dépasser 5MB.', 'error');
                        return;
                    }

                    // Prévisualisation de l'image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (currentPhoto) {
                            currentPhoto.src = e.target.result;
                        }
                        if (profileImage) {
                            profileImage.src = e.target.result;
                        }

                        // Afficher le bouton de confirmation
                        if (uploadBtn) {
                            uploadBtn.style.display = 'inline-flex';
                            uploadBtn.classList.add('pulse');
                        }
                    };
                    reader.readAsDataURL(file);

                    showNotification('Photo sélectionnée. Cliquez sur "Confirmer" pour sauvegarder.', 'success');
                }
            });
        }
    }

    // Initialisation des couleurs par matière
    function initializeSubjectColors() {
        const subjectColors = {
            'Mathématiques': '#3b82f6',
            'Math': '#3b82f6',
            'Français': '#22c55e',
            'Francais': '#22c55e',
            'Histoire': '#f59e0b',
            'Physique': '#8b5cf6',
            'Chimie': '#f97316',
            'Biologie': '#14b8a6',
            'Anglais': '#ec4899',
            'English': '#ec4899',
            'Sport': '#ef4444',
            'EPS': '#ef4444'
        };

        // Appliquer les couleurs aux éléments de cours
        document.querySelectorAll('[data-subject]').forEach(element => {
            const subject = element.getAttribute('data-subject');
            const color = subjectColors[subject];
            
            if (color) {
                // Pour les cours dans l'emploi du temps
                if (element.classList.contains('course-item')) {
                    element.style.borderLeftColor = color;
                }
                
                // Pour les éléments d'information
                if (element.classList.contains('subject-info')) {
                    const beforeElement = element.querySelector('::before');
                    element.style.setProperty('--subject-color', color);
                }
            }
        });

        // Appliquer les couleurs aux matières dans les notes
        document.querySelectorAll('.subject-name').forEach(element => {
            const subject = element.textContent.trim();
            const color = subjectColors[subject];
            
            if (color) {
                element.style.color = color;
                element.style.fontWeight = '700';
            }
        });
    }

    // Mode édition
    function toggleEditMode() {
        editMode = !editMode;
        const editBtn = document.querySelector('.btn-primary');

        if (editMode) {
            editBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
            editBtn.classList.add('editing');
            enableEditMode();
            showNotification('Mode édition activé', 'info');
        } else {
            editBtn.innerHTML = '<i class="fas fa-edit"></i> Modifier';
            editBtn.classList.remove('editing');
            disableEditMode();
            showNotification('Modifications sauvegardées', 'success');
        }
    }

    function enableEditMode() {
        // Rendre les éléments éditables
        const editableElements = document.querySelectorAll('.user-name, .username');
        editableElements.forEach(element => {
            element.contentEditable = true;
            element.classList.add('editable');
        });
    }

    function disableEditMode() {
        // Désactiver l'édition
        const editableElements = document.querySelectorAll('.editable');
        editableElements.forEach(element => {
            element.contentEditable = false;
            element.classList.remove('editable');
        });
    }

    // Animations et interactions
    function initializeAnimations() {
        // Animation des cartes au scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });

        // Animation des éléments d'information
        document.querySelectorAll('.info-item-modern').forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('fadeInUp');
        });
    }

    // Système de notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
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

    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl + E pour activer/désactiver le mode édition
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            toggleEditMode();
        }

        // Echap pour fermer les notifications
        if (e.key === 'Escape') {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => notification.remove());
        }
    });

    // Sauvegarde automatique en mode édition
    let saveTimeout;
    function autoSave() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            if (editMode) {
                showNotification('Sauvegarde automatique...', 'info');
                // Ici vous pourriez ajouter un appel AJAX pour sauvegarder
            }
        }, 2000);
    }

    // Écouter les changements en mode édition
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('editable')) {
            autoSave();
        }
    });

    // Animations CSS supplémentaires
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .fadeInUp {
            animation: fadeInUp 0.6s ease forwards;
        }

        .info-item-modern {
            opacity: 0;
            transform: translateY(20px);
        }

        .info-item-modern.fadeInUp {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);

    // Rendre la fonction toggleEditMode globale pour les boutons
    window.toggleEditMode = toggleEditMode;
    window.showNotification = showNotification;
});