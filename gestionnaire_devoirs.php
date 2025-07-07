<<?php
session_start();

// Vérification que la matière est définie
if (!isset($_SESSION['matiere'])) {
    echo "Matière non définie dans la session.";
    exit;
}

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$matiere = $_SESSION['matiere'];
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

// 🔴 Filtrer uniquement les devoirs de la matière du professeur
$stmt = $mysqli->prepare("
    SELECT classe, COUNT(*) as nb_devoirs
    FROM devoirs 
    WHERE matiere = ?
    GROUP BY classe
");
$stmt->bind_param("s", $matiere);
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
    <title>Gestionnaire des Devoirs - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="gestionnaire_notes.css">
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
                <a href="professeur.php" class="btn btn-secondary">
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
                    <i class="fas fa-tasks"></i>
                    Gestionnaire des Devoirs
                </h2>
                <p>Sélectionnez une classe pour gérer les devoirs</p>
            </div>

            <?php if (empty($classes)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                            <p>Aucun devoir trouvé.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($classes as $classe): ?>
                        <div class="class-card" onclick="loadClassHomework('<?php echo htmlspecialchars($classe['classe']); ?>')">
                            <div class="class-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="class-info">
                                <h3><?php echo htmlspecialchars($classe['classe']); ?></h3>
                                <p>Classe</p>
                            </div>
                            <div class="class-stats">
                                <div class="stat-item">
                                    <i class="fas fa-tasks"></i>
                                    <span><?php echo $classe['nb_devoirs']; ?> devoirs</span>
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
                            <i class="fas fa-tasks"></i>
                            <span id="class-homework-count">0 devoirs</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="showClassesList()">
                    <i class="fas fa-arrow-left"></i>
                    Retour aux classes
                </button>
            </div>

            <div class="homework-container" id="homework-container">
                <!-- Les devoirs seront chargés ici par JavaScript -->
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
                <p>Êtes-vous sûr de vouloir supprimer ce devoir ?</p>
                <div class="homework-info" id="homework-details">
                    <!-- Détails du devoir à supprimer -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Non
                </button>
                <button class="btn btn-danger" onclick="confirmDeleteHomework()">
                    <i class="fas fa-trash"></i>
                    Oui, supprimer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher le fichier rendu -->
    <div id="fileModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-file"></i>
                    Fichier rendu
                </h3>
            </div>
            <div class="modal-body" id="file-content">
                <!-- Contenu du fichier -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeFileModal()">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <script src="scripteleve.js"></script>
    <script src="gestionnaire_devoirsprof.js"></script>
</body>
</html>