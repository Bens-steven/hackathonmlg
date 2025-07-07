// Variables globales
let currentAnnonceId = null;

// Fonction pour confirmer la suppression
function confirmDelete(annonceId, titre) {
    currentAnnonceId = annonceId;
    
    // Mettre à jour les détails de l'annonce dans le modal
    document.getElementById('annonce-details').innerHTML = `
        <h4>${escapeHtml(titre)}</h4>
        <p>ID: ${annonceId}</p>
    `;
    
    // Afficher le modal
    document.getElementById('deleteModal').style.display = 'flex';
}

// Fonction pour fermer le modal de suppression
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentAnnonceId = null;
}

// Fonction pour supprimer l'annonce
function deleteAnnonce() {
    if (currentAnnonceId) {
        document.getElementById('deleteAnnonceId').value = currentAnnonceId;
        document.getElementById('deleteForm').submit();
    }
}

// Fonction utilitaire pour échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction pour afficher une notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Insérer la notification en haut de la page
    const container = document.querySelector('.container');
    const header = document.querySelector('.profile-header');
    container.insertBefore(notification, header.nextSibling);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Gestion des événements
document.addEventListener('DOMContentLoaded', function() {
    // Fermer le modal en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('deleteModal');
        if (e.target === modal) {
            closeDeleteModal();
        }
    });
    
    // Raccourci clavier pour fermer le modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
    
    // Animation d'apparition des cartes d'annonces
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Appliquer l'animation aux cartes d'annonces
    document.querySelectorAll('.annonce-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        card.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(card);
    });
    
    // Amélioration de la recherche en temps réel
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Optionnel: recherche en temps réel via AJAX
                // Pour l'instant, on garde la recherche par soumission de formulaire
            }, 500);
        });
    }
    
    // Confirmation avant suppression avec double-clic
    let deleteClicks = {};
    
    document.querySelectorAll('[onclick*="confirmDelete"]').forEach(button => {
        button.addEventListener('click', function(e) {
            const annonceId = this.getAttribute('onclick').match(/\d+/)[0];
            
            if (!deleteClicks[annonceId]) {
                deleteClicks[annonceId] = 1;
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Confirmer ?';
                this.classList.add('btn-warning');
                this.classList.remove('btn-danger');
                
                setTimeout(() => {
                    delete deleteClicks[annonceId];
                    this.innerHTML = '<i class="fas fa-trash"></i> Supprimer';
                    this.classList.remove('btn-warning');
                    this.classList.add('btn-danger');
                }, 3000);
                
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Amélioration de l'accessibilité
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Gestion du focus pour les modals
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    // Statistiques en temps réel
    updateStats();
});

// Fonction pour mettre à jour les statistiques
function updateStats() {
    const annonceCards = document.querySelectorAll('.annonce-card');
    const totalAnnonces = annonceCards.length;
    
    // Compter les annonces d'aujourd'hui
    const today = new Date().toDateString();
    let todayCount = 0;
    let withFilesCount = 0;
    
    annonceCards.forEach(card => {
        const dateElement = card.querySelector('.annonce-date');
        if (dateElement) {
            const cardDate = new Date(dateElement.textContent.trim()).toDateString();
            if (cardDate === today) {
                todayCount++;
            }
        }
        
        const fileElement = card.querySelector('.annonce-file');
        if (fileElement) {
            withFilesCount++;
        }
    });
    
    // Mettre à jour les compteurs si ils existent
    const totalElement = document.querySelector('.stat-card:first-child .stat-value');
    const todayElement = document.querySelector('.stat-card:nth-child(2) .stat-value');
    const filesElement = document.querySelector('.stat-card:nth-child(3) .stat-value');
    
    if (totalElement) totalElement.textContent = totalAnnonces;
    if (todayElement) todayElement.textContent = todayCount;
    if (filesElement) filesElement.textContent = withFilesCount;
}

// Fonction pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return 'Hier';
    } else if (diffDays <= 7) {
        return `Il y a ${diffDays} jours`;
    } else {
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}

// Fonction pour prévisualiser les fichiers
function previewFile(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (imageExtensions.includes(extension)) {
        // Créer un modal pour prévisualiser l'image
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3><i class="fas fa-image"></i> Prévisualisation</h3>
                </div>
                <div class="modal-body" style="text-align: center;">
                    <img src="uploads/${filename}" alt="Prévisualisation" style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
}

// Export des fonctions pour usage global
window.confirmDelete = confirmDelete;
window.closeDeleteModal = closeDeleteModal;
window.deleteAnnonce = deleteAnnonce;
window.previewFile = previewFile;