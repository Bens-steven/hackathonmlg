<?php
session_start();



// ✅ CONTRÔLE D'ACCÈS - Seuls les professeurs peuvent accéder
require_once 'check_access.php';
checkAccess(['G_Tous_Professeurs']);

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Récupérer le message de la session, s'il existe
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
unset($_SESSION['message']);

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

// Extraction prénom et nom
$parts = explode(" ", $nom_complet);
$prenom = $parts[0] ?? '';
$nom = $parts[1] ?? '';

// Recherche de la photo avec extensions valides (comme dans eleve.php)
$extensions = ['jpg', 'png', 'gif'];
$photo_path = '';
foreach ($extensions as $ext) {
    if (file_exists("photos/$username.$ext")) {
        $photo_path = "photos/$username.$ext";
        break;
    }
}

// Si aucune photo trouvée, générer un avatar SVG avec initiales
if (!$photo_path) {
    $initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    $photo_path = "data:image/svg+xml;base64," . base64_encode('
        <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
            <circle cx="75" cy="75" r="75" fill="#9CA3AF"/>
            <text x="75" y="85" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>
    ');
}

// Déterminer la matière
$matiere = "";
if (in_array("G_Mathematique", $user_groups)) {
    $_SESSION['matiere'] = "Mathématiques";
    $matiere = "Mathématiques";
} elseif (in_array("G_Francais", $user_groups)) {
    $_SESSION['matiere'] = "Français";
    $matiere = "Français";
} elseif (in_array("G_Histoire", $user_groups)) {
    $_SESSION['matiere'] = "Histoire";
    $matiere = "Histoire";
} elseif (in_array("G_Physique", $user_groups)) {
    $_SESSION['matiere'] = "Physique";
    $matiere = "Physique";
} else {
    $_SESSION['matiere'] = "Autre matière";
    $matiere = "Autre matière";
}

// Déterminer la classe selon les groupes AD
$classe = "";
foreach (["L1G1", "L1G2", "L2G1", "L2G2"] as $c) {
    if (in_array("G_" . $c, $user_groups)) {
        $_SESSION['classe'] = $c;
        $classe = $c;
        break;
    }
}

// Connexion MySQL pour emploi du temps
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
$mysqli->set_charset("utf8mb4");
if ($mysqli->connect_error) {
    die("Erreur MySQL : " . $mysqli->connect_error);
}

$jours_fr = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

$jour_actuel = $jours_fr[date('l')];

// Emploi du temps du jour actuel
$edt_today_query = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE professeur = ? AND jour_semaine = ? ORDER BY heure_debut");
$edt_today_query->bind_param("ss", $nom_complet, $jour_actuel);
$edt_today_query->execute();
$edt_today_result = $edt_today_query->get_result();

// Compter le nombre total de cours dans la semaine
$edt_count_query = $mysqli->prepare("SELECT COUNT(*) as total FROM emplois_du_temps WHERE professeur = ?");
$edt_count_query->bind_param("s", $nom_complet);
$edt_count_query->execute();
$edt_count_result = $edt_count_query->get_result();
$total_cours = $edt_count_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Professeur - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="file-input-styles.css">
    <link rel="stylesheet" href="schedule-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <header class="profile-header">
        <div class="profile-info">
            <div class="avatar-container">
                <img src="<?php echo $photo_path . (str_starts_with($photo_path, 'data:image') ? '' : '?v=' . time()); ?>" alt="Photo de profil" class="avatar" id="profileImage">
                <div class="status-indicator"></div>
            </div>
            <div class="user-details">
                <h1 class="user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                <p class="username">@<?php echo htmlspecialchars($username); ?></p>
                <div class="user-meta">
                    <span class="meta-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        Professeur de <?php echo htmlspecialchars($matiere); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-user-tie"></i>
                        Enseignant
                    </span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="gestionnaire_notes.php" class="btn btn-notes">
                <i class="fas fa-clipboard-list"></i>
                Gestionnaire des Notes
            </a>
            <a href="gestionnaire_devoirs.php" class="btn btn-homework">
                <i class="fas fa-tasks"></i>
                Gestionnaire de Devoirs
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="notification-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="content-left">
            <section class="card">
                <div class="card-header">
                    <h3><i class="fas fa-star"></i> Envoyer une note à un élève</h3>
                </div>
                <div class="card-body">
                    <form action="enregistrer_note.php" method="post" class="form-modern">
                        <div class="form-group">
                            <label for="eleve"><i class="fas fa-user-graduate"></i> Nom d'utilisateur de l'élève</label>
                            <input type="text" name="eleve" id="eleve" required placeholder="Ex: marie.dubois">
                        </div>
                        <input type="hidden" name="matiere" value="<?php echo htmlspecialchars($matiere); ?>">
                        <input type="hidden" name="classe" value="<?php echo htmlspecialchars($classe); ?>">
                        <div class="form-group">
                            <label for="note"><i class="fas fa-chart-line"></i> Note sur 20</label>
                            <input type="number" name="note" id="note" min="0" max="20" step="0.5" required placeholder="Ex: 15.5">
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-paper-plane"></i> Envoyer la note
                        </button>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tasks"></i> Envoyer un devoir à une classe</h3>
                </div>
                <div class="card-body">
                    <form action="enregistrer_devoir.php" method="post" enctype="multipart/form-data" class="form-modern">
                        <input type="hidden" name="matiere" value="<?php echo htmlspecialchars($matiere); ?>">
                        <div class="form-group">
                            <label for="classe"><i class="fas fa-users"></i> Classe ciblée</label>
                            <select name="classe" id="classe" required>
                                <option value="L1G1">L1G1</option>
                                <option value="L1G2">L1G2</option>
                                <option value="L2G1">L2G1</option>
                                <option value="L2G2">L2G2</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="titre"><i class="fas fa-heading"></i> Titre du devoir</label>
                            <input type="text" name="titre" id="titre" required placeholder="Ex: Exercices chapitre 5">
                        </div>
                        <div class="form-group">
                            <label for="contenu"><i class="fas fa-file-alt"></i> Contenu / consignes</label>
                            <textarea name="contenu" id="contenu" rows="5" required placeholder="Décrivez les consignes du devoir..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="fichier"><i class="fas fa-paperclip"></i> Pièce jointe (optionnel)</label>
                            <div class="file-input-container">
                                <div class="file-input-wrapper" id="fileInputWrapper">
                                    <input type="file" name="fichier" id="fichier" accept=".pdf,.doc,.docx,.txt,.jpg,.png" class="file-input-hidden">
                                    <div class="file-input-content">
                                        <div class="file-input-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-input-text">
                                            <div class="file-input-title">Cliquez pour sélectionner un fichier</div>
                                            <div class="file-input-subtitle">ou glissez-déposez votre fichier ici</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="file-info" id="fileInfo">
                                    <div class="file-info-icon">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="file-info-details">
                                        <div class="file-info-name" id="fileName"></div>
                                        <div class="file-info-size" id="fileSize"></div>
                                    </div>
                                    <button type="button" class="file-info-remove" id="removeFile">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="file-constraints">
                                    <i class="fas fa-info-circle"></i>
                                    Formats acceptés: PDF, DOC, DOCX, TXT, JPG, PNG • Taille max: 10MB
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="date_limite"><i class="fas fa-calendar-alt"></i> Date limite de rendu</label>
                            <input type="datetime-local" name="date_limite" id="date_limite" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-full">
                            <i class="fas fa-share"></i> Envoyer le devoir
                        </button>
                    </form>
                </div>
            </section>

            <section class="card schedule-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-day"></i> Mon emploi du temps - <?php echo $jour_actuel; ?></h3>
                    <div class="schedule-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('d/m/Y'); ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($edt_today_result->num_rows > 0): ?>
                        <div class="today-schedule">
                            <?php 
                            $current_time = date('H:i');
                            while ($cours = $edt_today_result->fetch_assoc()): 
                                $is_current = ($current_time >= $cours['heure_debut'] && $current_time <= $cours['heure_fin']);
                                $is_upcoming = ($current_time < $cours['heure_debut']);
                                $is_past = ($current_time > $cours['heure_fin']);
                                
                                $status_class = '';
                                $status_text = '';
                                $status_icon = '';
                                
                                if ($is_current) {
                                    $status_class = 'current';
                                    $status_text = 'En cours';
                                    $status_icon = 'fas fa-play-circle';
                                } elseif ($is_upcoming) {
                                    $status_class = 'upcoming';
                                    $status_text = 'À venir';
                                    $status_icon = 'fas fa-clock';
                                } else {
                                    $status_class = 'past';
                                    $status_text = 'Terminé';
                                    $status_icon = 'fas fa-check-circle';
                                }
                            ?>
                                <div class="course-item <?php echo $status_class; ?>" data-subject="<?php echo htmlspecialchars($cours['matiere']); ?>">
                                    <div class="course-time">
                                        <div class="time-range">
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($cours['heure_debut'] . ' - ' . $cours['heure_fin']); ?>
                                        </div>
                                        <div class="course-status <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?>"></i>
                                            <?php echo $status_text; ?>
                                        </div>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-subject">
                                            <?php echo htmlspecialchars($cours['matiere']); ?> - <?php echo htmlspecialchars($cours['classe']); ?>
                                        </div>
                                        <div class="course-info">
                                            <span class="course-room">
                                                <i class="fas fa-door-open"></i>
                                                Salle <?php echo htmlspecialchars($cours['salle']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php if ($total_cours > 0): ?>
                            <div class="show-all-container">
                                <a href="emploi-du-temps-complet.php" class="btn btn-schedule btn-full">
                                    <i class="fas fa-calendar-week"></i> Voir l'emploi du temps complet (<?php echo $total_cours; ?> cours cette semaine)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-schedule">
                            <div class="empty-schedule-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h4>Aucun cours prévu aujourd'hui</h4>
                            <p>Vous n'avez pas de cours programmés ce jour.</p>
                            <?php if ($total_cours > 0): ?>
                                <a href="emploi-du-temps-complet.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-week"></i> Voir l'emploi du temps de la semaine
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="content-right">
            <section class="card photo-card">
                <div class="card-header">
                    <h3><i class="fas fa-camera"></i> Photo de profil</h3>
                </div>
                <div class="card-body">
                    <div class="photo-upload-container">
                        <div class="current-photo">
                            <img src="<?php echo $photo_path . (str_starts_with($photo_path, 'data:image') ? '' : '?v=' . time()); ?>" alt="Photo de profil" id="currentPhoto">
                            <div class="photo-overlay"><i class="fas fa-camera"></i></div>
                        </div>
                        <form action="upload_photo.php" method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="file" name="photo" accept="image/*" id="photoInput" required>
                            <label for="photoInput" class="btn btn-secondary">
                                <i class="fas fa-upload"></i> Changer la photo
                            </label>
                            <button type="submit" class="btn btn-primary" id="uploadBtn" style="display: none;">
                                <i class="fas fa-check"></i> Confirmer
                            </button>
                        </form>
                        <p class="upload-info">
                            Formats acceptés: JPG, PNG, GIF<br>
                            Taille max: 5MB
                        </p>
                    </div>
                </div>
            </section>

            <section class="card info-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Mes informations</h3>
                </div>
                <div class="card-body">
                    <div class="teacher-info-grid">
                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Prénom</span>
                                <span class="info-value"><?php echo htmlspecialchars($prenom); ?></span>
                            </div>
                        </div>
                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Nom</span>
                                <span class="info-value"><?php echo htmlspecialchars($nom); ?></span>
                            </div>
                        </div>
                        <div class="info-item-modern subject-info" data-subject="<?php echo htmlspecialchars($matiere); ?>">
                            <div class="info-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Matière enseignée</span>
                                <span class="info-value"><?php echo htmlspecialchars($matiere); ?></span>
                            </div>
                        </div>
                        <div class="info-item-modern">
                            <div class="info-icon">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Identifiant</span>
                                <span class="info-value">@<?php echo htmlspecialchars($username); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<script src="scripteleve.js"></script>
<script src="schedule-script.js"></script>
<script>
document.getElementById('photoInput').addEventListener('change', function () {
  document.getElementById('uploadBtn').style.display = 'inline-block';
});

// Gestion du fichier personnalisé pour les devoirs
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fichier');
    const fileInputWrapper = document.getElementById('fileInputWrapper');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function updateFileDisplay(file) {
        if (file) {
            fileInputWrapper.classList.add('has-file');
            fileInfo.classList.add('show');
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            
            // Changer l'icône et le texte
            const icon = fileInputWrapper.querySelector('.file-input-icon i');
            const title = fileInputWrapper.querySelector('.file-input-title');
            const subtitle = fileInputWrapper.querySelector('.file-input-subtitle');
            
            icon.className = 'fas fa-check-circle';
            title.textContent = 'Fichier sélectionné';
            subtitle.textContent = 'Cliquez pour changer de fichier';
        } else {
            fileInputWrapper.classList.remove('has-file');
            fileInfo.classList.remove('show');
            
            // Remettre l'icône et le texte par défaut
            const icon = fileInputWrapper.querySelector('.file-input-icon i');
            const title = fileInputWrapper.querySelector('.file-input-title');
            const subtitle = fileInputWrapper.querySelector('.file-input-subtitle');
            
            icon.className = 'fas fa-cloud-upload-alt';
            title.textContent = 'Cliquez pour sélectionner un fichier';
            subtitle.textContent = 'ou glissez-déposez votre fichier ici';
        }
    }

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        updateFileDisplay(file);
    });

    removeFile.addEventListener('click', function() {
        fileInput.value = '';
        updateFileDisplay(null);
    });

    // Support du glisser-déposer
    fileInputWrapper.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileInputWrapper.style.borderColor = '#3b82f6';
        fileInputWrapper.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
    });

    fileInputWrapper.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileInputWrapper.style.borderColor = '';
        fileInputWrapper.style.backgroundColor = '';
    });

    fileInputWrapper.addEventListener('drop', function(e) {
        e.preventDefault();
        fileInputWrapper.style.borderColor = '';
        fileInputWrapper.style.backgroundColor = '';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileDisplay(files[0]);
        }
    });
});
</script>
</body>
</html>