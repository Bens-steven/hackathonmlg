<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;

// Extraction prénom et nom
$parts = explode(" ", $nom_complet);
$prenom = isset($parts[0]) ? $parts[0] : '';
$nom = isset($parts[1]) ? $parts[1] : '';

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

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur connexion MySQL: " . $mysqli->connect_error);
}

// ✅ GESTION DE LA SUPPRESSION D'ANNONCE (INTÉGRÉE)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['annonce_id'])) {
    $annonce_id = intval($_POST['annonce_id']);
    
    // Récupérer le fichier associé pour le supprimer
    $stmt = $mysqli->prepare("SELECT fichier FROM annonces WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $annonce_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $annonce = $result->fetch_assoc();
        $stmt->close();
        
        if ($annonce && $annonce['fichier']) {
            $file_path = "uploads/" . $annonce['fichier'];
            if (file_exists($file_path)) {
                unlink($file_path); // Supprimer le fichier du serveur
            }
        }
        
        // Supprimer l'annonce de la base de données
        $stmt = $mysqli->prepare("DELETE FROM annonces WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $annonce_id);
            
            if ($stmt->execute()) {
                $message = "✅ Annonce supprimée avec succès.";
            } else {
                $message = "❌ Erreur lors de la suppression de l'annonce.";
            }
            $stmt->close();
        } else {
            $message = "❌ Erreur de préparation de la requête de suppression.";
        }
    } else {
        $message = "❌ Erreur de préparation de la requête de récupération.";
    }
}

// Récupération de toutes les annonces avec pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// ✅ CORRECTION : Construction de la requête avec la bonne colonne 'date_envoi'
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(titre LIKE ? OR contenu LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(date_envoi) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "date_envoi >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "date_envoi >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Compter le total d'annonces
$count_query = "SELECT COUNT(*) as total FROM annonces $where_clause";
if (!empty($params)) {
    $count_stmt = $mysqli->prepare($count_query);
    if ($count_stmt && !empty($types)) {
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_annonces = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        // Fallback si la préparation échoue
        $total_annonces = $mysqli->query("SELECT COUNT(*) as total FROM annonces")->fetch_assoc()['total'];
    }
} else {
    $total_annonces = $mysqli->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_annonces / $limit);

// ✅ CORRECTION : Récupérer les annonces avec la bonne colonne 'date_envoi'
$query = "SELECT * FROM annonces $where_clause ORDER BY date_envoi DESC LIMIT ? OFFSET ?";
$final_params = $params;
$final_params[] = $limit;
$final_params[] = $offset;
$final_types = $types . 'ii';

$stmt = $mysqli->prepare($query);
$annonces = [];

if ($stmt) {
    if (!empty($final_types)) {
        $stmt->bind_param($final_types, ...$final_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $annonces[] = $row;
    }
    $stmt->close();
} else {
    // Fallback simple si la préparation échoue
    $simple_query = "SELECT * FROM annonces ORDER BY date_envoi DESC LIMIT $limit OFFSET $offset";
    $result = $mysqli->query($simple_query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $annonces[] = $row;
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire des Annonces - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="gestionnaire_annonce.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header avec profil -->
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
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>

        <?php if (isset($message)): ?>
            <div class="notification <?php echo strpos($message, '✅') !== false ? 'notification-success' : 'notification-error'; ?>">
                <i class="fas fa-<?php echo strpos($message, '✅') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Titre de la page -->
        <div class="page-title">
            <h2>
                <i class="fas fa-bullhorn"></i>
                Gestionnaire des Annonces
            </h2>
            <p>Gérez toutes les annonces envoyées aux élèves</p>
        </div>

        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Annonces</h3>
                    <div class="stat-value"><?php echo $total_annonces; ?></div>
                    <p>Annonces publiées</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-info">
                    <h3>Aujourd'hui</h3>
                    <div class="stat-value">
                        <?php
                        $today_count = 0;
                        foreach ($annonces as $annonce) {
                            if (date('Y-m-d', strtotime($annonce['date_envoi'])) === date('Y-m-d')) {
                                $today_count++;
                            }
                        }
                        echo $today_count;
                        ?>
                    </div>
                    <p>Annonces du jour</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paperclip"></i>
                </div>
                <div class="stat-info">
                    <h3>Avec Fichiers</h3>
                    <div class="stat-value">
                        <?php
                        $with_files = 0;
                        foreach ($annonces as $annonce) {
                            if (!empty($annonce['fichier'])) {
                                $with_files++;
                            }
                        }
                        echo $with_files;
                        ?>
                    </div>
                    <p>Pièces jointes</p>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="search-filters-container">
            <form method="GET" class="search-form">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="searchInput" placeholder="Rechercher dans les annonces..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="date_filter">Période</label>
                        <select name="date_filter" id="date_filter">
                            <option value="">Toutes les périodes</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Rechercher
                    </button>
                    
                    <a href="gestionnaire_annonce.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Effacer
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des annonces -->
        <div class="annonces-container">
            <?php if (empty($annonces)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>Aucune annonce trouvée</h4>
                            <p>
                                <?php if (!empty($search) || !empty($date_filter)): ?>
                                    Aucune annonce ne correspond à vos critères de recherche.
                                <?php else: ?>
                                    Aucune annonce n'a encore été publiée.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($annonces as $annonce): ?>
                    <div class="annonce-card">
                        <div class="annonce-header">
                            <div class="annonce-title">
                                <h3><?php echo htmlspecialchars($annonce['titre']); ?></h3>
                                <div class="annonce-meta">
                                    <span class="annonce-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime($annonce['date_envoi'])); ?>
                                    </span>
                                    <?php if (!empty($annonce['fichier'])): ?>
                                        <span class="annonce-file">
                                            <i class="fas fa-paperclip"></i>
                                            Pièce jointe
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="annonce-actions">
                                <?php if (!empty($annonce['fichier'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($annonce['fichier']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                        Télécharger
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $annonce['id']; ?>, '<?php echo htmlspecialchars($annonce['titre']); ?>')">
                                    <i class="fas fa-trash"></i>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        
                        <div class="annonce-content">
                            <?php echo nl2br(htmlspecialchars($annonce['contenu'])); ?>
                        </div>
                        
                        <div class="annonce-footer">
                            <div class="annonce-stats">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    Visible par tous les élèves
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-clock"></i>
                                    Publié il y a <?php echo timeAgo($annonce['date_envoi']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i>
                            Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" class="pagination-btn">
                            Suivant
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                    (<?php echo $total_annonces; ?> annonce<?php echo $total_annonces > 1 ? 's' : ''; ?> au total)
                </div>
            </div>
        <?php endif; ?>
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
                <p>Êtes-vous sûr de vouloir supprimer cette annonce ?</p>
                <div class="annonce-info" id="annonce-details">
                    <!-- Détails de l'annonce à supprimer -->
                </div>
                <p class="warning-text">
                    <i class="fas fa-exclamation-triangle"></i>
                    Cette action est irréversible. L'annonce et ses fichiers associés seront définitivement supprimés.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button class="btn btn-danger" onclick="deleteAnnonce()">
                    <i class="fas fa-trash"></i>
                    Supprimer définitivement
                </button>
            </div>
        </div>
    </div>

    <!-- ✅ FORMULAIRE INTÉGRÉ POUR LA SUPPRESSION -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="annonce_id" id="deleteAnnonceId">
    </form>

    <script src="scripteleve.js"></script>
    <script src="gestionnaire_annonce.js"></script>
</body>
</html>

<?php
// Fonction utilitaire pour calculer le temps écoulé
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'quelques secondes';
    if ($time < 3600) return floor($time/60) . ' minute' . (floor($time/60) > 1 ? 's' : '');
    if ($time < 86400) return floor($time/3600) . ' heure' . (floor($time/3600) > 1 ? 's' : '');
    if ($time < 2592000) return floor($time/86400) . ' jour' . (floor($time/86400) > 1 ? 's' : '');
    if ($time < 31536000) return floor($time/2592000) . ' mois';
    return floor($time/31536000) . ' an' . (floor($time/31536000) > 1 ? 's' : '');
}
?>