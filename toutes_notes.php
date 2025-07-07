<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
$parts = explode(" ", $nom_complet);
$prenom = isset($parts[0]) ? $parts[0] : '';
$nom = isset($parts[1]) ? $parts[1] : '';

// Photo avec avatar par défaut
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
if ($mysqli->connect_error) {
    die("Erreur MySQL : " . $mysqli->connect_error);
}

// Récupérer toutes les notes de l'élève groupées par matière
$stmt_notes = $mysqli->prepare("SELECT note, matiere, date FROM notes WHERE eleve_username = ? ORDER BY matiere, date DESC");
$stmt_notes->bind_param("s", $username);
$stmt_notes->execute();
$result_notes = $stmt_notes->get_result();

$notes_par_matiere = [];
$total_notes = 0;
$somme_notes = 0;

if ($result_notes && $result_notes->num_rows > 0) {
    while ($row = $result_notes->fetch_assoc()) {
        $matiere = $row['matiere'];
        if (!isset($notes_par_matiere[$matiere])) {
            $notes_par_matiere[$matiere] = [];
        }
        $notes_par_matiere[$matiere][] = $row;
        $total_notes++;
        $somme_notes += floatval($row['note']);
    }
}

// Calculer la moyenne générale
$moyenne_generale = $total_notes > 0 ? round($somme_notes / $total_notes, 2) : 0;

// Calculer les moyennes par matière
$moyennes_par_matiere = [];
foreach ($notes_par_matiere as $matiere => $notes) {
    $somme_matiere = 0;
    foreach ($notes as $note) {
        $somme_matiere += floatval($note['note']);
    }
    $moyennes_par_matiere[$matiere] = round($somme_matiere / count($notes), 2);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes mes notes - <?php echo htmlspecialchars($nom_complet); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <header class="profile-header">
        <div class="profile-info">
            <div class="avatar-container">
                <img src="<?php echo $photo_path; ?>" alt="Photo de profil" class="avatar">
                <div class="status-indicator"></div>
            </div>
            <div class="user-details">
                <h1 class="user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                <p class="username">@<?php echo htmlspecialchars($username); ?></p>
                <div class="user-meta">
                    <span class="meta-item">
                        <i class="fas fa-chart-line"></i>
                        Toutes mes notes
                    </span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="eleve.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Retour au profil
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </div>
    </header>

    <!-- Statistiques générales -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-info">
                <h3>Moyenne générale</h3>
                <div class="stat-value <?php 
                    if ($moyenne_generale >= 16) echo 'excellent';
                    elseif ($moyenne_generale >= 12) echo 'good';
                    elseif ($moyenne_generale >= 10) echo 'average';
                    else echo 'poor';
                ?>">
                    <?php echo $moyenne_generale; ?>/20
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-list-ol"></i>
            </div>
            <div class="stat-info">
                <h3>Total des notes</h3>
                <div class="stat-value"><?php echo $total_notes; ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-info">
                <h3>Matières</h3>
                <div class="stat-value"><?php echo count($notes_par_matiere); ?></div>
            </div>
        </div>
    </div>

    <!-- Notes par matière -->
    <div class="subjects-container">
        <?php if (count($notes_par_matiere) > 0): ?>
            <?php foreach ($notes_par_matiere as $matiere => $notes): ?>
                <section class="card subject-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-book-open"></i>
                            <?php echo htmlspecialchars($matiere); ?>
                        </h3>
                        <div class="subject-average <?php 
                            $moy = $moyennes_par_matiere[$matiere];
                            if ($moy >= 16) echo 'excellent';
                            elseif ($moy >= 12) echo 'good';
                            elseif ($moy >= 10) echo 'average';
                            else echo 'poor';
                        ?>">
                            Moyenne: <?php echo $moyennes_par_matiere[$matiere]; ?>/20
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grades-container">
                            <?php foreach ($notes as $note): ?>
                                <?php
                                $note_value = floatval($note['note']);
                                $grade_class = '';
                                if ($note_value >= 16) {
                                    $grade_class = 'excellent';
                                } elseif ($note_value >= 12) {
                                    $grade_class = 'good';
                                } elseif ($note_value >= 10) {
                                    $grade_class = 'average';
                                } else {
                                    $grade_class = 'poor';
                                }
                                ?>
                                <div class="grade-item">
                                    <div class="subject-info">
                                        <span class="grade-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($note['date'])); ?>
                                        </span>
                                    </div>
                                    <div class="grade-value <?php echo $grade_class; ?>">
                                        <?php echo htmlspecialchars($note['note']); ?>/20
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <section class="card">
                <div class="card-body">
                    <p class="empty-state">Aucune note n'a encore été saisie.</p>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<script src="scripteleve.js"></script>
</body>
</html>