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

// Récupérer les statistiques des absences et retards de l'élève
$stmt_stats = $mysqli->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
        SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
    FROM absence_retard 
    WHERE eleve = ?
");
$stmt_stats->bind_param("s", $username);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_assoc();

// Récupérer l'historique complet des absences et retards
$stmt_historique = $mysqli->prepare("
    SELECT 
        id,
        date,
        heure,
        type,
        motif,
        classe
    FROM absence_retard 
    WHERE eleve = ? 
    ORDER BY date DESC, heure DESC
");
$stmt_historique->bind_param("s", $username);
$stmt_historique->execute();
$result_historique = $stmt_historique->get_result();

$historique = [];
while ($row = $result_historique->fetch_assoc()) {
    $historique[] = $row;
}

$stmt_stats->close();
$stmt_historique->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Absences et Retards - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="absence_retard.css">
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
                            <i class="fas fa-user-graduate"></i>
                            Élève
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-user-clock"></i>
                            Suivi des absences
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="eleve.php" class="btn btn-gradient">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>

        <!-- Titre de la page -->
        <div class="page-title">
            <h2>
                <i class="fas fa-user-clock"></i>
                Mes Absences et Retards
            </h2>
            <p>Consultez votre historique de présence</p>
        </div>

        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card total-card">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3>Total</h3>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <p>Enregistrements</p>
                </div>
            </div>
            
            <div class="stat-card absence-card">
                <div class="stat-icon absence-icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-info">
                    <h3>Absences</h3>
                    <div class="stat-value absence-value"><?php echo $stats['nb_absences']; ?></div>
                    <p>Jours d'absence</p>
                </div>
            </div>
            
            <div class="stat-card retard-card">
                <div class="stat-icon retard-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Retards</h3>
                    <div class="stat-value retard-value"><?php echo $stats['nb_retards']; ?></div>
                    <p>Retards enregistrés</p>
                </div>
            </div>
        </div>

        <!-- Historique -->
        <div class="card historique-card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-history"></i>
                    Historique complet
                </h3>
                <div class="filter-controls">
                    <select id="typeFilter" class="filter-select">
                        <option value="">Tous les types</option>
                        <option value="absence">Absences</option>
                        <option value="retard">Retards</option>
                    </select>
                    <button class="btn btn-sm btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i>
                        Effacer
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($historique)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-check" style="font-size: 3rem; margin-bottom: 1rem; color: #22c55e;"></i>
                        <h4>Aucune absence ou retard</h4>
                        <p>Félicitations ! Vous êtes toujours présent et ponctuel.</p>
                    </div>
                <?php else: ?>
                    <div class="historique-container" id="historiqueContainer">
                        <?php foreach ($historique as $item): ?>
                            <div class="historique-item <?php echo $item['type']; ?>" data-type="<?php echo $item['type']; ?>">
                                <div class="historique-date">
                                    <div class="date-circle <?php echo $item['type']; ?>">
                                        <i class="fas fa-<?php echo $item['type'] === 'absence' ? 'user-times' : 'clock'; ?>"></i>
                                    </div>
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date('d/m/Y', strtotime($item['date'])); ?></div>
                                        <div class="date-day"><?php echo strftime('%A', strtotime($item['date'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="historique-details">
                                    <div class="historique-header">
                                        <span class="type-badge <?php echo $item['type']; ?>">
                                            <i class="fas fa-<?php echo $item['type'] === 'absence' ? 'user-times' : 'clock'; ?>"></i>
                                            <?php echo ucfirst($item['type']); ?>
                                        </span>
                                        <?php if ($item['heure']): ?>
                                            <span class="time-badge">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $item['heure']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="historique-info">
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span>Classe: <?php echo htmlspecialchars($item['classe']); ?></span>
                                        </div>
                                        <?php if ($item['motif']): ?>
                                            <div class="info-item motif">
                                                <i class="fas fa-comment"></i>
                                                <span>"<?php echo htmlspecialchars($item['motif']); ?>"</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="scripteleve.js"></script>
    <script src="absence_retard.js"></script>
</body>
</html>