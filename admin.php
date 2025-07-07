<?php
session_start();

// ✅ CONTRÔLE D'ACCÈS - Seuls les administrateurs peuvent accéder
require_once 'check_access.php';
checkAccess(['G_Tous_Personnel_Admin']);

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    // Redirige l'utilisateur vers la page de connexion si non connecté
    header("Location: login.php");
    exit();
}

// Récupère le nom complet de l'utilisateur connecté (si défini dans la session)
$fullname = $_SESSION['fullname'] ?? 'Nom non défini';
$username = $_SESSION['username'];

// Extraction prénom et nom
$parts = explode(" ", $fullname);
$prenom = $parts[0] ?? '';
$nom = $parts[1] ?? '';

// Recherche de la photo avec extensions valides
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

// Affichage des messages de session (erreur ou succès)
if (isset($_SESSION['message'])) {
    echo '<div class="message">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}

// =================== Récupération des classes LDAP ===================
$liste_classes = [];
$ldapconn = ldap_connect("ldap://192.168.20.132");

if ($ldapconn) {
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    $ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\AndrypL1G2", "Test@1234");

    if ($ldapbind) {
        // Recherche dans l'OU=Groupes
        $base_dn = "OU=Groupes,DC=educonnect,DC=mg"; // Base DN pour l'OU Groupes
        $filter = "(cn=G_L*)"; // Recherche des groupes G_L
        $search = ldap_search($ldapconn, $base_dn, $filter, ["cn"]);

        if ($search) {
            $entries = ldap_get_entries($ldapconn, $search);
            // Vérification et récupération des classes
            for ($i = 0; $i < $entries["count"]; $i++) {
                $cn = $entries[$i]["cn"][0];
                if (preg_match('/^G_([A-Za-z0-9]+)/', $cn, $match)) {
                    $liste_classes[] = $match[1]; // On extrait et ajoute la classe
                }
            }
        } else {
            echo "Erreur recherche LDAP : " . ldap_error($ldapconn);
        }

        ldap_unbind($ldapconn);
    } else {
        echo "Erreur de liaison LDAP : " . ldap_error($ldapconn);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Administrateur - <?php echo htmlspecialchars($fullname); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="file-input-styles.css">
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
                    <i class="fas fa-user-shield"></i>
                    Membre de la Scolarité
                </span>
                <span class="meta-item">
                    <i class="fas fa-cog"></i>
                    Administrateur
                </span>
            </div>
        </div>
    </div>
    <div class="header-actions">
        <a href="gestionnaire_annonce.php" class="btn btn-primary">
            <i class="fas fa-history"></i>
            Historique des Annonces
        </a>
        <!-- Nouveau bouton pour les absences et retards -->
        <a href="gestionnaire_absence.php" class="btn btn-warning">
            <i class="fas fa-clock"></i>
            Absences/Retards
        </a>
        <a href="logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </div>
</header>

    <div class="main-content">
        <div class="content-left">
            <section class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn"></i> Envoyer une annonce à tous les élèves</h3>
                </div>
                <div class="card-body">
                    <form action="envoyer_annonce.php" method="POST" enctype="multipart/form-data" class="form-modern">
                        <div class="form-group">
                            <label for="titre"><i class="fas fa-heading"></i> Titre de l'annonce</label>
                            <input type="text" name="titre" id="titre" required placeholder="Ex: Réunion parents-professeurs">
                        </div>
                        <div class="form-group">
                            <label for="contenu"><i class="fas fa-file-alt"></i> Contenu du message</label>
                            <textarea name="contenu" id="contenu" rows="5" required placeholder="Rédigez votre annonce..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="fichier"><i class="fas fa-paperclip"></i> Joindre un fichier (optionnel)</label>
                            <div class="file-input-container">
                                <div class="file-input-wrapper" id="fileInputWrapper">
                                    <input type="file" name="fichier" id="fichier" accept=".pdf,.doc,.docx,.jpg,.png" class="file-input-hidden">
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
                                    Formats acceptés: PDF, DOC, DOCX, JPG, PNG • Taille max: 10MB
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-paper-plane"></i> Envoyer l'annonce
                        </button>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Enregistrer un retard ou une absence</h3>
                </div>
                <div class="card-body">
                    <form action="enregistrer_absence.php" method="POST" class="form-modern">
                        <div class="form-group">
                            <label for="eleve"><i class="fas fa-user-graduate"></i> Nom d'utilisateur de l'élève</label>
                            <input type="text" name="eleve" id="eleve" required placeholder="Ex: marie.dubois">
                        </div>
                        
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" name="date" id="date" required>
                        </div>
                        <div class="form-group">
                            <label for="heure"><i class="fas fa-clock"></i> Heure (optionnel)</label>
                            <input type="time" name="heure" id="heure">
                        </div>
                        <div class="form-group">
                            <label for="type"><i class="fas fa-exclamation-triangle"></i> Type</label>
                            <select name="type" id="type" required>
                                <option value="">-- Sélectionner le type --</option>
                                <option value="retard">Retard</option>
                                <option value="absence">Absence</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="motif"><i class="fas fa-comment"></i> Motif</label>
                            <textarea name="motif" id="motif" rows="3" placeholder="Précisez le motif (optionnel)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-full">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </section>
            <section class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Gestion de l'emploi du temps</h3>
                </div>
                <div class="card-body">
                    <form action="gestion_edt.php" method="get" class="form-modern">
                        <div class="form-group">
                            <label for="classe"><i class="fas fa-users"></i> Choisir une classe</label>
                            <select name="classe" id="classe" required>
                                <option value="">-- Sélectionner une classe --</option>
                                <?php
                                foreach ($liste_classes as $classe) {
                                    echo "<option value=\"$classe\">$classe</option>";
                                }
                                ?>

                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-calendar-check"></i> Gérer cette classe
                        </button>
                    </form>
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
                            <input type="hidden" name="redirect_back" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
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
        </div>
    </div>
</div>
<script src="scripteleve.js"></script>
<script>
// Gestion de l'upload de photo
document.getElementById('photoInput').addEventListener('change', function () {
  document.getElementById('uploadBtn').style.display = 'inline-block';
});

// Gestion du fichier personnalisé pour les annonces
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