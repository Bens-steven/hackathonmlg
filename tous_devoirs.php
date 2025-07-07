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

$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur MySQL : " . $mysqli->connect_error);
}

$classe = '';
if (in_array('G_L1G1', $user_groups)) $classe = 'L1G1';
elseif (in_array('G_L1G2', $user_groups)) $classe = 'L1G2';
elseif (in_array('G_L2G1', $user_groups)) $classe = 'L2G1';
elseif (in_array('G_L2G2', $user_groups)) $classe = 'L2G2';

$stmt = $mysqli->prepare("SELECT d.id, d.titre, d.contenu, d.date_creation, d.date_limite, d.fichier, rd.fichier_rendu, rd.date_rendu, d.matiere, CASE WHEN rd.fichier_rendu IS NOT NULL THEN 1 ELSE 0 END as rendu FROM devoirs d LEFT JOIN rendus_devoirs rd ON d.id = rd.devoir_id AND rd.eleve_username = ? WHERE d.classe = ? ORDER BY d.date_creation DESC");
$stmt->bind_param("ss", $username, $classe);
$stmt->execute();
$result = $stmt->get_result();

$devoirs_par_matiere = [];
$total_devoirs = 0;
$devoirs_rendus = 0;
$devoirs_en_retard = 0;

while ($row = $result->fetch_assoc()) {
    $matiere = $row['matiere'];
    if (!isset($devoirs_par_matiere[$matiere])) {
        $devoirs_par_matiere[$matiere] = [];
    }
    $devoirs_par_matiere[$matiere][] = $row;
    $total_devoirs++;

    if ($row['rendu']) {
        $devoirs_rendus++;
    } elseif (new DateTime($row['date_limite']) < new DateTime()) {
        $devoirs_en_retard++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous mes devoirs - <?php echo htmlspecialchars($nom_complet); ?></title>
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
                        <span class="meta-item"><i class="fas fa-tasks"></i> Tous mes devoirs</span>
                        <?php if ($classe): ?>
                            <span class="meta-item"><i class="fas fa-users"></i> Classe <?php echo htmlspecialchars($classe); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="eleve.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour au profil</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </header>

        <div class="stats-container">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-info"><h3>Total des devoirs</h3><div class="stat-value"><?php echo $total_devoirs; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3>Devoirs rendus</h3><div class="stat-value excellent"><?php echo $devoirs_rendus; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-info"><h3>En retard</h3><div class="stat-value poor"><?php echo $devoirs_en_retard; ?></div></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-info"><h3>À rendre</h3><div class="stat-value"><?php echo $total_devoirs - $devoirs_rendus - $devoirs_en_retard; ?></div></div></div>
        </div>

        <div class="homework-list-container">
            <?php if (count($devoirs_par_matiere) > 0): ?>
                <?php foreach ($devoirs_par_matiere as $matiere => $devoirs): ?>
                    <section class="matiere-section">
                        <h2><?php echo htmlspecialchars($matiere); ?></h2>
                        <?php foreach ($devoirs as $devoir): ?>
                            <?php
                            $isOverdue = new DateTime($devoir['date_limite']) < new DateTime() && !$devoir['rendu'];
                            $statusClass = $devoir['rendu'] ? 'rendered' : ($isOverdue ? 'overdue' : 'pending');
                            $deadlinePassed = new DateTime($devoir['date_limite']) < new DateTime();
                            ?>
                            <section class="card homework-detail-card <?php echo $statusClass; ?>">
                                <div class="card-header">
                                    <div class="homework-header-info">
                                        <h3><?php echo htmlspecialchars($devoir['titre']); ?></h3>
                                        <div class="homework-status">
                                            <?php if ($devoir['rendu']): ?>
                                                <span class="status-badge rendered"><i class="fas fa-check-circle"></i> Rendu le <?php echo date('d/m/Y à H:i', strtotime($devoir['date_rendu'])); ?></span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="status-badge overdue"><i class="fas fa-times-circle"></i> En retard</span>
                                            <?php else: ?>
                                                <span class="status-badge pending"><i class="fas fa-clock"></i> À rendre</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="homework-content">
                                        <p><?php echo nl2br(htmlspecialchars($devoir['contenu'])); ?></p>
                                    </div>
                                    <div class="homework-dates">
                                        <div class="homework-date"><i class="fas fa-calendar"></i> Créé le <?php echo date('d/m/Y à H:i', strtotime($devoir['date_creation'])); ?></div>
                                        <div class="homework-deadline <?php echo $isOverdue ? 'overdue' : ''; ?>">
                                            <i class="fas fa-hourglass-end"></i> À rendre avant le <?php echo date('d/m/Y à H:i', strtotime($devoir['date_limite'])); ?>
                                        </div>
                                    </div>
                                    <div class="homework-actions">
                                        <?php if ($devoir['fichier']): ?>
                                            <a href="pieces_jointes/<?php echo htmlspecialchars($devoir['fichier']); ?>" target="_blank" class="btn btn-secondary">
                                                <i class="fas fa-paperclip"></i> Télécharger la pièce jointe
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$devoir['rendu']): ?>
                                            <?php if ($deadlinePassed): ?>
                                                <button class="btn btn-disabled" disabled style="background-color: #6b7280; color: #9ca3af; cursor: not-allowed; opacity: 0.6;" onclick="alert('La date limite pour rendre ce devoir est dépassée.')">
                                                    <i class="fas fa-clock"></i> Date limite dépassée
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary" onclick="showSubmitModal(<?php echo $devoir['id']; ?>, '<?php echo htmlspecialchars($devoir['titre'], ENT_QUOTES); ?>', '<?php echo $devoir['date_limite']; ?>')" data-deadline="<?php echo $devoir['date_limite']; ?>">
                                                    <i class="fas fa-upload"></i> Rendre le devoir
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <section class="card"><div class="card-body"><p class="empty-state">Aucun devoir n'a encore été assigné à votre classe.</p></div></section>
            <?php endif; ?>
        </div>
    </div>
    <script src="gestionnaire_devoirs.js?v=<?php echo time(); ?>"></script>
</body>
</html>