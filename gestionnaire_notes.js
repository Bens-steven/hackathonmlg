// Variables globales
let currentClass = '';
let noteToDelete = null;

// Fonction pour charger les détails d'une classe
function loadClassDetails(className) {
    currentClass = className;
    
    // Afficher un indicateur de chargement
    showLoadingState();
    
    // Faire l'appel AJAX pour récupérer les notes
    fetch(`get_class_notes.php?classe=${encodeURIComponent(className)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayClassDetails(className, data.notes, data.stats);
            } else {
                showNotification('Erreur lors du chargement des notes: ' + data.error, 'error');
                console.error('Erreur:', data.error);
            }
        })
        .catch(error => {
            showNotification('Erreur de connexion lors du chargement des notes', 'error');
            console.error('Erreur:', error);
        })
        .finally(() => {
            hideLoadingState();
        });
}

// Fonction pour afficher l'état de chargement
function showLoadingState() {
    const container = document.getElementById('students-container');
    if (container) {
        container.innerHTML = `
            <div class="loading-state" style="text-align: center; padding: 3rem;">
                <div class="loading-spinner" style="
                    width: 40px; 
                    height: 40px; 
                    border: 4px solid #f3f4f6; 
                    border-top: 4px solid #3b82f6; 
                    border-radius: 50%; 
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p style="color: #6b7280;">Chargement des notes...</p>
            </div>
        `;
    }
}

// Fonction pour masquer l'état de chargement
function hideLoadingState() {
    // L'état de chargement sera remplacé par le contenu réel
}

// Fonction pour afficher les détails d'une classe
function displayClassDetails(className, notes, stats) {
    // Masquer la vue des classes et afficher la vue détaillée
    document.getElementById('classes-view').style.display = 'none';
    document.getElementById('class-detail-view').style.display = 'block';
    
    // Mettre à jour le titre et les statistiques
    document.getElementById('class-name').textContent = className;
    document.getElementById('class-student-count').textContent = `${stats.nb_eleves} élèves`;
    document.getElementById('class-average').textContent = `Moyenne: ${stats.moyenne}/20`;
    
    // Organiser les notes par élève
    const studentNotes = organizeNotesByStudent(notes);
    
    // Afficher les élèves et leurs notes
    displayStudents(studentNotes);
}

// Fonction pour organiser les notes par élève
function organizeNotesByStudent(notes) {
    const studentNotes = {};
    
    notes.forEach(note => {
        if (!studentNotes[note.eleve_username]) {
            studentNotes[note.eleve_username] = {
                username: note.eleve_username,
                notes: [],
                average: 0
            };
        }
        studentNotes[note.eleve_username].notes.push(note);
    });
    
    // Calculer la moyenne pour chaque élève
    Object.keys(studentNotes).forEach(username => {
        const student = studentNotes[username];
        if (student.notes.length > 0) {
            const total = student.notes.reduce((sum, note) => sum + parseFloat(note.note), 0);
            student.average = (total / student.notes.length).toFixed(1);
        }
    });
    
    return studentNotes;
}

// Fonction pour afficher les élèves
function displayStudents(studentNotes) {
    const container = document.getElementById('students-container');
    
    if (Object.keys(studentNotes).length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-user-graduate" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                        <p>Aucune note trouvée pour cette classe.</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    Object.keys(studentNotes).sort().forEach(username => {
        const student = studentNotes[username];
        const averageClass = getGradeClass(student.average);
        
        html += `
            <div class="card student-card">
                <div class="card-header">
                    <div class="student-info">
                        <h3>${escapeHtml(student.username)}</h3>
                        <p class="username">@${escapeHtml(student.username)}</p>
                    </div>
                    <div class="student-average ${averageClass}">
                        Moyenne: ${student.average}/20
                    </div>
                </div>
                <div class="card-body">
                    <div class="grades-list">
                        ${student.notes.map(note => `
                            <div class="grade-item">
                                <div class="grade-info">
                                    <div class="grade-subject">${escapeHtml(note.matiere)}</div>
                                    <div class="grade-date">
                                        <i class="fas fa-calendar"></i>
                                        ${note.date_formatted}
                                    </div>
                                </div>
                                <div class="grade-actions">
                                    <div class="grade-value ${getGradeClass(note.note)}">
                                        ${note.note}/20
                                    </div>
                                    <button class="btn-delete" onclick="showDeleteModal(${note.id}, '${escapeHtml(note.eleve_username)}', '${note.note}', '${note.date_formatted}')" title="Supprimer cette note">
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

// Fonction pour déterminer la classe CSS selon la note
function getGradeClass(note) {
    const numNote = parseFloat(note);
    if (numNote >= 16) return 'excellent';
    if (numNote >= 14) return 'good';
    if (numNote >= 10) return 'average';
    return 'poor';
}

// Fonction pour échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction pour afficher le modal de suppression
function showDeleteModal(noteId, studentUsername, noteValue, noteDate) {
    noteToDelete = noteId;
    
    const modal = document.getElementById('deleteModal');
    const noteDetails = document.getElementById('note-details');
    
    noteDetails.innerHTML = `
        <p><strong>Élève:</strong> ${escapeHtml(studentUsername)}</p>
        <p><strong>Note:</strong> ${noteValue}/20</p>
        <p><strong>Date:</strong> ${noteDate}</p>
    `;
    
    modal.style.display = 'flex';
}

// Fonction pour fermer le modal de suppression
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
    noteToDelete = null;
}

// Fonction pour confirmer la suppression
function confirmDelete() {
    if (!noteToDelete) {
        showNotification('Erreur: Aucune note sélectionnée pour suppression', 'error');
        return;
    }
    
    // Créer un fichier PHP pour la suppression
    fetch('delete_note.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `note_id=${noteToDelete}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Note supprimée avec succès', 'success');
            closeDeleteModal();
            // Recharger les détails de la classe
            loadClassDetails(currentClass);
        } else {
            showNotification('Erreur lors de la suppression: ' + data.error, 'error');
        }
    })
    .catch(error => {
        showNotification('Erreur de connexion lors de la suppression', 'error');
        console.error('Erreur:', error);
    });
}

// Fonction pour retourner à la liste des classes
function showClassesList() {
    document.getElementById('class-detail-view').style.display = 'none';
    document.getElementById('classes-view').style.display = 'block';
    currentClass = '';
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
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
        z-index: 1001;
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

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
});

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});