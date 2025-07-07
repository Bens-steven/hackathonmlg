// Variables globales
let allItems = [];

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeAbsenceRetardPage();
});

// Fonction d'initialisation
function initializeAbsenceRetardPage() {
    // Récupérer tous les éléments d'historique
    allItems = Array.from(document.querySelectorAll('.historique-item'));
    
    // Initialiser les filtres
    initializeFilters();
    
    // Ajouter les animations
    addScrollAnimations();
    
    // Formater les dates en français
    formatDatesInFrench();
}

// Initialiser les filtres
function initializeFilters() {
    const typeFilter = document.getElementById('typeFilter');
    
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            filterItems();
        });
    }
}

// Fonction de filtrage
function filterItems() {
    const typeFilter = document.getElementById('typeFilter').value;
    
    allItems.forEach(item => {
        const itemType = item.getAttribute('data-type');
        let shouldShow = true;
        
        // Filtrer par type
        if (typeFilter && itemType !== typeFilter) {
            shouldShow = false;
        }
        
        // Afficher ou masquer l'élément
        if (shouldShow) {
            item.classList.remove('hidden');
            item.style.display = 'flex';
        } else {
            item.classList.add('hidden');
            item.style.display = 'none';
        }
    });
    
    // Vérifier s'il y a des résultats
    checkEmptyResults();
}

// Vérifier s'il y a des résultats après filtrage
function checkEmptyResults() {
    const visibleItems = allItems.filter(item => !item.classList.contains('hidden'));
    const container = document.getElementById('historiqueContainer');
    
    if (visibleItems.length === 0 && allItems.length > 0) {
        // Afficher un message "aucun résultat"
        if (!document.querySelector('.no-results')) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results empty-state';
            noResults.innerHTML = `
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                <h4>Aucun résultat</h4>
                <p>Aucun enregistrement ne correspond aux filtres sélectionnés.</p>
            `;
            container.appendChild(noResults);
        }
    } else {
        // Supprimer le message "aucun résultat" s'il existe
        const noResults = document.querySelector('.no-results');
        if (noResults) {
            noResults.remove();
        }
    }
}

// Effacer tous les filtres
function clearFilters() {
    document.getElementById('typeFilter').value = '';
    
    // Réafficher tous les éléments
    allItems.forEach(item => {
        item.classList.remove('hidden');
        item.style.display = 'flex';
    });
    
    // Supprimer le message "aucun résultat" s'il existe
    const noResults = document.querySelector('.no-results');
    if (noResults) {
        noResults.remove();
    }
    
    // Notification
    showNotification('Filtres effacés', 'success');
}

// Ajouter des animations au scroll
function addScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Observer tous les éléments d'historique
    allItems.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(item);
    });
}

// Formater les dates en français
function formatDatesInFrench() {
    const dateElements = document.querySelectorAll('.date-day');
    
    dateElements.forEach(element => {
        const text = element.textContent.toLowerCase();
        const frenchDays = {
            'monday': 'lundi',
            'tuesday': 'mardi',
            'wednesday': 'mercredi',
            'thursday': 'jeudi',
            'friday': 'vendredi',
            'saturday': 'samedi',
            'sunday': 'dimanche'
        };
        
        if (frenchDays[text]) {
            element.textContent = frenchDays[text];
        }
    });
}

// Fonction pour afficher des notifications
function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Créer la nouvelle notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Styles en fonction du type
    const styles = {
        success: {
            background: 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)',
            color: 'white',
            icon: 'fas fa-check-circle'
        },
        error: {
            background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            color: 'white',
            icon: 'fas fa-exclamation-circle'
        },
        info: {
            background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            color: 'white',
            icon: 'fas fa-info-circle'
        }
    };
    
    const style = styles[type] || styles.info;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${style.background};
        color: ${style.color};
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    notification.innerHTML = `
        <i class="${style.icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Supprimer automatiquement après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Ajouter les animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    .historique-item {
        transition: all 0.3s ease;
    }
    
    .historique-item.hidden {
        opacity: 0;
        transform: translateX(-20px);
    }
`;
document.head.appendChild(style);

// Fonction utilitaire pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return date.toLocaleDateString('fr-FR', options);
}

// Fonction utilitaire pour calculer le nombre de jours entre deux dates
function daysBetween(date1, date2) {
    const oneDay = 24 * 60 * 60 * 1000;
    const firstDate = new Date(date1);
    const secondDate = new Date(date2);
    
    return Math.round(Math.abs((firstDate - secondDate) / oneDay));
}

// Fonction pour exporter les données (optionnelle)
function exportData() {
    const visibleItems = allItems.filter(item => !item.classList.contains('hidden'));
    
    if (visibleItems.length === 0) {
        showNotification('Aucune donnée à exporter', 'error');
        return;
    }
    
    let csvContent = "Date,Type,Heure,Classe,Motif\n";
    
    visibleItems.forEach(item => {
        const date = item.querySelector('.date-main').textContent;
        const type = item.querySelector('.type-badge').textContent.trim();
        const heure = item.querySelector('.time-badge')?.textContent.trim() || '';
        const classe = item.querySelector('.info-item').textContent.replace('Classe: ', '');
        const motif = item.querySelector('.info-item.motif')?.textContent.replace(/"/g, '') || '';
        
        csvContent += `"${date}","${type}","${heure}","${classe}","${motif}"\n`;
    });
    
    // Créer et télécharger le fichier
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'mes_absences_retards.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Données exportées avec succès', 'success');
}

// Gestion des raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl + F pour focus sur le filtre
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('typeFilter').focus();
    }
    
    // Escape pour effacer les filtres
    if (e.key === 'Escape') {
        clearFilters();
    }
});