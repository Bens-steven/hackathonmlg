// fichier : gestionnaire_devoirsprof.js

// Variables globales pour la suppression
let currentHomeworkToDelete = null;

function loadClassHomework(classe) {
    // Affiche la vue des devoirs avec animation
    const classesView = document.getElementById('classes-view');
    const detailView = document.getElementById('class-detail-view');
    
    classesView.style.opacity = '0';
    setTimeout(() => {
        classesView.style.display = 'none';
        detailView.style.display = 'block';
        detailView.style.opacity = '1';
    }, 300);

    document.getElementById('class-name').innerHTML = `
        <i class="fas fa-users"></i>
        Classe ${classe}
    `;

    // Affichage du loader pendant le chargement
    const container = document.getElementById('homework-container');
    container.innerHTML = `
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <p class="loading-text">Chargement des devoirs en cours...</p>
        </div>
    `;

    // Envoie la requête AJAX pour récupérer les devoirs
    fetch(`get_class_homework.php?classe=${classe}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showErrorMessage("Erreur lors du chargement : " + data.error);
                showClassesList();
                return;
            }

            // Met à jour le nombre de devoirs avec animation
            const countElement = document.getElementById('class-homework-count');
            countElement.innerHTML = `
                <i class="fas fa-tasks"></i>
                ${data.stats.nb_devoirs} devoir(s)
            `;

            container.innerHTML = ''; // Vide l'ancien contenu

            if (data.homework.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-modern">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Aucun devoir trouvé</h3>
                        <p>Cette classe n'a pas encore de devoirs assignés.</p>
                    </div>
                `;
                return;
            }

            // Créer les cartes de devoirs avec un délai d'animation
            data.homework.forEach((devoir, index) => {
                setTimeout(() => {
                    createHomeworkCard(devoir, classe, container);
                }, index * 150);
            });
        })
        .catch(err => {
            console.error('Erreur de récupération des devoirs :', err);
            showErrorMessage("Une erreur réseau s'est produite lors du chargement.");
            showClassesList();
        });
}

function createHomeworkCard(devoir, classe, container) {
    const card = document.createElement('div');
    card.className = 'homework-card-modern';
    card.setAttribute('data-homework-id', devoir.id);
    
    const devoirId = devoir.id;
    const now = new Date();
    const deadline = new Date(devoir.date_limite);
    const isOverdue = now > deadline;
    
    // Déterminer le statut du devoir
    let statusClass = 'status-active';
    let statusIcon = 'fas fa-clock';
    let statusText = 'En cours';
    
    if (isOverdue) {
        statusClass = 'status-overdue';
        statusIcon = 'fas fa-exclamation-triangle';
        statusText = 'Échéance dépassée';
    }

    card.innerHTML = `
        <div class="homework-header-modern">
            <div class="homework-title-section">
                <h3 class="homework-title-modern">
                    <i class="fas fa-file-alt"></i>
                    ${devoir.titre}
                </h3>
                <div class="homework-actions-modern">
                    <div class="homework-status ${statusClass}">
                        <i class="${statusIcon}"></i>
                        <span>${statusText}</span>
                    </div>
                    <button class="btn-delete-homework" onclick="showDeleteConfirmation(${devoirId}, '${devoir.titre.replace(/'/g, "\\'")}')">
                        <i class="fas fa-trash"></i>
                        <span>Supprimer</span>
                    </button>
                </div>
            </div>
            <div class="homework-dates-modern">
                <div class="date-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Créé le ${devoir.date_creation_formatted}</span>
                </div>
                <div class="date-item ${isOverdue ? 'date-overdue' : ''}">
                    <i class="fas fa-calendar-times"></i>
                    <span>Échéance : ${devoir.date_limite_formatted}</span>
                </div>
            </div>
        </div>
        
        <div class="homework-content-modern">
            <div class="homework-description">
                <p>${devoir.contenu}</p>
            </div>
            
            ${devoir.fichier ? `
                <div class="homework-attachment">
                    <a href="pieces_jointes/${devoir.fichier}" target="_blank" class="attachment-link">
                        <i class="fas fa-paperclip"></i>
                        <span>Pièce jointe disponible</span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            ` : ''}
            
            <div class="submissions-section" id="submissions-${devoirId}">
                <div class="submissions-header">
                    <h4>
                        <i class="fas fa-users"></i>
                        Rendus des élèves
                    </h4>
                    <div class="loading-submissions">
                        <div class="mini-spinner"></div>
                        <span>Chargement...</span>
                    </div>
                </div>
                <div class="submissions-content" id="submissions-content-${devoirId}">
                    <!-- Les soumissions seront chargées ici -->
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(card);
    
    // Animation d'apparition
    setTimeout(() => {
        card.classList.add('card-animate-in');
    }, 50);

    // Charger les rendus pour ce devoir
    loadSubmissions(devoirId, classe, deadline);
}

function loadSubmissions(devoirId, classe, deadline) {
    fetch(`get_submissions.php?homework_id=${devoirId}&classe=${classe}`)
        .then(res => res.json())
        .then(subData => {
            const submissionsContent = document.getElementById(`submissions-content-${devoirId}`);
            const loadingElement = document.querySelector(`#submissions-${devoirId} .loading-submissions`);
            
            if (!subData.success) {
                loadingElement.innerHTML = `
                    <div class="error-message-inline">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Erreur lors du chargement des rendus</span>
                    </div>
                `;
                return;
            }

            const now = new Date();
            
            // Calculer les statistiques
            const submitted = subData.submissions.filter(s => s.rendu).length;
            const total = subData.submissions.length;
            const pending = total - submitted;
            const overdue = subData.submissions.filter(s => !s.rendu && now > deadline).length;
            
            // Masquer le loader
            loadingElement.style.display = 'none';
            
            // Créer les statistiques
            const statsHtml = `
                <div class="submissions-stats-modern">
                    <div class="stat-item-modern stat-submitted">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-content">
                            <span class="stat-number">${submitted}</span>
                            <span class="stat-label">Rendus</span>
                        </div>
                    </div>
                    <div class="stat-item-modern stat-pending">
                        <i class="fas fa-clock"></i>
                        <div class="stat-content">
                            <span class="stat-number">${pending}</span>
                            <span class="stat-label">En attente</span>
                        </div>
                    </div>
                    ${overdue > 0 ? `
                        <div class="stat-item-modern stat-overdue">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="stat-content">
                                <span class="stat-number">${overdue}</span>
                                <span class="stat-label">En retard</span>
                            </div>
                        </div>
                    ` : ''}
                    <div class="stat-item-modern stat-total">
                        <i class="fas fa-users"></i>
                        <div class="stat-content">
                            <span class="stat-number">${total}</span>
                            <span class="stat-label">Total élèves</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Créer la grille des élèves
            const studentsGrid = document.createElement('div');
            studentsGrid.className = 'students-grid-modern';
            
            subData.submissions.forEach((entry, index) => {
                const studentCard = document.createElement('div');
                const isSubmitted = entry.rendu;
                const isLate = !isSubmitted && now > deadline;
                
                let cardClass = 'student-card-modern';
                let statusClass = 'student-status-pending';
                let statusIcon = 'fas fa-clock';
                let statusText = 'En attente';
                
                if (isSubmitted) {
                    cardClass += ' student-submitted';
                    statusClass = 'student-status-submitted';
                    statusIcon = 'fas fa-check-circle';
                    statusText = 'Rendu';
                } else if (isLate) {
                    cardClass += ' student-overdue';
                    statusClass = 'student-status-overdue';
                    statusIcon = 'fas fa-exclamation-triangle';
                    statusText = 'En retard';
                }
                
                // Générer un avatar coloré basé sur le nom
                const avatarColor = generateAvatarColor(entry.eleve_username);
                const initials = entry.eleve_username.substring(0, 2).toUpperCase();
                
                studentCard.className = cardClass;
                studentCard.innerHTML = `
                    <div class="student-avatar-modern" style="background: ${avatarColor}">
                        ${initials}
                    </div>
                    <div class="student-info-modern">
                        <div class="student-name-modern">${entry.eleve_username}</div>
                        <div class="student-status-modern ${statusClass}">
                            <i class="${statusIcon}"></i>
                            <span>${statusText}</span>
                        </div>
                    </div>
                    ${isSubmitted ? `
                        <div class="student-actions-modern">
                            <button class="btn-view-file-modern" onclick="viewSubmissionFile('${entry.fichier_rendu}', '${entry.eleve_username}')">
                                <i class="fas fa-eye"></i>
                                <span>Voir le fichier</span>
                            </button>
                        </div>
                    ` : ''}
                `;
                
                studentsGrid.appendChild(studentCard);
                
                // Animation d'apparition échelonnée
                setTimeout(() => {
                    studentCard.classList.add('student-animate-in');
                }, index * 100);
            });
            
            submissionsContent.innerHTML = statsHtml;
            submissionsContent.appendChild(studentsGrid);
        })
        .catch(error => {
            const loadingElement = document.querySelector(`#submissions-${devoirId} .loading-submissions`);
            loadingElement.innerHTML = `
                <div class="error-message-inline">
                    <i class="fas fa-wifi"></i>
                    <span>Erreur de connexion</span>
                </div>
            `;
            console.error(error);
        });
}

// Fonction pour afficher la modal de confirmation de suppression
function showDeleteConfirmation(homeworkId, homeworkTitle) {
    currentHomeworkToDelete = {
        id: homeworkId,
        title: homeworkTitle
    };
    
    // Créer la modal si elle n'existe pas
    let modal = document.getElementById('delete-confirmation-modal');
    if (!modal) {
        modal = createDeleteModal();
        document.body.appendChild(modal);
    }
    
    // Mettre à jour le contenu de la modal
    const titleElement = modal.querySelector('.homework-title-to-delete');
    titleElement.textContent = homeworkTitle;
    
    // Afficher la modal
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('modal-show');
    }, 10);
}

// Fonction pour créer la modal de suppression
function createDeleteModal() {
    const modal = document.createElement('div');
    modal.id = 'delete-confirmation-modal';
    modal.className = 'delete-modal-modern';
    
    modal.innerHTML = `
        <div class="delete-modal-overlay" onclick="closeDeleteModal()"></div>
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <div class="delete-icon-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Confirmer la suppression</h3>
            </div>
            <div class="delete-modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce devoir ?</p>
                <div class="homework-info-delete">
                    <i class="fas fa-file-alt"></i>
                    <span class="homework-title-to-delete"></span>
                </div>
                <div class="warning-message">
                    <i class="fas fa-info-circle"></i>
                    <span>Cette action supprimera définitivement le devoir et tous les fichiers associés.</span>
                </div>
            </div>
            <div class="delete-modal-footer">
                <button class="btn-cancel-delete" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    <span>Annuler</span>
                </button>
                <button class="btn-confirm-delete" onclick="confirmDeleteHomework()">
                    <i class="fas fa-trash"></i>
                    <span>Supprimer</span>
                </button>
            </div>
        </div>
    `;
    
    return modal;
}

// Fonction pour fermer la modal de suppression
function closeDeleteModal() {
    const modal = document.getElementById('delete-confirmation-modal');
    if (modal) {
        modal.classList.remove('modal-show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    currentHomeworkToDelete = null;
}

// Fonction pour confirmer la suppression
function confirmDeleteHomework() {
    if (!currentHomeworkToDelete) {
        showErrorMessage("Erreur : aucun devoir sélectionné pour la suppression");
        return;
    }
    
    const { id, title } = currentHomeworkToDelete;
    
    // Afficher un loader sur le bouton de confirmation
    const confirmBtn = document.querySelector('.btn-confirm-delete');
    const originalContent = confirmBtn.innerHTML;
    confirmBtn.innerHTML = `
        <div class="mini-spinner"></div>
        <span>Suppression...</span>
    `;
    confirmBtn.disabled = true;
    
    // Envoyer la requête de suppression
    const formData = new FormData();
    formData.append('homework_id', id);
    
    fetch('delete_homework.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer la modal
            closeDeleteModal();
            
            // Supprimer la carte du DOM avec animation
            const homeworkCard = document.querySelector(`[data-homework-id="${id}"]`);
            if (homeworkCard) {
                homeworkCard.style.transform = 'translateX(-100%)';
                homeworkCard.style.opacity = '0';
                setTimeout(() => {
                    homeworkCard.remove();
                    
                    // Mettre à jour le compteur de devoirs
                    updateHomeworkCount();
                }, 300);
            }
            
            // Afficher le message de succès
            showSuccessMessage(`"${title}" supprimé avec succès`);
            
        } else {
            showErrorMessage("Erreur lors de la suppression : " + data.error);
            
            // Restaurer le bouton
            confirmBtn.innerHTML = originalContent;
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression:', error);
        showErrorMessage("Une erreur réseau s'est produite lors de la suppression");
        
        // Restaurer le bouton
        confirmBtn.innerHTML = originalContent;
        confirmBtn.disabled = false;
    });
}

// Fonction pour mettre à jour le compteur de devoirs
function updateHomeworkCount() {
    const homeworkCards = document.querySelectorAll('.homework-card-modern');
    const count = homeworkCards.length;
    const countElement = document.getElementById('class-homework-count');
    
    if (countElement) {
        countElement.innerHTML = `
            <i class="fas fa-tasks"></i>
            ${count} devoir(s)
        `;
    }
    
    // Si plus aucun devoir, afficher l'état vide
    if (count === 0) {
        const container = document.getElementById('homework-container');
        container.innerHTML = `
            <div class="empty-state-modern">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Aucun devoir trouvé</h3>
                <p>Cette classe n'a plus de devoirs assignés.</p>
            </div>
        `;
    }
}

function generateAvatarColor(username) {
    // Générer une couleur basée sur le nom d'utilisateur
    const colors = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
        'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
        'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
        'linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%)'
    ];
    
    let hash = 0;
    for (let i = 0; i < username.length; i++) {
        hash = username.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    return colors[Math.abs(hash) % colors.length];
}

function viewSubmissionFile(filename, studentName) {
    // Ouvrir le fichier dans un nouvel onglet avec une notification
    window.open(`rendus_eleves/${filename}`, '_blank');
    
    showSuccessMessage(`Ouverture du fichier de ${studentName}`);
}

function showClassesList() {
    const classesView = document.getElementById('classes-view');
    const detailView = document.getElementById('class-detail-view');
    
    detailView.style.opacity = '0';
    setTimeout(() => {
        detailView.style.display = 'none';
        classesView.style.display = 'block';
        classesView.style.opacity = '1';
    }, 300);
}

// Fonctions utilitaires pour les messages
function showSuccessMessage(message) {
    showNotification(message, 'success');
}

function showErrorMessage(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification-modern');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification-modern notification-${type}`;
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    notification.innerHTML = `
        <div class="notification-content-modern">
            <i class="${icons[type] || icons.info}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close-modern" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('notification-show');
    }, 100);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('notification-show');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Ajouter les styles CSS dynamiquement
function addModernStyles() {
    if (document.getElementById('modern-homework-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'modern-homework-styles';
    style.textContent = `
        /* Styles modernes pour le gestionnaire de devoirs */
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        .loading-text {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .empty-state-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .empty-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e5e7eb 0%, #f3f4f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            color: #9ca3af;
        }
        
        .empty-state-modern h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-modern p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .homework-card-modern {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.5s ease;
        }
        
        .homework-card-modern.card-animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .homework-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .homework-header-modern {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .homework-title-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .homework-title-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
        
        .homework-title-modern i {
            color: #3b82f6;
        }
        
        .homework-actions-modern {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .homework-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .homework-status.status-active {
            background: #dcfce7;
            color: #166534;
            border: 2px solid #22c55e;
        }
        
        .homework-status.status-overdue {
            background: #fef2f2;
            color: #dc2626;
            border: 2px solid #ef4444;
        }
        
        .btn-delete-homework {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete-homework:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-delete-homework:active {
            transform: translateY(0);
        }
        
        .homework-dates-modern {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            border: 1px solid #e5e7eb;
        }
        
        .date-item.date-overdue {
            color: #dc2626;
            background: #fef2f2;
            border-color: #ef4444;
        }
        
        .date-item i {
            color: #3b82f6;
        }
        
        .date-item.date-overdue i {
            color: #dc2626;
        }
        
        .homework-content-modern {
            padding: 2rem;
        }
        
        .homework-description {
            margin-bottom: 2rem;
        }
        
        .homework-description p {
            color: #4b5563;
            line-height: 1.7;
            font-size: 1rem;
        }
        
        .homework-attachment {
            margin-bottom: 2rem;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .attachment-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .submissions-section {
            border-top: 2px solid #f1f5f9;
            padding-top: 2rem;
        }
        
        .submissions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .submissions-header h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .submissions-header h4 i {
            color: #3b82f6;
        }
        
        .loading-submissions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .mini-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .error-message-inline {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #dc2626;
            font-size: 0.9rem;
            background: #fef2f2;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            border: 1px solid #ef4444;
        }
        
        .submissions-stats-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item-modern {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-item-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-item-modern i {
            font-size: 1.5rem;
        }
        
        .stat-item-modern.stat-submitted {
            border-color: #22c55e;
        }
        
        .stat-item-modern.stat-submitted i {
            color: #22c55e;
        }
        
        .stat-item-modern.stat-pending {
            border-color: #f59e0b;
        }
        
        .stat-item-modern.stat-pending i {
            color: #f59e0b;
        }
        
        .stat-item-modern.stat-overdue {
            border-color: #ef4444;
        }
        
        .stat-item-modern.stat-overdue i {
            color: #ef4444;
        }
        
        .stat-item-modern.stat-total {
            border-color: #3b82f6;
        }
        
        .stat-item-modern.stat-total i {
            color: #3b82f6;
        }
        
        .stat-content {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .students-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .student-card-modern {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .student-card-modern.student-animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .student-card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .student-card-modern.student-submitted {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        
        .student-card-modern.student-overdue {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .student-avatar-modern {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .student-info-modern {
            flex: 1;
            min-width: 0;
        }
        
        .student-name-modern {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .student-status-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .student-status-submitted {
            color: #166534;
        }
        
        .student-status-pending {
            color: #d97706;
        }
        
        .student-status-overdue {
            color: #dc2626;
        }
        
        .student-actions-modern {
            flex-shrink: 0;
        }
        
        .btn-view-file-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-view-file-modern:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
        }
        
        /* Styles pour la modal de suppression */
        .delete-modal-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .delete-modal-modern.modal-show {
            opacity: 1;
        }
        
        .delete-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        
        .delete-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .delete-modal-header {
            padding: 2rem 2rem 1rem;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .delete-icon-warning {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #ef4444;
            border: 3px solid #fecaca;
        }
        
        .delete-modal-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .delete-modal-body {
            padding: 1.5rem 2rem;
            text-align: center;
        }
        
        .delete-modal-body p {
            font-size: 1.1rem;
            color: #4b5563;
            margin-bottom: 1.5rem;
        }
        
        .homework-info-delete {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1.5rem;
        }
        
        .homework-info-delete i {
            color: #3b82f6;
            font-size: 1.2rem;
        }
        
        .homework-title-to-delete {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
        }
        
        .warning-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #fffbeb;
            color: #d97706;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #fed7aa;
            font-size: 0.9rem;
        }
        
        .warning-message i {
            color: #f59e0b;
        }
        
        .delete-modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-cancel-delete {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel-delete:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(107, 114, 128, 0.3);
        }
        
        .btn-confirm-delete {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm-delete:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-confirm-delete:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .notification-modern {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-width: 350px;
            padding: 1rem 1.5rem;
            border-left: 4px solid #3b82f6;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }
        
        .notification-modern.notification-show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .notification-modern.notification-success {
            border-left-color: #22c55e;
        }
        
        .notification-modern.notification-error {
            border-left-color: #ef4444;
        }
        
        .notification-modern.notification-warning {
            border-left-color: #f59e0b;
        }
        
        .notification-content-modern {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
        
        .notification-modern.notification-success .notification-content-modern i {
            color: #22c55e;
        }
        
        .notification-modern.notification-error .notification-content-modern i {
            color: #ef4444;
        }
        
        .notification-modern.notification-warning .notification-content-modern i {
            color: #f59e0b;
        }
        
        .notification-modern.notification-info .notification-content-modern i {
            color: #3b82f6;
        }
        
        .notification-close-modern {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .notification-close-modern:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .homework-title-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .homework-actions-modern {
                width: 100%;
                justify-content: space-between;
            }
            
            .homework-dates-modern {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .submissions-stats-modern {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .students-grid-modern {
                grid-template-columns: 1fr;
            }
            
            .student-card-modern {
                flex-direction: column;
                text-align: center;
            }
            
            .notification-modern {
                min-width: 300px;
                right: 10px;
                left: 10px;
            }
            
            .delete-modal-content {
                width: 95%;
                margin: 1rem;
            }
            
            .delete-modal-footer {
                flex-direction: column;
            }
        }
        
        /* Transitions pour les vues */
        #classes-view, #class-detail-view {
            transition: opacity 0.3s ease;
        }
    `;
    
    document.head.appendChild(style);
}

// Initialiser les styles au chargement
document.addEventListener('DOMContentLoaded', function() {
    addModernStyles();
});

// Ajouter les styles immédiatement si le DOM est déjà chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addModernStyles);
} else {
    addModernStyles();
}