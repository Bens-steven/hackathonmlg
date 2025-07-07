// Fonctions globales pour les modals (DOIVENT être en dehors de DOMContentLoaded)
function showSubmitModal(devoirId, titre) {
    const modal = document.getElementById('submitModal');
    const titleElem = document.getElementById('modal-title');
    const idInput = document.getElementById('modal-devoir-id');

    if (modal && titleElem && idInput) {
        titleElem.textContent = "Rendre le devoir : " + titre;
        idInput.value = devoirId;
        modal.style.display = 'flex';
        
        // Adapter le modal pour mobile
        if (window.innerWidth <= 768) {
            modal.classList.add('mobile-modal');
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.add('mobile-modal-content');
            }
        }
    } else {
        console.error('Erreur : le modal de rendu est introuvable.');
    }
}

function closeSubmitModal() {
    const modal = document.getElementById('submitModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('mobile-modal');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.classList.remove('mobile-modal-content');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let editMode = false;
    let isMobile = window.innerWidth <= 768;

    // Initialisation
    initializeMobileFeatures();
    initializePhotoUpload();
    initializeAnimations();
    initializeSubjectColors();
    initializeTouchGestures();

    // Gestion du redimensionnement
    window.addEventListener('resize', function() {
        const wasMobile = isMobile;
        isMobile = window.innerWidth <= 768;
        
        if (wasMobile !== isMobile) {
            initializeMobileFeatures();
        }
    });

    // Initialisation des fonctionnalités mobiles
    function initializeMobileFeatures() {
        if (isMobile) {
            createMobileNavigation();
            createMobileHeader();
            adaptCardsForMobile();
            initializeSwipeGestures();
        } else {
            removeMobileFeatures();
        }
    }

    // Créer la navigation mobile
    function createMobileNavigation() {
        let mobileNav = document.getElementById('mobileNav');
        
        if (!mobileNav) {
            mobileNav = document.createElement('nav');
            mobileNav.id = 'mobileNav';
            mobileNav.className = 'mobile-nav';
            
            const navContent = document.createElement('div');
            navContent.className = 'mobile-nav-content';
            
            const navItems = [
                { icon: 'fas fa-home', text: 'Accueil', href: '#', active: true },
                { icon: 'fas fa-calendar-day', text: 'Emploi du temps', href: 'emploi-du-temps-complet.php' },
                { icon: 'fas fa-tasks', text: 'Devoirs', href: 'tous_devoirs.php' },
                { icon: 'fas fa-chart-line', text: 'Notes', href: 'toutes_notes.php' },
                { icon: 'fas fa-bullhorn', text: 'Annonces', href: 'annonces.php', badge: true }
            ];
            
            navItems.forEach(item => {
                const navItem = document.createElement('a');
                navItem.href = item.href;
                navItem.className = `mobile-nav-item ${item.active ? 'active' : ''}`;
                
                navItem.innerHTML = `
                    <i class="${item.icon}"></i>
                    <span>${item.text}</span>
                    ${item.badge ? '<span class="mobile-nav-badge">3</span>' : ''}
                `;
                
                navContent.appendChild(navItem);
            });
            
            mobileNav.appendChild(navContent);
            document.body.appendChild(mobileNav);
        }
    }

    // Créer le header mobile
    function createMobileHeader() {
        let mobileHeader = document.getElementById('mobileHeader');
        
        if (!mobileHeader) {
            mobileHeader = document.createElement('header');
            mobileHeader.id = 'mobileHeader';
            mobileHeader.className = 'mobile-header';
            
            const headerContent = document.createElement('div');
            headerContent.className = 'mobile-header-content';
            
            headerContent.innerHTML = `
                <div class="mobile-header-title">
                    <i class="fas fa-user-graduate"></i>
                    Mon Profil
                </div>
                <div class="mobile-header-actions">
                    <button class="mobile-header-btn" onclick="toggleEditMode()">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="logout.php" class="mobile-header-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            `;
            
            mobileHeader.appendChild(headerContent);
            document.body.appendChild(mobileHeader);
        }
    }

    // Adapter les cartes pour mobile
    function adaptCardsForMobile() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            if (!card.classList.contains('mobile-adapted')) {
                card.classList.add('mobile-card');
                card.classList.add('mobile-adapted');
                
                // Adapter le contenu spécifique
                adaptScheduleForMobile(card);
                adaptHomeworkForMobile(card);
                adaptGradesForMobile(card);
                adaptPhotoForMobile(card);
            }
        });
    }

    // Adapter l'emploi du temps pour mobile
    function adaptScheduleForMobile(card) {
        if (card.classList.contains('schedule-card')) {
            const courseItems = card.querySelectorAll('.course-item');
            courseItems.forEach(item => {
                if (!item.classList.contains('mobile-adapted')) {
                    item.classList.add('mobile-course-item');
                    item.classList.add('mobile-adapted');
                    
                    // Réorganiser le contenu
                    const timeElement = item.querySelector('.course-time');
                    const detailsElement = item.querySelector('.course-details');
                    
                    if (timeElement) {
                        timeElement.classList.add('mobile-course-time');
                        const timeRange = timeElement.querySelector('.time-range');
                        const status = timeElement.querySelector('.course-status');
                        
                        if (timeRange) timeRange.classList.add('mobile-course-time-range');
                        if (status) status.classList.add('mobile-course-status');
                    }
                    
                    if (detailsElement) {
                        detailsElement.classList.add('mobile-course-details');
                        const subject = detailsElement.querySelector('.course-subject');
                        const info = detailsElement.querySelector('.course-info');
                        
                        if (subject) subject.classList.add('mobile-course-subject');
                        if (info) info.classList.add('mobile-course-info');
                    }
                }
            });
        }
    }

    // Adapter les devoirs pour mobile
    function adaptHomeworkForMobile(card) {
        if (card.classList.contains('homework-card')) {
            const homeworkItems = card.querySelectorAll('.homework-item');
            homeworkItems.forEach(item => {
                if (!item.classList.contains('mobile-adapted')) {
                    item.classList.add('mobile-homework-item');
                    item.classList.add('mobile-adapted');
                    
                    // Créer la structure mobile
                    const title = item.querySelector('.homework-title');
                    const status = item.querySelector('.status-badge');
                    const content = item.querySelector('.homework-content');
                    const dates = item.querySelector('.homework-dates');
                    const actions = item.querySelector('.homework-actions');
                    
                    if (title || status) {
                        const header = document.createElement('div');
                        header.className = 'mobile-homework-header';
                        
                        if (title) {
                            title.classList.add('mobile-homework-title');
                            header.appendChild(title);
                        }
                        
                        if (status) {
                            const statusContainer = document.createElement('div');
                            statusContainer.className = 'mobile-homework-status';
                            statusContainer.appendChild(status);
                            header.appendChild(statusContainer);
                        }
                        
                        item.insertBefore(header, item.firstChild);
                    }
                    
                    if (content || dates || actions) {
                        const body = document.createElement('div');
                        body.className = 'mobile-homework-body';
                        
                        if (content) {
                            content.classList.add('mobile-homework-content');
                            body.appendChild(content);
                        }
                        
                        if (dates) {
                            dates.classList.add('mobile-homework-meta');
                            const dateItems = dates.querySelectorAll('.homework-date');
                            dateItems.forEach(date => date.classList.add('mobile-homework-date'));
                            body.appendChild(dates);
                        }
                        
                        if (actions) {
                            actions.classList.add('mobile-homework-actions');
                            const buttons = actions.querySelectorAll('.btn');
                            buttons.forEach(btn => {
                                btn.classList.add('mobile-btn');
                                if (btn.classList.contains('btn-sm')) {
                                    btn.classList.add('mobile-btn-sm');
                                }
                            });
                            body.appendChild(actions);
                        }
                        
                        item.appendChild(body);
                    }
                }
            });
        }
    }

    // Adapter les notes pour mobile
    function adaptGradesForMobile(card) {
        if (card.classList.contains('grades-card')) {
            const gradeItems = card.querySelectorAll('.grade-item');
            gradeItems.forEach(item => {
                if (!item.classList.contains('mobile-adapted')) {
                    item.classList.add('mobile-grade-item');
                    item.classList.add('mobile-adapted');
                    
                    const subjectInfo = item.querySelector('.subject-info');
                    const gradeValue = item.querySelector('.grade-value');
                    
                    if (subjectInfo) {
                        subjectInfo.classList.add('mobile-grade-info');
                        const subjectName = subjectInfo.querySelector('.subject-name');
                        const gradeDate = subjectInfo.querySelector('.grade-date');
                        
                        if (subjectName) subjectName.classList.add('mobile-grade-subject');
                        if (gradeDate) gradeDate.classList.add('mobile-grade-date');
                    }
                    
                    if (gradeValue) {
                        gradeValue.classList.add('mobile-grade-value');
                    }
                }
            });
        }
    }

    // Adapter la photo pour mobile
    function adaptPhotoForMobile(card) {
        if (card.classList.contains('photo-card')) {
            const photoContainer = card.querySelector('.photo-upload-container');
            if (photoContainer && !photoContainer.classList.contains('mobile-adapted')) {
                photoContainer.classList.add('mobile-photo-container');
                photoContainer.classList.add('mobile-adapted');
                
                const currentPhoto = photoContainer.querySelector('.current-photo');
                const photoOverlay = photoContainer.querySelector('.photo-overlay');
                
                if (currentPhoto) currentPhoto.classList.add('mobile-photo-current');
                if (photoOverlay) photoOverlay.classList.add('mobile-photo-overlay');
                
                const buttons = photoContainer.querySelectorAll('.btn');
                buttons.forEach(btn => {
                    btn.classList.add('mobile-btn');
                    if (!btn.classList.contains('btn-sm')) {
                        btn.classList.add('mobile-btn-full');
                    }
                });
            }
        }
    }

    // Supprimer les fonctionnalités mobiles
    function removeMobileFeatures() {
        const mobileNav = document.getElementById('mobileNav');
        const mobileHeader = document.getElementById('mobileHeader');
        
        if (mobileNav) mobileNav.remove();
        if (mobileHeader) mobileHeader.remove();
        
        // Supprimer les classes mobiles
        document.querySelectorAll('.mobile-adapted').forEach(element => {
            element.classList.remove('mobile-adapted');
            // Supprimer toutes les classes mobile-*
            const classes = Array.from(element.classList);
            classes.forEach(className => {
                if (className.startsWith('mobile-')) {
                    element.classList.remove(className);
                }
            });
        });
    }

    // Initialiser les gestes tactiles
    function initializeTouchGestures() {
        if (!isMobile) return;
        
        let startX, startY, currentX, currentY;
        
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });
        
        document.addEventListener('touchmove', function(e) {
            if (!startX || !startY) return;
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
        }, { passive: true });
        
        document.addEventListener('touchend', function(e) {
            if (!startX || !startY || !currentX || !currentY) return;
            
            const diffX = startX - currentX;
            const diffY = startY - currentY;
            
            // Swipe horizontal
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    // Swipe left
                    handleSwipeLeft();
                } else {
                    // Swipe right
                    handleSwipeRight();
                }
            }
            
            // Reset
            startX = startY = currentX = currentY = null;
        }, { passive: true });
    }

    // Initialiser les gestes de swipe
    function initializeSwipeGestures() {
        if (!isMobile) return;
        
        const cards = document.querySelectorAll('.mobile-card');
        cards.forEach(card => {
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            card.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
                card.style.transition = 'none';
            }, { passive: true });
            
            card.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                
                currentX = e.touches[0].clientX;
                const diffX = currentX - startX;
                
                // Limiter le mouvement
                if (Math.abs(diffX) < 100) {
                    card.style.transform = `translateX(${diffX}px)`;
                }
            }, { passive: true });
            
            card.addEventListener('touchend', function(e) {
                if (!isDragging) return;
                
                isDragging = false;
                card.style.transition = 'transform 0.3s ease';
                card.style.transform = 'translateX(0)';
                
                const diffX = currentX - startX;
                
                // Actions basées sur le swipe
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        // Swipe right - action positive
                        handleCardSwipeRight(card);
                    } else {
                        // Swipe left - action négative
                        handleCardSwipeLeft(card);
                    }
                }
            }, { passive: true });
        });
    }

    // Gestion des swipes
    function handleSwipeLeft() {
        // Navigation vers la page suivante
        const navItems = document.querySelectorAll('.mobile-nav-item');
        const activeItem = document.querySelector('.mobile-nav-item.active');
        const currentIndex = Array.from(navItems).indexOf(activeItem);
        
        if (currentIndex < navItems.length - 1) {
            navItems[currentIndex].classList.remove('active');
            navItems[currentIndex + 1].classList.add('active');
        }
    }

    function handleSwipeRight() {
        // Navigation vers la page précédente
        const navItems = document.querySelectorAll('.mobile-nav-item');
        const activeItem = document.querySelector('.mobile-nav-item.active');
        const currentIndex = Array.from(navItems).indexOf(activeItem);
        
        if (currentIndex > 0) {
            navItems[currentIndex].classList.remove('active');
            navItems[currentIndex - 1].classList.add('active');
        }
    }

    function handleCardSwipeRight(card) {
        // Action positive sur la carte (ex: marquer comme lu)
        if (card.classList.contains('homework-card')) {
            showNotification('Devoir marqué comme prioritaire', 'success');
        }
    }

    function handleCardSwipeLeft(card) {
        // Action négative sur la carte (ex: masquer temporairement)
        if (card.classList.contains('homework-card')) {
            card.style.opacity = '0.5';
            showNotification('Carte masquée temporairement', 'info');
            
            setTimeout(() => {
                card.style.opacity = '1';
            }, 2000);
        }
    }

    // Gestion de l'upload de photo (adaptée pour mobile)
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
                            
                            // Adapter pour mobile
                            if (isMobile) {
                                uploadBtn.classList.add('mobile-btn', 'mobile-btn-full');
                            }
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
                if (element.classList.contains('course-item') || element.classList.contains('mobile-course-item')) {
                    element.style.borderLeftColor = color;
                }
                
                // Pour les éléments d'information
                if (element.classList.contains('subject-info')) {
                    element.style.setProperty('--subject-color', color);
                }
            }
        });

        // Appliquer les couleurs aux matières dans les notes
        document.querySelectorAll('.subject-name, .mobile-grade-subject').forEach(element => {
            const subject = element.textContent.trim();
            const color = subjectColors[subject];
            
            if (color) {
                element.style.color = color;
                element.style.fontWeight = '700';
            }
        });
    }

    // Mode édition (adapté pour mobile)
    function toggleEditMode() {
        editMode = !editMode;
        const editBtn = isMobile ? 
            document.querySelector('.mobile-header-btn i.fa-edit')?.parentElement :
            document.querySelector('.btn-primary');

        if (editMode) {
            if (editBtn) {
                if (isMobile) {
                    editBtn.innerHTML = '<i class="fas fa-save"></i>';
                } else {
                    editBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
                }
                editBtn.classList.add('editing');
            }
            enableEditMode();
            showNotification('Mode édition activé', 'info');
        } else {
            if (editBtn) {
                if (isMobile) {
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                } else {
                    editBtn.innerHTML = '<i class="fas fa-edit"></i> Modifier';
                }
                editBtn.classList.remove('editing');
            }
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

    // Animations et interactions (optimisées pour mobile)
    function initializeAnimations() {
        // Animation des cartes au scroll (réduite sur mobile)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: isMobile ? 0.05 : 0.1
        });

        document.querySelectorAll('.card, .mobile-card').forEach(card => {
            observer.observe(card);
        });

        // Animation des éléments d'information (simplifiée sur mobile)
        document.querySelectorAll('.info-item-modern').forEach((item, index) => {
            if (!isMobile) {
                item.style.animationDelay = `${index * 0.1}s`;
                item.classList.add('fadeInUp');
            }
        });
    }

    // Système de notifications (adapté pour mobile)
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Position adaptée pour mobile
        const isTopPosition = isMobile;
        
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
            ${isTopPosition ? 'top: 80px' : 'top: 20px'};
            ${isTopPosition ? 'left: 10px; right: 10px' : 'right: 20px'};
            background: ${getNotificationColor(type)};
            color: white;
            padding: ${isMobile ? '0.75rem 1rem' : '1rem 1.5rem'};
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            ${isTopPosition ? 'width: auto' : 'min-width: 300px'};
            animation: slideInRight 0.3s ease;
            font-size: ${isMobile ? '0.9rem' : '1rem'};
        `;

        document.body.appendChild(notification);

        // Suppression automatique après 4 secondes sur mobile, 5 sur desktop
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, isMobile ? 4000 : 5000);
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

    // Raccourcis clavier (désactivés sur mobile)
    if (!isMobile) {
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
    }

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

    // Optimisations pour les performances mobiles
    if (isMobile) {
        // Réduire la fréquence des événements de scroll
        let ticking = false;
        function updateOnScroll() {
            // Logique de scroll optimisée
            ticking = false;
        }
        
        document.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateOnScroll);
                ticking = true;
            }
        }, { passive: true });
        
        // Désactiver les animations coûteuses sur mobile
        const style = document.createElement('style');
        style.textContent = `
            @media (max-width: 768px) {
                * {
                    animation-duration: 0.2s !important;
                    transition-duration: 0.2s !important;
                }
                
                .card:hover,
                .mobile-card:hover {
                    transform: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Rendre les fonctions globales
    window.toggleEditMode = toggleEditMode;
    window.showNotification = showNotification;
    window.isMobile = () => isMobile;
});

// Gestion de l'orientation mobile
window.addEventListener('orientationchange', function() {
    setTimeout(() => {
        // Recalculer les dimensions après changement d'orientation
        const event = new Event('resize');
        window.dispatchEvent(event);
    }, 100);
});

// Prévenir le zoom sur les inputs sur iOS
document.addEventListener('touchstart', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        e.target.style.fontSize = '16px';
    }
});