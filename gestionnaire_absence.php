<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

// Extraction prénom et nom
$parts = explode(" ", $nom_complet);
$prenom = isset($parts[0]) ? $parts[0] : '';
$nom = isset($parts[1]) ? $parts[1] : '';

// Photo de profil avec avatar par défaut
$photo_path = "photos/" . $username . ".jpg";
if (!file_exists($photo_path)) {
    // Créer un avatar par défaut avec les initiales
    $initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    $photo_path = "data:image/svg+xml;base64," . base64_encode('
        <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
            <circle cx="75" cy="75" r="75" fill="#9CA3AF"/>
            <text x="75" y="85" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>
    ');
}

// Connexion à MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) die("Erreur connexion MySQL: " . $mysqli->connect_error);

// Récupérer les classes distinctes avec statistiques d'absences et retards
$stmt = $mysqli->prepare("
    SELECT 
        classe,
        COUNT(DISTINCT eleve) as nb_eleves,
        COUNT(*) as total_absences_retards,
        SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
        SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
    FROM absence_retard 
    GROUP BY classe
");
$stmt->execute();
$result = $stmt->get_result();

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire des Absences - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="gestionnaire_absence.css">
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
                            <i class="fas fa-user-clock"></i>
                            Gestionnaire d'Absences
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-user-tie"></i>
                            Administrateur
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-gradient">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>

        <!-- Vue principale: liste des classes -->
        <div id="classes-view">
            <div class="page-title">
                <h2>
                    <i class="fas fa-user-clock"></i>
                    Gestionnaire des Absences et Retards
                </h2>
                <p>Sélectionnez une classe pour gérer les absences et retards</p>
            </div>

            <?php if (empty($classes)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                            <p>Aucune absence ou retard enregistré.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($classes as $classe): ?>
                        <div class="class-card" onclick="loadClassDetails('<?php echo htmlspecialchars($classe['classe']); ?>')">
                            <div class="class-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="class-info">
                                <h3><?php echo htmlspecialchars($classe['classe']); ?></h3>
                                <p>Gestion des absences</p>
                            </div>
                            <div class="class-stats">
                                <div class="stat-item">
                                    <i class="fas fa-user-graduate"></i>
                                    <span><?php echo $classe['nb_eleves']; ?> élèves</span>
                                </div>
                                <div class="stat-item absence-stat">
                                    <i class="fas fa-user-times"></i>
                                    <span><?php echo $classe['nb_absences']; ?> absences</span>
                                </div>
                                <div class="stat-item retard-stat">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $classe['nb_retards']; ?> retards</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Vue détaillée d'une classe (cachée par défaut) -->
        <div id="class-detail-view" class="class-detail-view" style="display: none;">
            <div class="class-header">
                <div class="class-title">
                    <h2 id="class-name">Classe</h2>
                    <div class="class-summary">
                        <div class="summary-item">
                            <i class="fas fa-user-graduate"></i>
                            <span id="class-student-count">0 élèves</span>
                        </div>
                        <div class="summary-item absence-summary">
                            <i class="fas fa-user-times"></i>
                            <span id="class-absence-count">0 absences</span>
                        </div>
                        <div class="summary-item retard-summary">
                            <i class="fas fa-clock"></i>
                            <span id="class-retard-count">0 retards</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="showClassesList()">
                    <i class="fas fa-arrow-left"></i>
                    Retour aux classes
                </button>
            </div>

            <div class="students-container" id="students-container">
                <!-- Les absences des élèves seront chargées ici par JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmer la suppression
                </h3>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet enregistrement ?</p>
                <div class="absence-info" id="absence-details">
                    <!-- Détails de l'absence/retard à supprimer -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Non
                </button>
                <button class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i>
                    Oui, supprimer
                </button>
            </div>
        </div>
    </div>

    <script src="scripteleve.js"></script>
    <script src="gestionnaire_abscence.js"></script>
</body>
</html>