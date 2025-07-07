<?php
session_start();

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Classe passée en GET
$classe = $_GET['classe'] ?? '';
if (empty($classe)) {
    echo "Classe non spécifiée.";
    exit;
}

// Récupère le nom complet de l'utilisateur connecté (si défini dans la session)
$fullname = $_SESSION['fullname'] ?? 'Nom non défini';
$username = $_SESSION['username'] ?? 'admin';

// Extraction prénom et nom
$parts = explode(" ", $fullname);
$prenom = $parts[0] ?? '';
$nom = $parts[1] ?? '';

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

// ================= Connexion LDAP avec ElimProf ================= //
$ldapconn = ldap_connect("ldap://192.168.20.132");
$professeur = 'Professeur inconnu';
$matieres_disponibles = [];

if ($ldapconn) {
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    $ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");

    if ($ldapbind) {
        // Récupérer tous les groupes G_*
        $search = ldap_search($ldapconn, "dc=educonnect,dc=mg", "(cn=G_*)", ["cn"]);
        $entries = ldap_get_entries($ldapconn, $search);

        for ($i = 0; $i < $entries["count"]; $i++) {
            $cn = $entries[$i]["cn"][0];
            // On garde seulement les vrais groupes matières (exclut G_Tous_Eleves, etc.)
            if (preg_match('/^G_([A-Za-z]+)$/', $cn, $matches)) {
                $matiere = ucfirst(strtolower($matches[1]));
                $matieres_disponibles[] = $matiere;
            }
        }

        // Récupérer professeur associé si matière choisie
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $matiere_post = $_POST['matiere'] ?? '';
            if ($matiere_post) {
                $searchProf = ldap_search($ldapconn, "dc=educonnect,dc=mg", "(CN=G_" . ucfirst($matiere_post) . ")", ["member"]);
                $resProf = ldap_get_entries($ldapconn, $searchProf);
                if ($resProf["count"] > 0 && isset($resProf[0]["member"][0])) {
                    $dn = $resProf[0]["member"][0];
                    if (preg_match('/CN=([^,]+)/', $dn, $match)) {
                        $professeur = $match[1];
                    }
                }
            }
        }
    }
    ldap_unbind($ldapconn);
} else {
    die("❌ Connexion LDAP échouée.");
}

$notification_message = '';
$notification_type = '';

// Enregistrement d'un cours
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['jour'], $_POST['heure_debut'], $_POST['heure_fin'], $_POST['matiere'], $_POST['salle'])) {
    
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $matiere = $_POST['matiere'];
    $salle = $_POST['salle'];

    // Vérifier si le créneau horaire est déjà occupé
    $checkQuery = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE classe = ? AND jour_semaine = ? AND ((heure_debut BETWEEN ? AND ?) OR (heure_fin BETWEEN ? AND ?) OR (? BETWEEN heure_debut AND heure_fin) OR (? BETWEEN heure_debut AND heure_fin))");
    $checkQuery->bind_param("ssssssss", $classe, $jour, $heure_debut, $heure_fin, $heure_debut, $heure_fin, $heure_debut, $heure_fin);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        $notification_message = 'Ce créneau horaire est déjà occupé !';
        $notification_type = 'error';
    } else {
        // Aucun cours dans le créneau, on peut ajouter le cours
        if ($heure_debut >= $heure_fin) {
            $notification_message = 'L\'heure de début doit être inférieure à l\'heure de fin.';
            $notification_type = 'error';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO emplois_du_temps (classe, jour_semaine, heure_debut, heure_fin, matiere, salle, professeur) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $classe, $jour, $heure_debut, $heure_fin, $matiere, $salle, $professeur);
            $stmt->execute();
            $stmt->close();
            $notification_message = 'Cours ajouté avec succès !';
            $notification_type = 'success';
        }
    }
}

// Récupération des cours existants
$result = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE classe = ? ORDER BY FIELD(jour_semaine, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'), heure_debut");
$result->bind_param("s", $classe);
$result->execute();
$cours = $result->get_result();

// Déterminer le jour actuel en français
$jours_fr = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi', 
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];
$jour_actuel = $jours_fr[date('l')] ?? 'Lundi';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du temps - <?php echo htmlspecialchars($classe); ?></title>
    <link rel="stylesheet" href="styleeleve.css">
    <link rel="stylesheet" href="gestion_edt.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <header class="profile-header">
        <div class="profile-info">
            <div class="avatar-container">
                <img src="<?php echo $photo_path; ?>" alt="Photo de profil" class="avatar" id="profileImage">
                <div class="status-indicator"></div>
            </div>
            <div class="user-details">
                <h1 class="user-name">Emploi du temps</h1>
                <p class="username classe-clickable" onclick="toggleScheduleTable()" style="cursor: pointer; text-decoration: underline;">
                    Classe <?php echo htmlspecialchars($classe); ?>
                </p>
                <div class="user-meta">
                    <span class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        Gestion des créneaux
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-users"></i>
                        <?php echo htmlspecialchars($classe); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Retour à l'admin
            </a>
        </div>
    </header>

    <?php if ($notification_message): ?>
        <div class="notification notification-<?php echo $notification_type; ?>">
            <div class="notification-content">
                <i class="fas fa-<?php echo $notification_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($notification_message); ?></span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="content-left">
            <section class="card schedule-form-card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Ajouter un nouveau cours</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="schedule-form" id="scheduleForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="jour"><i class="fas fa-calendar-day"></i> Jour de la semaine</label>
                                <select name="jour" id="jour" required class="form-select">
                                    <option value="">-- Sélectionner un jour --</option>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="matiere"><i class="fas fa-book"></i> Matière</label>
                                <select name="matiere" id="matiere" required class="form-select">
                                    <option value="">-- Sélectionner une matière --</option>
                                    <?php foreach ($matieres_disponibles as $m): ?>
                                        <option value="<?php echo htmlspecialchars($m); ?>">
                                            <?php echo htmlspecialchars($m); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="heure_debut"><i class="fas fa-clock"></i> Heure de début</label>
                                <input type="time" name="heure_debut" id="heure_debut" required class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="heure_fin"><i class="fas fa-clock"></i> Heure de fin</label>
                                <input type="time" name="heure_fin" id="heure_fin" required class="form-input">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="salle"><i class="fas fa-door-open"></i> Salle</label>
                                <input type="text" name="salle" id="salle" required class="form-input" placeholder="Ex: A101, B205...">
                            </div>
                            <div class="form-group">
                                <label for="professeur_display"><i class="fas fa-user-tie"></i> Professeur</label>
                                <input type="text" id="professeur_display" class="form-input" value="<?php echo htmlspecialchars($professeur); ?>" readonly>
                                <small class="form-text">Automatiquement assigné selon la matière</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-add-course">
                                <i class="fas fa-plus"></i>
                                Ajouter le cours
                            </button>
                            <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i>
                                Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="content-right">
            <section class="card schedule-preview-card">
                <div class="card-header">
                    <h3><i class="fas fa-eye"></i> Aperçu du jour - <?php echo $jour_actuel; ?></h3>
                </div>
                <div class="card-body">
                    <div class="day-preview-today">
                        <?php
                        // Organiser les cours par jour
                        $cours_par_jour = [];
                        $cours->data_seek(0);
                        while ($row = $cours->fetch_assoc()) {
                            $cours_par_jour[$row['jour_semaine']][] = $row;
                        }
                        ?>
                        <div class="today-preview">
                            <div class="today-header">
                                <i class="fas fa-calendar-day"></i>
                                Aujourd'hui - <?php echo $jour_actuel; ?>
                                <span class="today-date"><?php echo date('d/m/Y'); ?></span>
                            </div>
                            <div class="today-courses">
                                <?php if (isset($cours_par_jour[$jour_actuel])): ?>
                                    <?php foreach ($cours_par_jour[$jour_actuel] as $course): ?>
                                        <div class="course-preview-today">
                                            <div class="course-time-today">
                                                <i class="fas fa-clock"></i>
                                                <?php echo substr($course['heure_debut'], 0, 5) . ' - ' . substr($course['heure_fin'], 0, 5); ?>
                                            </div>
                                            <div class="course-details-today">
                                                <div class="course-subject-today"><?php echo htmlspecialchars($course['matiere']); ?></div>
                                                <div class="course-room-today">
                                                    <i class="fas fa-door-open"></i>
                                                    <?php echo htmlspecialchars($course['salle']); ?>
                                                </div>
                                                <div class="course-teacher-today">
                                                    <i class="fas fa-user-tie"></i>
                                                    <?php echo htmlspecialchars($course['professeur']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-courses-today">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>Aucun cours prévu aujourd'hui</p>
                                        <p>Profitez de cette journée libre !</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="card schedule-table-card" id="scheduleTableCard" style="display: none;">
        <div class="card-header">
            <h3><i class="fas fa-table"></i> Cours enregistrés pour la classe <?php echo htmlspecialchars($classe); ?></h3>
            <button class="btn btn-secondary btn-sm" onclick="toggleScheduleTable()">
                <i class="fas fa-times"></i>
                Masquer
            </button>
        </div>
        <div class="card-body">
            <?php if ($cours->num_rows > 0): ?>
                <div class="schedule-table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-day"></i> Jour</th>
                                <th><i class="fas fa-clock"></i> Horaires</th>
                                <th><i class="fas fa-book"></i> Matière</th>
                                <th><i class="fas fa-door-open"></i> Salle</th>
                                <th><i class="fas fa-user-tie"></i> Professeur</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $cours->data_seek(0);
                            while ($row = $cours->fetch_assoc()): 
                            ?>
                                <tr class="schedule-row">
                                    <td class="day-cell">
                                        <span class="day-badge"><?php echo $row['jour_semaine']; ?></span>
                                    </td>
                                    <td class="time-cell">
                                        <div class="time-range">
                                            <span class="start-time"><?php echo substr($row['heure_debut'], 0, 5); ?></span>
                                            <i class="fas fa-arrow-right"></i>
                                            <span class="end-time"><?php echo substr($row['heure_fin'], 0, 5); ?></span>
                                        </div>
                                    </td>
                                    <td class="subject-cell">
                                        <span class="subject-name"><?php echo htmlspecialchars($row['matiere']); ?></span>
                                    </td>
                                    <td class="room-cell">
                                        <span class="room-number"><?php echo htmlspecialchars($row['salle']); ?></span>
                                    </td>
                                    <td class="teacher-cell">
                                        <span class="teacher-name"><?php echo htmlspecialchars($row['professeur']); ?></span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="supprimer_edt.php?id=<?php echo $row['id']; ?>&classe=<?php echo urlencode($classe); ?>" 
                                           class="btn btn-danger btn-sm delete-btn" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')">
                                            <i class="fas fa-trash"></i>
                                            Supprimer
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Aucun cours enregistré pour cette classe</p>
                    <p>Commencez par ajouter votre premier cours ci-dessus</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script src="scripteleve.js"></script>
<script src="gestion_edt.js"></script>
</body>
</html>