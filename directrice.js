// Variables globales
let currentClass = '';
let currentStudent = '';
let currentSubject = '';
let currentTeacher = '';
let teachersData = {}; // ✅ Stocker les données des professeurs

// Navigation principale
function showMainView() {
    document.getElementById('main-view').style.display = 'block';
    document.getElementById('students-view').style.display = 'none';
    document.getElementById('teachers-view').style.display = 'none';
}

function showStudentsView() {
    console.log('🎯 showStudentsView() appelée');
    document.getElementById('main-view').style.display = 'none';
    document.getElementById('students-view').style.display = 'block';
    document.getElementById('teachers-view').style.display = 'none';
    
    // Réinitialiser les vues
    document.getElementById('classes-list').style.display = 'block';
    document.getElementById('class-students-view').style.display = 'none';
    document.getElementById('student-detail-view').style.display = 'none';
    
    // Charger les classes
    loadClasses();
}

function showTeachersView() {
    console.log('🎯 showTeachersView() appelée');
    document.getElementById('main-view').style.display = 'none';
    document.getElementById('students-view').style.display = 'none';
    document.getElementById('teachers-view').style.display = 'block';
    
    // Réinitialiser les vues
    document.getElementById('subjects-list').style.display = 'block';
    document.getElementById('subject-teachers-view').style.display = 'none';
    document.getElementById('teacher-detail-view').style.display = 'none';
    
    // Charger les professeurs
    loadTeachers();
}

// Gestion des classes
function loadClasses() {
    console.log('🔄 Chargement des classes...');
    
    // Afficher un indicateur de chargement
    const container = document.getElementById('classesGrid');
    if (!container) {
        console.error('❌ Container classesGrid non trouvé');
        return;
    }
    
    container.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem; color: #3b82f6;"></i>
            <h4>Chargement des classes...</h4>
            <p>Connexion à l'Active Directory en cours...</p>
        </div>
    `;
    
    // Connexion LDAP pour récupérer les classes
    fetch('get_classes_directrice.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        console.log('📡 Réponse reçue:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📊 Données reçues:', data);
        if (data.success) {
            displayClasses(data.classes);
        } else {
            console.error('❌ Erreur serveur:', data.message);
            showError('Erreur lors du chargement des classes: ' + data.message);
        }
    })
    .catch(error => {
        console.error('💥 Erreur réseau:', error);
        showError('Erreur de connexion: ' + error.message);
        
        // Afficher l'erreur dans le container
        if (container) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                    <h4>Erreur de chargement</h4>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="loadClasses()">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                </div>
            `;
        }
    });
}

function displayClasses(classes) {
    console.log('🎨 Affichage de', classes ? classes.length : 0, 'classes');
    const container = document.getElementById('classesGrid');
    
    if (!container) {
        console.error('❌ Container classesGrid non trouvé');
        return;
    }
    
    if (!classes || classes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                <h4>Aucune classe trouvée</h4>
                <p>Aucune classe n'est actuellement configurée dans l'Active Directory.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    classes.forEach(classe => {
        if (!classe || !classe.nom) {
            console.warn('⚠️ Classe invalide:', classe);
            return;
        }
        
        html += `
            <div class="class-card" onclick="loadStudentsOfClass('${escapeHtml(classe.nom)}')">
                <div class="class-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="class-info">
                    <h3>${escapeHtml(classe.nom)}</h3>
                    <p>Classe de ${escapeHtml(classe.nom)}</p>
                </div>
                <div class="class-stats">
                    <div class="stat-item">
                        <i class="fas fa-user-graduate"></i>
                        <span>${classe.nb_eleves || 0} élèves</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Moyenne: ${classe.moyenne || 0}/20</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function showClassesList() {
    document.getElementById('classes-list').style.display = 'block';
    document.getElementById('class-students-view').style.display = 'none';
    document.getElementById('student-detail-view').style.display = 'none';
    currentClass = '';
}

// Gestion des professeurs
function loadTeachers() {
    console.log('🔄 Chargement des professeurs...');
    
    // Afficher un indicateur de chargement
    const container = document.getElementById('subjectsGrid');
    if (!container) {
        console.error('❌ Container subjectsGrid non trouvé');
        return;
    }
    
    container.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem; color: #8b5cf6;"></i>
            <h4>Chargement des professeurs...</h4>
            <p>Récupération des données depuis l'Active Directory...</p>
        </div>
    `;
    
    // Récupérer les professeurs par matière
    fetch('get_teachers_directrice.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        console.log('📡 Réponse professeurs reçue:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('👨‍🏫 Données professeurs reçues:', data);
        if (data.success) {
            // ✅ Stocker les données pour usage ultérieur
            teachersData = data.teachers_by_subject;
            displaySubjects(data.teachers_by_subject);
        } else {
            console.error('❌ Erreur professeurs:', data.message);
            showError('Erreur lors du chargement des professeurs: ' + data.message);
        }
    })
    .catch(error => {
        console.error('💥 Erreur réseau professeurs:', error);
        showError('Erreur de connexion: ' + error.message);
        
        // Afficher l'erreur dans le container
        if (container) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                    <h4>Erreur de chargement</h4>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="loadTeachers()">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                </div>
            `;
        }
    });
}

function displaySubjects(teachersBySubject) {
    console.log('🎨 Affichage des matières:', teachersBySubject);
    const container = document.getElementById('subjectsGrid');
    
    if (!container) {
        console.error('❌ Container subjectsGrid non trouvé');
        return;
    }
    
    if (!teachersBySubject || Object.keys(teachersBySubject).length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                <h4>Aucun professeur trouvé</h4>
                <p>Aucun professeur n'est actuellement configuré dans l'Active Directory.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    // Couleurs par matière
    const subjectColors = {
        'Mathématiques': '#3b82f6',
        'Français': '#22c55e',
        'Histoire': '#f59e0b',
        'Physique': '#8b5cf6',
        'Chimie': '#f97316',
        'Biologie': '#14b8a6',
        'Anglais': '#ec4899',
        'Sport': '#ef4444',
        'EPS': '#ef4444',
        'Géographie': '#06b6d4',
        'Philosophie': '#6366f1',
        'Économie': '#84cc16'
    };
    
    Object.entries(teachersBySubject).forEach(([subject, teachers]) => {
        const color = subjectColors[subject] || '#6b7280';
        const totalClasses = teachers.reduce((sum, teacher) => sum + teacher.total_classes, 0);
        
        html += `
            <div class="subject-card" onclick="loadTeachersOfSubject('${escapeHtml(subject)}')" style="--subject-color: ${color}">
                <div class="subject-icon" style="background: linear-gradient(135deg, ${color} 0%, ${color}dd 100%);">
                    <i class="fas fa-${getSubjectIcon(subject)}"></i>
                </div>
                <div class="subject-info">
                    <h3>${escapeHtml(subject)}</h3>
                    <p>Équipe pédagogique</p>
                </div>
                <div class="subject-stats">
                    <div class="stat-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>${teachers.length} professeur${teachers.length > 1 ? 's' : ''}</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span>${totalClasses} classe${totalClasses > 1 ? 's' : ''}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function getSubjectIcon(subject) {
    const icons = {
        'Mathématiques': 'calculator',
        'Français': 'book',
        'Histoire': 'landmark',
        'Physique': 'atom',
        'Chimie': 'flask',
        'Biologie': 'dna',
        'Anglais': 'globe',
        'Sport': 'running',
        'EPS': 'running',
        'Géographie': 'map',
        'Philosophie': 'brain',
        'Économie': 'chart-line'
    };
    return icons[subject] || 'book';
}

// ✅ FONCTION CORRIGÉE
function loadTeachersOfSubject(subject) {
    console.log('👨‍🏫 Chargement des professeurs de:', subject);
    currentSubject = subject;
    
    document.getElementById('subjects-list').style.display = 'none';
    document.getElementById('subject-teachers-view').style.display = 'block';
    document.getElementById('teacher-detail-view').style.display = 'none';
    
    const titleElement = document.getElementById('subject-title');
    if (titleElement) {
        titleElement.textContent = subject;
    }
    
    // ✅ Afficher les professeurs de cette matière spécifique
    displayTeachersOfSubject(subject);
}

// ✅ NOUVELLE FONCTION pour afficher les professeurs d'une matière
function displayTeachersOfSubject(subject) {
    console.log('🎨 Affichage des professeurs de:', subject);
    const container = document.getElementById('teachersGrid');
    
    if (!container) {
        console.error('❌ Container teachersGrid non trouvé');
        return;
    }
    
    // Vérifier si on a les données pour cette matière
    if (!teachersData[subject] || teachersData[subject].length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                <h4>Aucun professeur trouvé</h4>
                <p>Aucun professeur n'enseigne actuellement ${subject}.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    teachersData[subject].forEach(teacher => {
        if (!teacher || !teacher.username) {
            console.warn('⚠️ Professeur invalide:', teacher);
            return;
        }
        
        const initials = getInitials(teacher.display_name || teacher.username);
        const moyenne = teacher.class_averages.length > 0 
            ? (teacher.class_averages.reduce((sum, ca) => sum + parseFloat(ca.moyenne), 0) / teacher.class_averages.length).toFixed(1)
            : 0;
        const gradeClass = getGradeClass(moyenne);
        
        html += `
            <div class="teacher-card" onclick="showTeacherDetail('${escapeHtml(teacher.username)}', '${escapeHtml(subject)}')">
                <div class="teacher-avatar">
                    ${initials}
                </div>
                <div class="teacher-info">
                    <h4>${escapeHtml(teacher.display_name || teacher.username)}</h4>
                    <p class="teacher-username">@${escapeHtml(teacher.username)}</p>
                    <p class="teacher-subject">${escapeHtml(subject)}</p>
                </div>
                <div class="teacher-stats">
                    <div class="teacher-stat">
                        <span class="teacher-stat-label">Classes</span>
                        <span class="teacher-stat-value">${teacher.total_classes}</span>
                    </div>
                    <div class="teacher-stat">
                        <span class="teacher-stat-label">Moyenne</span>
                        <span class="teacher-stat-value ${gradeClass}">${moyenne}/20</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// ✅ NOUVELLE FONCTION pour afficher les détails d'un professeur
function showTeacherDetail(username, subject) {
    console.log('👤 Affichage des détails du professeur:', username, 'matière:', subject);
    
    if (!username || !subject) {
        console.error('❌ Nom d\'utilisateur ou matière manquant');
        showError('Nom d\'utilisateur ou matière manquant');
        return;
    }
    
    currentTeacher = username;
    
    document.getElementById('subject-teachers-view').style.display = 'none';
    document.getElementById('teacher-detail-view').style.display = 'block';
    
    // Afficher un indicateur de chargement
    const container = document.getElementById('teacherCardContainer');
    if (container) {
        container.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem; color: #8b5cf6;"></i>
                <h4>Chargement des détails...</h4>
                <p>Récupération des données du professeur...</p>
            </div>
        `;
    }
    
    // Charger les détails du professeur avec FormData
    const formData = new FormData();
    formData.append('username', username);
    formData.append('matiere', subject);
    
    fetch('get_teacher_details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('👤 Détails professeur reçus:', data);
        if (data.success) {
            displayTeacherCard(data.teacher);
        } else {
            console.error('❌ Erreur détails:', data.message);
            showError('Erreur lors du chargement des détails: ' + data.message);
        }
    })
    .catch(error => {
        console.error('💥 Erreur détails professeur:', error);
        showError('Erreur de connexion: ' + error.message);
    });
}

// ✅ NOUVELLE FONCTION pour afficher la carte du professeur
function displayTeacherCard(teacher) {
    const container = document.getElementById('teacherCardContainer');
    if (!container) {
        console.error('❌ Container teacherCardContainer non trouvé');
        return;
    }
    
    if (!teacher) {
        console.error('❌ Données professeur manquantes');
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                <h4>Erreur</h4>
                <p>Impossible de charger les données du professeur</p>
            </div>
        `;
        return;
    }
    
    const initials = getInitials(teacher.display_name || teacher.username);
    
    let classesHtml = '';
    if (teacher.detailed_stats && teacher.detailed_stats.length > 0) {
        teacher.detailed_stats.forEach(classData => {
            const gradeClass = getGradeClass(classData.moyenne);
            classesHtml += `
                <div class="class-item">
                    <span class="class-name">${escapeHtml(classData.classe)}</span>
                    <div class="class-details">
                        <span class="class-average ${gradeClass}">${classData.moyenne}/20</span>
                        <span class="class-students">${classData.nb_eleves} élèves</span>
                        <span class="class-notes">${classData.nb_notes} notes</span>
                    </div>
                </div>
            `;
        });
    } else {
        classesHtml = '<p class="empty-state">Aucune donnée de classe disponible</p>';
    }
    
    const html = `
        <div class="teacher-identity-card">
            <div class="teacher-header">
                <div class="teacher-photo">
                    ${initials}
                </div>
                <div class="teacher-basic-info">
                    <h2>${escapeHtml(teacher.display_name || teacher.username)}</h2>
                    <p class="username">@${escapeHtml(teacher.username)}</p>
                    <div class="teacher-status">
                        <span class="status-badge teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Professeur
                        </span>
                        <span class="status-badge subject">
                            <i class="fas fa-book"></i>
                            ${escapeHtml(teacher.matiere)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="teacher-details">
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-users"></i>
                        Classes enseignées
                    </h3>
                    <div class="classes-list">
                        ${classesHtml}
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        Statistiques générales
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-users"></i>
                                Total élèves
                            </div>
                            <div class="stat-value">${teacher.summary.total_students}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-door-open"></i>
                                Classes actives
                            </div>
                            <div class="stat-value">${teacher.summary.classes_with_data}/${teacher.summary.total_classes}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function showSubjectsList() {
    document.getElementById('subjects-list').style.display = 'block';
    document.getElementById('subject-teachers-view').style.display = 'none';
    document.getElementById('teacher-detail-view').style.display = 'none';
    currentSubject = '';
}

function showTeachersOfSubject() {
    document.getElementById('subject-teachers-view').style.display = 'block';
    document.getElementById('teacher-detail-view').style.display = 'none';
    currentTeacher = '';
}

// Gestion des élèves d'une classe
function loadStudentsOfClass(className) {
    console.log('👥 Chargement des élèves de la classe:', className);
    
    if (!className) {
        console.error('❌ Nom de classe manquant');
        showError('Nom de classe manquant');
        return;
    }
    
    currentClass = className;
    
    document.getElementById('classes-list').style.display = 'none';
    document.getElementById('class-students-view').style.display = 'block';
    document.getElementById('student-detail-view').style.display = 'none';
    
    const titleElement = document.getElementById('class-title');
    if (titleElement) {
        titleElement.textContent = `Classe ${className}`;
    }
    
    // Afficher un indicateur de chargement
    const container = document.getElementById('studentsGrid');
    if (!container) {
        console.error('❌ Container studentsGrid non trouvé');
        return;
    }
    
    container.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem; color: #3b82f6;"></i>
            <h4>Chargement des élèves...</h4>
            <p>Récupération des données depuis l'Active Directory...</p>
        </div>
    `;
    
    // Charger les élèves via AJAX avec FormData pour s'assurer que les données sont bien envoyées
    const formData = new FormData();
    formData.append('classe', className);
    
    fetch('get_students_directrice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Réponse élèves reçue:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('👥 Données élèves reçues:', data);
        if (data.success) {
            displayStudents(data.students);
        } else {
            console.error('❌ Erreur élèves:', data.message);
            showError('Erreur lors du chargement des élèves: ' + data.message);
        }
    })
    .catch(error => {
        console.error('💥 Erreur réseau élèves:', error);
        showError('Erreur de connexion: ' + error.message);
        
        // Afficher l'erreur dans le container
        if (container) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #ef4444;"></i>
                    <h4>Erreur de chargement</h4>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="loadStudentsOfClass('${escapeHtml(className)}')">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                </div>
            `;
        }
    });
}

// Fonction pour afficher les élèves
function displayStudents(students) {
    console.log('🎨 Affichage de', students ? students.length : 0, 'élèves');
    const container = document.getElementById('studentsGrid');
    
    if (!container) {
        console.error('❌ Container studentsGrid non trouvé');
        return;
    }
    
    if (!students || students.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-graduate" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                <h4>Aucun élève trouvé</h4>
                <p>Cette classe ne contient aucun élève.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    students.forEach(student => {
        if (!student || !student.username) {
            console.warn('⚠️ Élève invalide:', student);
            return;
        }
        
        const initials = getInitials(student.username);
        const gradeClass = getGradeClass(student.moyenne_generale);
        
        html += `
            <div class="student-card" onclick="showStudentDetail('${escapeHtml(student.username)}')">
                <div class="student-avatar">
                    ${initials}
                </div>
                <div class="student-info">
                    <h4>${escapeHtml(student.username)}</h4>
                    <p class="student-username">@${escapeHtml(student.username)}</p>
                </div>
                <div class="student-stats">
                    <div class="student-stat">
                        <span class="student-stat-label">Moyenne générale</span>
                        <span class="student-stat-value ${gradeClass}">${student.moyenne_generale || 0}/20</span>
                    </div>
                    <div class="student-stat">
                        <span class="student-stat-label">Absences</span>
                        <span class="student-stat-value absences">${student.nb_absences || 0}</span>
                    </div>
                    <div class="student-stat">
                        <span class="student-stat-label">Retards</span>
                        <span class="student-stat-value retards">${student.nb_retards || 0}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Gestion des détails d'un élève
function showStudentDetail(username) {
    console.log('👤 Affichage des détails de l\'élève:', username);
    
    if (!username) {
        console.error('❌ Nom d\'utilisateur manquant');
        showError('Nom d\'utilisateur manquant');
        return;
    }
    
    if (!currentClass) {
        console.error('❌ Classe courante non définie');
        showError('Classe courante non définie');
        return;
    }
    
    currentStudent = username;
    
    document.getElementById('class-students-view').style.display = 'none';
    document.getElementById('student-detail-view').style.display = 'block';
    
    // Charger les détails de l'élève avec FormData
    const formData = new FormData();
    formData.append('username', username);
    formData.append('classe', currentClass);
    
    fetch('get_student_details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('👤 Détails élève reçus:', data);
        if (data.success) {
            displayStudentCard(data.student);
        } else {
            console.error('❌ Erreur détails:', data.message);
            showError('Erreur lors du chargement des détails: ' + data.message);
        }
    })
    .catch(error => {
        console.error('💥 Erreur détails élève:', error);
        showError('Erreur de connexion: ' + error.message);
    });
}

function displayStudentCard(student) {
    const container = document.getElementById('studentCardContainer');
    if (!container) {
        console.error('❌ Container studentCardContainer non trouvé');
        return;
    }
    
    if (!student) {
        console.error('❌ Données élève manquantes');
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                <h4>Erreur</h4>
                <p>Impossible de charger les données de l'élève</p>
            </div>
        `;
        return;
    }
    
    const initials = getInitials(student.username);
    const generalGradeClass = getGradeClass(student.moyenne_generale);
    
    let gradesHtml = '';
    if (student.notes && student.notes.length > 0) {
        student.notes.forEach(note => {
            const gradeClass = getGradeClass(note.moyenne);
            gradesHtml += `
                <div class="grade-item">
                    <span class="grade-subject">${escapeHtml(note.matiere)}</span>
                    <span class="grade-value ${gradeClass}">${note.moyenne}/20</span>
                </div>
            `;
        });
    } else {
        gradesHtml = '<p class="empty-state">Aucune note disponible</p>';
    }
    
    const html = `
        <div class="student-identity-card">
            <div class="student-header">
                <div class="student-photo">
                    ${initials}
                </div>
                <div class="student-basic-info">
                    <h2>${escapeHtml(student.username)}</h2>
                    <p class="username">@${escapeHtml(student.username)}</p>
                    <div class="student-status">
                        <span class="status-badge student">
                            <i class="fas fa-user-graduate"></i>
                            Élève
                        </span>
                        <span class="status-badge class">
                            <i class="fas fa-users"></i>
                            Classe ${escapeHtml(student.classe)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="student-details">
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Notes par matière
                    </h3>
                    <div class="grades-list">
                        ${gradesHtml}
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-user-clock"></i>
                        Assiduité
                    </h3>
                    <div class="attendance-stats">
                        <div class="attendance-item absences">
                            <div class="attendance-label">
                                <i class="fas fa-user-times"></i>
                                Absences
                            </div>
                            <div class="attendance-value absences">${student.nb_absences || 0}</div>
                        </div>
                        <div class="attendance-item retards">
                            <div class="attendance-label">
                                <i class="fas fa-clock"></i>
                                Retards
                            </div>
                            <div class="attendance-value retards">${student.nb_retards || 0}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="general-average">
                <h3>
                    <i class="fas fa-trophy"></i>
                    Moyenne générale
                </h3>
                <div class="general-average-value ${generalGradeClass}">${student.moyenne_generale || 0}/20</div>
                <p>Résultat global de l'élève</p>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function showStudentsOfClass() {
    document.getElementById('class-students-view').style.display = 'block';
    document.getElementById('student-detail-view').style.display = 'none';
    currentStudent = '';
}

// Fonctions utilitaires
function getInitials(username) {
    if (!username) return '??';
    const parts = username.split(/[\s.]+/);
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
    }
    return username.substring(0, 2).toUpperCase();
}

function getGradeClass(grade) {
    const gradeValue = parseFloat(grade);
    if (isNaN(gradeValue)) return 'poor';
    if (gradeValue >= 16) return 'excellent';
    if (gradeValue >= 12) return 'good';
    if (gradeValue >= 10) return 'average';
    return 'poor';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    // Créer une notification de succès
    const notification = document.createElement('div');
    notification.className = 'notification-success';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
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
    console.error('🚨 Erreur:', message);
    
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
        max-width: 400px;
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

// Animation pour les notifications
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
    
    .loading-state, .error-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    
    .loading-state h4, .error-state h4 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }
    
    .loading-state p, .error-state p {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    .error-state {
        color: #ef4444;
    }
    
    .error-state h4 {
        color: #ef4444;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    
    .empty-state h4 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }
    
    .empty-state p {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Styles pour les cartes de matières */
    .subject-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .subject-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--subject-color, #8b5cf6);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .subject-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        border-color: var(--subject-color, #8b5cf6);
    }
    
    .subject-card:hover::before {
        transform: scaleX(1);
    }
    
    .subject-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 2rem;
        color: white;
        transition: transform 0.3s ease;
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
    }
    
    .subject-card:hover .subject-icon {
        transform: scale(1.1);
    }
    
    .subject-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .subject-info p {
        color: #6b7280;
        margin-bottom: 1.5rem;
    }
    
    .subject-stats {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #4b5563;
        font-size: 0.9rem;
        background: #f8fafc;
        padding: 0.5rem 1rem;
        border-radius: 20px;
    }
    
    .stat-item i {
        color: var(--subject-color, #8b5cf6);
    }
    
    /* Styles pour les cartes de professeurs */
    .teacher-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(139, 92, 246, 0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .teacher-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #8b5cf6;
    }
    
    .teacher-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    .teacher-info h4 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }
    
    .teacher-username {
        color: #6b7280;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .teacher-subject {
        color: #8b5cf6;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    
    .teacher-stats {
        display: flex;
        gap: 1rem;
        width: 100%;
    }
    
    .teacher-stat {
        flex: 1;
        text-align: center;
    }
    
    .teacher-stat-label {
        display: block;
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .teacher-stat-value {
        display: block;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .teacher-stat-value.excellent {
        color: #22c55e;
    }
    
    .teacher-stat-value.good {
        color: #3b82f6;
    }
    
    .teacher-stat-value.average {
        color: #f59e0b;
    }
    
    .teacher-stat-value.poor {
        color: #ef4444;
    }
    
    /* Styles pour la carte d'identité du professeur */
    .teacher-identity-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.1);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .teacher-header {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        align-items: center;
    }
    
    .teacher-photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 2.5rem;
        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
    }
    
    .teacher-basic-info h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .teacher-basic-info .username {
        color: #6b7280;
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }
    
    .teacher-status {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .status-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f8fafc;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #4b5563;
        border: 1px solid #e5e7eb;
    }
    
    .status-badge.teacher {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
    }
    
    .status-badge.subject {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
    }
    
    .teacher-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .detail-section h3 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.3rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
    }
    
    .detail-section h3 i {
        color: #8b5cf6;
    }
    
    .classes-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .class-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .class-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 1.1rem;
    }
    
    .class-details {
        display: flex;
        gap: 1rem;
        font-size: 0.9rem;
    }
    
    .class-average {
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        background: white;
    }
    
    .class-students, .class-notes {
        color: #6b7280;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .stat-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        text-align: center;
    }
    
    .stat-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
    }
    
    .stat-label i {
        color: #8b5cf6;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    @media (max-width: 768px) {
        .teacher-header {
            flex-direction: column;
            text-align: center;
        }
        
        .teacher-details {
            grid-template-columns: 1fr;
        }
        
        .class-item {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }
        
        .class-details {
            justify-content: center;
        }
    }
`;
document.head.appendChild(style);