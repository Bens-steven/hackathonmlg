<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
}

// Récupérer toutes les annonces
$result = $mysqli->query("SELECT * FROM annonces ORDER BY date_envoi DESC");
$annonces = $result->fetch_all(MYSQLI_ASSOC);

// Récupérer les annonces vues par l'utilisateur (simulé avec localStorage côté client)
$mysqli->close();

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📢 Annonces</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="annonces.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header avec profil utilisateur -->
        <header class="profile-header">
            <div class="profile-info">
                <div class="avatar-container">
                    <img src="<?php echo $photo_path . (str_starts_with($photo_path, 'data:image') ? '' : '?v=' . time()); ?>" alt="Photo de profil" class="avatar">
                    <div class="status-indicator"></div>
                </div>
                <div class="user-details">
                    <h1 class="user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                    <p class="username">@<?php echo htmlspecialchars($username); ?></p>
                    <div class="user-meta">
                        <span class="meta-item"><i class="fas fa-bullhorn"></i> Annonces</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="eleve.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
                <button class="btn btn-primary" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
            </div>
        </header>

        <!-- Statistiques des annonces -->
        <div class="announcements-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-info">
                    <h3>Total des annonces</h3>
                    <div class="stat-value" id="totalAnnouncements"><?php echo count($annonces); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon unread">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3>Non lues</h3>
                    <div class="stat-value" id="unreadCount">0</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon read">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="stat-info">
                    <h3>Lues</h3>
                    <div class="stat-value" id="readCount">0</div>
                </div>
            </div>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="search-filters-container">
            <div class="search-input-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Rechercher dans les annonces..." autocomplete="off">
            </div>
            
            <div class="filters-row">
                <div class="filter-group">
                    <label for="statusFilter">Statut</label>
                    <select id="statusFilter">
                        <option value="all">Toutes les annonces</option>
                        <option value="unread">Non lues</option>
                        <option value="read">Lues</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="dateFilter">Période</label>
                    <select id="dateFilter">
                        <option value="all">Toutes les périodes</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sortFilter">Trier par</label>
                    <select id="sortFilter">
                        <option value="newest">Plus récentes</option>
                        <option value="oldest">Plus anciennes</option>
                        <option value="title">Titre A-Z</option>
                    </select>
                </div>
                
                <button class="clear-filters-btn" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> Effacer les filtres
                </button>
            </div>
        </div>

        <!-- Résultats de recherche -->
        <div id="searchStats" class="search-stats" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="searchStatsText"></span>
        </div>

        <!-- Liste des annonces -->
        <div class="announcements-container" id="announcementsContainer">
            <?php if (empty($annonces)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <p>Aucune annonce disponible pour le moment.</p>
                    <p>Les nouvelles annonces apparaîtront ici dès qu'elles seront publiées.</p>
                </div>
            <?php else: ?>
                <?php foreach ($annonces as $index => $annonce): ?>
                    <div class="announcement-card" 
                         data-id="<?php echo $annonce['id']; ?>"
                         data-title="<?php echo htmlspecialchars(strtolower($annonce['titre'])); ?>"
                         data-content="<?php echo htmlspecialchars(strtolower($annonce['contenu'])); ?>"
                         data-date="<?php echo $annonce['date_envoi']; ?>"
                         data-timestamp="<?php echo strtotime($annonce['date_envoi']); ?>">
                        
                        <div class="announcement-header">
                            <div class="announcement-title-section">
                                <h2 class="announcement-title">
                                    <span class="unread-indicator" id="indicator-<?php echo $annonce['id']; ?>">
                                        <i class="fas fa-circle"></i>
                                    </span>
                                    <?php echo htmlspecialchars($annonce['titre']); ?>
                                </h2>
                                <div class="announcement-meta">
                                    <span class="announcement-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date("d/m/Y à H:i", strtotime($annonce['date_envoi'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="announcement-actions">
                                <button class="btn btn-sm btn-primary expand-btn" onclick="toggleAnnouncement(<?php echo $annonce['id']; ?>)">
                                    <i class="fas fa-chevron-down"></i>
                                    <span>Voir plus</span>
                                </button>
                                <button class="btn btn-sm btn-success mark-read-btn" onclick="markAsRead(<?php echo $annonce['id']; ?>)" style="display: none;">
                                    <i class="fas fa-check"></i>
                                    <span>Marquer comme lu</span>
                                </button>
                            </div>
                        </div>

                        <div class="announcement-content" id="content-<?php echo $annonce['id']; ?>" style="display: none;">
                            <div class="announcement-text">
                                <?php echo nl2br(htmlspecialchars($annonce['contenu'])); ?>
                            </div>
                            
                            <?php if (!empty($annonce['fichier'])): ?>
                                <div class="announcement-attachment">
                                    <div class="attachment-card">
                                        <div class="attachment-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </div>
                                        <div class="attachment-info">
                                            <span class="attachment-name"><?php echo htmlspecialchars($annonce['fichier']); ?></span>
                                            <span class="attachment-type">Pièce jointe</span>
                                        </div>
                                        <a href="uploads/<?php echo urlencode($annonce['fichier']); ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i>
                                            Télécharger
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="announcement-footer">
                                <div class="announcement-info">
                                    <span class="info-item">
                                        <i class="fas fa-clock"></i>
                                        Publié le <?php echo date("d/m/Y à H:i", strtotime($annonce['date_envoi'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Message d'état vide pour les recherches -->
        <div id="noResultsMessage" class="empty-state" style="display: none;">
            <i class="fas fa-search"></i>
            <p>Aucune annonce ne correspond à votre recherche.</p>
            <p>Essayez avec d'autres mots-clés ou modifiez vos filtres.</p>
        </div>
    </div>

    <script src="scripteleve.js"></script>
    <script src="annonces.js"></script>
</body>
</html>