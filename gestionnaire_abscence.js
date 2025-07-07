// Variables globales
let currentClass = '';
let deleteId = null;

// Fonction pour charger les détails d'une classe
function loadClassDetails(className) {
    currentClass = className;
    
    // Masquer la vue des classes et afficher la vue détaillée
    document.getElementById('classes-view').style.display = 'none';
    document.getElementById('class-detail-view').style.display = 'block';
    
    // Mettre à jour le nom de la classe
    document.getElementById('class-name').textContent = className;
    
    // Charger les données des élèves via AJAX
    fetch('get_class_absences.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'classe=' + encodeURIComponent(className)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayStudentsAbsences(data.students, data.stats);
        } else {
            console.error('Erreur:', data.message);
            showError('Erreur lors du chargement des données');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// Fonction pour afficher les absences des élèves
function displayStudentsAbsences(students, stats) {
    const container = document.getElementById('students-container');
    
    // Mettre à jour les statistiques de la classe
    document.getElementById('class-student-count').textContent = stats.nb_eleves + ' élèves';
    document.getElementById('class-absence-count').textContent = stats.nb_absences + ' absences';
    document.getElementById('class-retard-count').textContent = stats.nb_retards + ' retards';
    
    if (students.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-user-check" style="font-size: 3rem; margin-bottom: 1rem; color: #22c55e;"></i>
                        <h4>Aucune absence ou retard</h4>
                        <p>Tous les élèves de cette classe sont présents et ponctuels.</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    students.forEach(student => {
        html += `
            <div class="card student-card">
                <div class="card-header">
                    <div class="student-info">
                        <h3>${escapeHtml(student.eleve)}</h3>
                        <p class="username">@${escapeHtml(student.eleve)}</p>
                    </div>
                    <div class="student-stats">
                        <div class="stat-badge absence-badge">
                            <i class="fas fa-user-times"></i>
                            <span>${student.nb_absences} absences</span>
                        </div>
                        <div class="stat-badge retard-badge">
                            <i class="fas fa-clock"></i>
                            <span>${student.nb_retards} retards</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="absences-list">
                        ${student.absences.map(absence => `
                            <div class="absence-item ${absence.type}">
                                <div class="absence-info">
                                    <div class="absence-type ${absence.type}">
                                        <i class="fas fa-${absence.type === 'absence' ? 'user-times' : 'clock'}"></i>
                                        <span class="type-badge ${absence.type}">${absence.type}</span>
                                    </div>
                                    <div class="absence-date">
                                        <i class="fas fa-calendar"></i>
                                        <span>${formatDate(absence.date)}</span>
                                        ${absence.heure ? `<i class="fas fa-clock" style="margin-left: 1rem;"></i><span>${absence.heure}</span>` : ''}
                                    </div>
                                    ${absence.motif ? `<div class="absence-motif">"${escapeHtml(absence.motif)}"</div>` : ''}
                                </div>
                                <div class="absence-actions">
                                    <button class="btn-delete" onclick="showDeleteModal(${absence.id}, '${absence.type}', '${absence.date}', '${absence.eleve}')" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Fonction pour retourner à la liste des classes
function showClassesList() {
    document.getElementById('class-detail-view').style.display = 'none';
    document.getElementById('classes-view').style.display = 'block';
    currentClass = '';
}

// Fonction pour afficher le modal de suppression
function showDeleteModal(id, type, date, eleve) {
    deleteId = id;
    
    const modal = document.getElementById('deleteModal');
    const details = document.getElementById('absence-details');
    
    details.innerHTML = `
        <p><strong>Élève:</strong> ${escapeHtml(eleve)}</p>
        <p><strong>Type:</strong> <span class="type-badge ${type}">${type}</span></p>
        <p><strong>Date:</strong> ${formatDate(date)}</p>
    `;
    
    modal.style.display = 'flex';
}

// Fonction pour fermer le modal de suppression
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteId = null;
}

// Fonction pour confirmer la suppression
function confirmDelete() {
    if (!deleteId) return;
    
    fetch('delete_absence.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + deleteId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            // Recharger les données de la classe
            loadClassDetails(currentClass);
            showSuccess('Enregistrement supprimé avec succès');
        } else {
            showError(data.message || 'Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

// Fonctions utilitaires
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function showSuccess(message) {
    // Créer une notification de succès
    const notification = document.createElement('div');
    notification.className = 'notification-success';
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showError(message) {
    // Créer une notification d'erreur
    const notification = document.createElement('div');
    notification.className = 'notification-error';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
});

// Fermer le modal avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});