// Système de gestion des annonces avec fonctionnalités avancées
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let allAnnouncements = [];
    let readAnnouncements = JSON.parse(localStorage.getItem('readAnnouncements') || '[]');
    let currentFilters = {
        search: '',
        status: 'all',
        date: 'all',
        sort: 'newest'
    };

    // Initialisation
    initializeAnnouncements();
    setupEventListeners();
    updateStats();

    // Initialiser les annonces
    function initializeAnnouncements() {
        const announcementCards = document.querySelectorAll('.announcement-card');
        allAnnouncements = Array.from(announcementCards).map(card => {
            const id = parseInt(card.dataset.id);
            const isRead = readAnnouncements.includes(id);
            
            // Marquer visuellement les annonces lues
            if (isRead) {
                markAnnouncementAsRead(id, false);
            }
            
            return {
                id: id,
                element: card,
                title: card.dataset.title,
                content: card.dataset.content,
                date: card.dataset.date,
                timestamp: parseInt(card.dataset.timestamp),
                isRead: isRead
            };
        });
    }

    // Configuration des écouteurs d'événements
    function setupEventListeners() {
        // Barre de recherche
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearch, 300));
        }

        // Filtres
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const sortFilter = document.getElementById('sortFilter');

        if (statusFilter) statusFilter.addEventListener('change', handleFilterChange);
        if (dateFilter) dateFilter.addEventListener('change', handleFilterChange);
        if (sortFilter) sortFilter.addEventListener('change', handleFilterChange);

        // Raccourcis clavier
        document.addEventListener('keydown', handleKeyboardShortcuts);
    }

    // Gestion de la recherche
    function handleSearch(event) {
        currentFilters.search = event.target.value.toLowerCase().trim();
        applyFilters();
        updateSearchStats();
    }

    // Gestion des changements de filtres
    function handleFilterChange(event) {
        const filterId = event.target.id;
        const value = event.target.value;
        
        switch(filterId) {
            case 'statusFilter':
                currentFilters.status = value;
                break;
            case 'dateFilter':
                currentFilters.date = value;
                break;
            case 'sortFilter':
                currentFilters.sort = value;
                break;
        }
        
        applyFilters();
        updateSearchStats();
    }

    // Appliquer tous les filtres
    function applyFilters() {
        let filteredAnnouncements = [...allAnnouncements];

        // Filtre de recherche
        if (currentFilters.search) {
            filteredAnnouncements = filteredAnnouncements.filter(announcement => 
                announcement.title.includes(currentFilters.search) || 
                announcement.content.includes(currentFilters.search)
            );
        }

        // Filtre de statut
        if (currentFilters.status !== 'all') {
            filteredAnnouncements = filteredAnnouncements.filter(announcement => {
                if (currentFilters.status === 'read') return announcement.isRead;
                if (currentFilters.status === 'unread') return !announcement.isRead;
                return true;
            });
        }

        // Filtre de date
        if (currentFilters.date !== 'all') {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

            filteredAnnouncements = filteredAnnouncements.filter(announcement => {
                const announcementDate = new Date(announcement.timestamp * 1000);
                
                switch(currentFilters.date) {
                    case 'today':
                        return announcementDate >= today;
                    case 'week':
                        return announcementDate >= weekAgo;
                    case 'month':
                        return announcementDate >= monthAgo;
                    default:
                        return true;
                }
            });
        }

        // Tri
        filteredAnnouncements.sort((a, b) => {
            switch(currentFilters.sort) {
                case 'newest':
                    return b.timestamp - a.timestamp;
                case 'oldest':
                    return a.timestamp - b.timestamp;
                case 'title':
                    return a.title.localeCompare(b.title);
                default:
                    return 0;
            }
        });

        // Afficher/masquer les annonces
        allAnnouncements.forEach(announcement => {
            const isVisible = filteredAnnouncements.includes(announcement);
            announcement.element.style.display = isVisible ? 'block' : 'none';
            
            if (isVisible) {
                announcement.element.classList.remove('filtered-out');
                highlightSearchTerms(announcement.element, currentFilters.search);
            } else {
                announcement.element.classList.add('filtered-out');
            }
        });

        // Afficher le message "aucun résultat" si nécessaire
        const noResultsMessage = document.getElementById('noResultsMessage');
        if (noResultsMessage) {
            noResultsMessage.style.display = filteredAnnouncements.length === 0 ? 'block' : 'none';
        }
    }

    // Surligner les termes de recherche
    function highlightSearchTerms(element, searchTerm) {
        if (!searchTerm) return;

        const titleElement = element.querySelector('.announcement-title');
        const contentElement = element.querySelector('.announcement-text');

        [titleElement, contentElement].forEach(el => {
            if (el && el.textContent) {
                const originalText = el.textContent;
                const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                const highlightedText = originalText.replace(regex, '<span class="search-highlight">$1</span>');
                
                if (highlightedText !== originalText) {
                    el.innerHTML = highlightedText;
                    el.closest('.announcement-card').classList.add('search-highlight');
                }
            }
        });
    }

    // Mettre à jour les statistiques de recherche
    function updateSearchStats() {
        const searchStats = document.getElementById('searchStats');
        const searchStatsText = document.getElementById('searchStatsText');
        
        if (!searchStats || !searchStatsText) return;

        const visibleCount = allAnnouncements.filter(a => a.element.style.display !== 'none').length;
        const totalCount = allAnnouncements.length;

        if (currentFilters.search || currentFilters.status !== 'all' || currentFilters.date !== 'all') {
            searchStats.style.display = 'flex';
            searchStatsText.textContent = `${visibleCount} annonce(s) trouvée(s) sur ${totalCount}`;
        } else {
            searchStats.style.display = 'none';
        }
    }

    // Mettre à jour les statistiques générales
    function updateStats() {
        const totalElement = document.getElementById('totalAnnouncements');
        const unreadElement = document.getElementById('unreadCount');
        const readElement = document.getElementById('readCount');

        if (totalElement) totalElement.textContent = allAnnouncements.length;
        
        const unreadCount = allAnnouncements.filter(a => !a.isRead).length;
        const readCount = allAnnouncements.filter(a => a.isRead).length;
        
        if (unreadElement) unreadElement.textContent = unreadCount;
        if (readElement) readElement.textContent = readCount;
    }

    // Basculer l'affichage d'une annonce
    window.toggleAnnouncement = function(id) {
        const card = document.querySelector(`[data-id="${id}"]`);
        const content = document.getElementById(`content-${id}`);
        const expandBtn = card.querySelector('.expand-btn');
        const markReadBtn = card.querySelector('.mark-read-btn');
        
        if (!card || !content || !expandBtn) return;

        const isExpanded = content.style.display !== 'none';
        
        if (isExpanded) {
            // Fermer l'annonce
            content.style.display = 'none';
            expandBtn.classList.remove('expanded');
            expandBtn.querySelector('span').textContent = 'Voir';
            markReadBtn.style.display = 'none';
            card.classList.remove('just-opened');
            card.classList.add('just-closed');
        } else {
            // Ouvrir l'annonce
            content.style.display = 'block';
            expandBtn.classList.add('expanded');
            expandBtn.querySelector('span').textContent = 'Voir';
            
            // Afficher le bouton "Marquer comme lu" si l'annonce n'est pas lue
            const announcement = allAnnouncements.find(a => a.id === id);
            if (announcement && !announcement.isRead) {
                markReadBtn.style.display = 'inline-flex';
            }
            
            card.classList.remove('just-closed');
            card.classList.add('just-opened');
            
            // Scroll vers l'annonce ouverte
            setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    };

    // Marquer une annonce comme lue
    window.markAsRead = function(id) {
        markAnnouncementAsRead(id, true);
        showToast('Annonce marquée comme lue', 'success');
    };

    // Marquer toutes les annonces comme lues
    window.markAllAsRead = function() {
        allAnnouncements.forEach(announcement => {
            if (!announcement.isRead) {
                markAnnouncementAsRead(announcement.id, false);
            }
        });
        
        showToast(`${allAnnouncements.filter(a => !a.isRead).length} annonces marquées comme lues`, 'success');
        updateStats();
        applyFilters();
    };

    // Fonction utilitaire pour marquer une annonce comme lue
    function markAnnouncementAsRead(id, showAnimation = true) {
        const card = document.querySelector(`[data-id="${id}"]`);
        const indicator = document.getElementById(`indicator-${id}`);
        const markReadBtn = card?.querySelector('.mark-read-btn');
        
        if (!card) return;

        // Mettre à jour l'état local
        const announcement = allAnnouncements.find(a => a.id === id);
        if (announcement) {
            announcement.isRead = true;
        }

        // Mettre à jour le localStorage
        if (!readAnnouncements.includes(id)) {
            readAnnouncements.push(id);
            localStorage.setItem('readAnnouncements', JSON.stringify(readAnnouncements));
        }

        // Mettre à jour l'interface
        if (showAnimation) {
            card.classList.add('just-read');
        }
        
        setTimeout(() => {
            card.classList.add('read');
            if (indicator) {
                indicator.classList.add('read');
            }
            if (markReadBtn) {
                markReadBtn.style.display = 'none';
            }
        }, showAnimation ? 500 : 0);
    }

    // Effacer tous les filtres
    window.clearAllFilters = function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('dateFilter').value = 'all';
        document.getElementById('sortFilter').value = 'newest';
        
        currentFilters = {
            search: '',
            status: 'all',
            date: 'all',
            sort: 'newest'
        };
        
        applyFilters();
        updateSearchStats();
        showToast('Filtres effacés', 'info');
    };

    // Gestion des raccourcis clavier
    function handleKeyboardShortcuts(event) {
        // Ctrl + F pour focus sur la recherche
        if (event.ctrlKey && event.key === 'f') {
            event.preventDefault();
            document.getElementById('searchInput')?.focus();
        }
        
        // Ctrl + A pour marquer tout comme lu
        if (event.ctrlKey && event.key === 'a' && event.target.tagName !== 'INPUT') {
            event.preventDefault();
            markAllAsRead();
        }
        
        // Échap pour effacer la recherche
        if (event.key === 'Escape' && document.activeElement === document.getElementById('searchInput')) {
            document.getElementById('searchInput').value = '';
            handleSearch({ target: { value: '' } });
        }
    }

    // Afficher une notification toast
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Obtenir l'icône pour le toast
    function getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle',
            error: 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Fonction utilitaire pour debounce
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Échapper les caractères spéciaux pour regex
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Initialisation finale
    setTimeout(() => {
        applyFilters();
        updateSearchStats();
    }, 100);
});