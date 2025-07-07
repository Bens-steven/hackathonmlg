<?php 
session_start();

// Vérification que la matière est bien définie dans la session
if (!isset($_SESSION['matiere'])) {
    echo "❌ Matière non définie. Veuillez vous reconnecter.";
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
    // Créer un avatar par défaut avec les initiales
    $initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    $photo_path = "data:image/svg+xml;base64," . base64_encode('
        <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
            <circle cx="75" cy="75" r="75" fill="#9CA3AF"/>
            <text x="75" y="85" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>
    ');
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) die("Erreur connexion MySQL: " . $mysqli->connect_error);

// Récupération de la classe depuis l'URL
$classe = isset($_GET['classe']) ? $_GET['classe'] : '';
if (!$classe) {
    echo "Classe non spécifiée.";
    exit;
}

// Récupérer les notes des élèves pour cette classe ET cette matière
$stmt = $mysqli->prepare("SELECT eleve_username, note, DATE_FORMAT(date, '%d/%m/%Y') as date_formatted FROM notes WHERE classe = ? AND matiere = ? ORDER BY eleve_username");
if (!$stmt) {
    echo "Erreur de préparation de la requête SQL: " . $mysqli->error;
    exit;
}

$stmt->bind_param("ss", $classe, $matiere);
$stmt->execute();
$result = $stmt->get_result();

$eleves = [];
$total_notes = 0;
$sum_notes = 0;
while ($row = $result->fetch_assoc()) {
    $eleves[] = $row;
    $total_notes++;
    $sum_notes += $row['note'];
}

$moyenne_classe = $total_notes > 0 ? round($sum_notes / $total_notes, 1) : 0;

$stmt->close();
$mysqli->close();

// Fonction pour déterminer la classe CSS selon la note
function getGradeClass($note) {
    if ($note >= 16) return 'excellent';
    if ($note >= 14) return 'good';
    if ($note >= 10) return 'average';
    return 'poor';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classe <?php echo htmlspecialchars($classe); ?> - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
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
                            <i class="fas fa-users"></i>
                            Classe <?php echo htmlspecialchars($classe); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="gestionnaire_notes.php" class="btn btn-gradient">
                    <i class="fas fa-arrow-left"></i>
                    Retour aux classes
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>

        <!-- Statistiques de la classe -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Nombre d'élèves</h3>
                    <div class="stat-value"><?php echo count(array_unique(array_column($eleves, 'eleve_username'))); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>Moyenne de classe</h3>
                    <div class="stat-value <?php echo getGradeClass($moyenne_classe); ?>"><?php echo $moyenne_classe; ?>/20</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3>Total des notes</h3>
                    <div class="stat-value"><?php echo $total_notes; ?></div>
                </div>
            </div>
        </div>

        <!-- Notes des élèves -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-star"></i>
                    Notes de la classe <?php echo htmlspecialchars($classe); ?> (<?php echo htmlspecialchars($matiere); ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($eleves)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                        <p>Aucune note trouvée pour cette classe et matière.</p>
                    </div>
                <?php else: ?>
                    <div class="grades-container">
                        <?php foreach ($eleves as $eleve): ?>
                            <div class="grade-item">
                                <div class="subject-info">
                                    <div class="subject-name">
                                        <i class="fas fa-user-graduate"></i>
                                        <?php echo htmlspecialchars($eleve['eleve_username']); ?>
                                    </div>
                                    <div class="grade-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo $eleve['date_formatted']; ?>
                                    </div>
                                </div>
                                <div class="grade-value <?php echo getGradeClass($eleve['note']); ?>">
                                    <?php echo htmlspecialchars($eleve['note']); ?>/20
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="scripteleve.js"></script>
</body>
</html>
