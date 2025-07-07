<?php
session_start();

// ✅ CONTRÔLE D'ACCÈS - Seuls les membres de la direction peuvent accéder
require_once 'check_access.php';
checkAccess(['G_Admin_Direction']);

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

// Vérification du groupe G_Admin_Direction
if (!in_array('G_Admin_Direction', $user_groups)) {
    header("Location: login.html");
    exit;
}

// Extraction prénom et nom
$parts = explode(" ", $nom_complet);
$prenom = isset($parts[0]) ? $parts[0] : '';
$nom = isset($parts[1]) ? $parts[1] : '';

// Photo de profil avec avatar par défaut
$photo_path = "photos/" . $username . ".jpg";
if (!file_exists($photo_path)) {
    $initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    $photo_path = "data:image/svg+xml;base64," . base64_encode('
        <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
            <circle cx="75" cy="75" r="75" fill="#9CA3AF"/>
            <text x="75" y="85" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>
    ');
}

// ===== CONNEXION LDAP POUR LES STATISTIQUES =====
$ldapconn = ldap_connect("ldap://192.168.20.132");
$nb_classes = 0;
$nb_eleves_total = 0;
$nb_professeurs_total = 0;

if ($ldapconn) {
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    
    $ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");
    
    if ($ldapbind) {
        // ===== COMPTER LES CLASSES =====
        // Rechercher tous les groupes de classes dans l'AD (G_L1G1, G_L1G2, etc.)
        $search_classes = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_L*G*)", ["cn"]);
        if ($search_classes) {
            $entries_classes = ldap_get_entries($ldapconn, $search_classes);
            
            for ($i = 0; $i < $entries_classes["count"]; $i++) {
                $groupName = $entries_classes[$i]["cn"][0];
                // Vérifier que c'est bien un groupe de classe (format G_L1G1, G_L2G2, etc.)
                if (preg_match('/^G_(L[1-2]G[1-2])$/', $groupName)) {
                    $nb_classes++;
                }
            }
        }
        
        // ===== COMPTER LES ÉLÈVES TOTAL =====
        // Rechercher le groupe G_Tous_Eleves pour compter tous les élèves
        $search_eleves = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_Tous_Eleves)", ["member"]);
        if ($search_eleves) {
            $entries_eleves = ldap_get_entries($ldapconn, $search_eleves);
            
            if ($entries_eleves["count"] > 0 && isset($entries_eleves[0]["member"])) {
                $nb_eleves_total = $entries_eleves[0]["member"]["count"];
            }
        }
        
        // ===== COMPTER LES PROFESSEURS TOTAL =====
        // Rechercher le groupe G_Tous_Professeurs pour compter tous les professeurs
        $search_professeurs = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_Tous_Professeurs)", ["member"]);
        if ($search_professeurs) {
            $entries_professeurs = ldap_get_entries($ldapconn, $search_professeurs);
            
            if ($entries_professeurs["count"] > 0 && isset($entries_professeurs[0]["member"])) {
                $nb_professeurs_total = $entries_professeurs[0]["member"]["count"];
            }
        }
    }
    
    ldap_unbind($ldapconn);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direction - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="directrice.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header avec profil -->
        <header class="profile-header">
            <div class="profile-info">
                <div class="avatar-container">
                    <img src="<?php echo $photo_path; ?>" alt="Photo de profil" class="avatar" id="profileImage">
                    <div class="status-indicator"></div>
                </div>
                <div class="user-details">
                    <h1 class="user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                    <p class="username">@<?php echo htmlspecialchars($username); ?></p>
                    <div class="user-meta">
                        <span class="meta-item">
                            <i class="fas fa-crown"></i>
                            Directrice
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-building"></i>
                            Administration
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>

        <!-- Vue principale: choix entre Élèves et Professeurs -->
        <div id="main-view">
            <div class="page-title">
                <h2>
                    <i class="fas fa-crown"></i>
                    Tableau de Bord Direction
                </h2>
                <p>Gestion et supervision de l'établissement</p>
            </div>

            <!-- Statistiques générales -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Classes</h3>
                        <div class="stat-value"><?php echo $nb_classes; ?></div>
                        <p>Classes actives</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Élèves</h3>
                        <div class="stat-value"><?php echo $nb_eleves_total; ?></div>
                        <p>Élèves inscrits</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Professeurs</h3>
                        <div class="stat-value"><?php echo $nb_professeurs_total; ?></div>
                        <p>Enseignants</p>
                    </div>
                </div>
            </div>

            <!-- Options principales -->
            <div class="main-options">
                <div class="option-card" onclick="showStudentsView()">
                    <div class="option-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="option-info">
                        <h3>Gestion des Élèves</h3>
                        <p>Consulter les classes, élèves et leurs performances</p>
                    </div>
                    <div class="option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <div class="option-card" onclick="showTeachersView()">
                    <div class="option-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="option-info">
                        <h3>Gestion des Professeurs</h3>
                        <p>Superviser les enseignants et leurs matières</p>
                    </div>
                    <div class="option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vue des élèves (cachée par défaut) -->
        <div id="students-view" class="section-view" style="display: none;">
            <div class="section-header">
                <h2>
                    <i class="fas fa-user-graduate"></i>
                    Gestion des Élèves
                </h2>
                <button class="btn btn-secondary" onclick="showMainView()">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </button>
            </div>

            <!-- Liste des classes -->
            <div id="classes-list">
                <div class="classes-grid" id="classesGrid">
                    <!-- Les classes seront chargées ici par JavaScript -->
                </div>
            </div>

            <!-- Vue détaillée d'une classe -->
            <div id="class-students-view" style="display: none;">
                <div class="class-header">
                    <h3 id="class-title">Classe</h3>
                    <div class="class-actions">
                        <button class="btn btn-secondary" onclick="showClassesList()">
                            <i class="fas fa-arrow-left"></i>
                            Retour aux classes
                        </button>
                    </div>
                </div>
                <div class="students-grid" id="studentsGrid">
                    <!-- Les élèves seront chargés ici par JavaScript -->
                </div>
            </div>

            <!-- Vue détaillée d'un élève -->
            <div id="student-detail-view" style="display: none;">
                <div class="student-header">
                    <button class="btn btn-secondary" onclick="showStudentsOfClass()">
                        <i class="fas fa-arrow-left"></i>
                        Retour aux élèves
                    </button>
                </div>
                <div class="student-card-container" id="studentCardContainer">
                    <!-- La carte d'identité de l'élève sera chargée ici -->
                </div>
            </div>
        </div>

        <!-- Vue des professeurs (cachée par défaut) -->
        <div id="teachers-view" class="section-view" style="display: none;">
            <div class="section-header">
                <h2>
                    <i class="fas fa-chalkboard-teacher"></i>
                    Gestion des Professeurs
                </h2>
                <button class="btn btn-secondary" onclick="showMainView()">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </button>
            </div>

            <!-- Liste des matières -->
            <div id="subjects-list">
                <div class="classes-grid" id="subjectsGrid">
                    <!-- Les matières seront chargées ici par JavaScript -->
                </div>
            </div>

            <!-- Vue détaillée d'une matière -->
            <div id="subject-teachers-view" style="display: none;">
                <div class="class-header">
                    <h3 id="subject-title">Matière</h3>
                    <div class="class-actions">
                        <button class="btn btn-secondary" onclick="showSubjectsList()">
                            <i class="fas fa-arrow-left"></i>
                            Retour aux matières
                        </button>
                    </div>
                </div>
                <div class="students-grid" id="teachersGrid">
                    <!-- Les professeurs seront chargés ici par JavaScript -->
                </div>
            </div>

            <!-- Vue détaillée d'un professeur -->
            <div id="teacher-detail-view" style="display: none;">
                <div class="student-header">
                    <button class="btn btn-secondary" onclick="showTeachersOfSubject()">
                        <i class="fas fa-arrow-left"></i>
                        Retour aux professeurs
                    </button>
                </div>
                <div class="student-card-container" id="teacherCardContainer">
                    <!-- La carte d'identité du professeur sera chargée ici -->
                </div>
            </div>
        </div>
    </div>

    <script src="scripteleve.js"></script>
    <script src="directrice.js"></script>
</body>
</html>