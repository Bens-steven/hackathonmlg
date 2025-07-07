<?php 
session_start();

// ✅ CONTRÔLE D'ACCÈS - Seuls les élèves et professeurs peuvent accéder
require_once 'check_access.php';
checkAccess(['G_Tous_Eleves', 'G_Tous_Professeurs']);

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$nom_complet = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $username;
$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
$mysqli->set_charset("utf8mb4");
if ($mysqli->connect_error) {
    die("Erreur MySQL : " . $mysqli->connect_error);
}

// Détection si c'est un professeur
$est_prof = false;
$groupes_matieres = ["G_Mathematique", "G_Francais", "G_Histoire", "G_Physique"];
foreach ($groupes_matieres as $groupe) {
    if (in_array($groupe, $user_groups)) {
        $est_prof = true;
        break;
    }
}

// Si professeur → on récupère tous ses cours dans toutes les classes
if ($est_prof) {
    $edt_query = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE professeur = ? ORDER BY FIELD(jour_semaine, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'), heure_debut");
    $edt_query->bind_param("s", $nom_complet);
    $classe = "Toutes classes";
} else {
    // Élève → déduction de sa classe via groupes
    $classe = '';
    if (in_array('G_L1G1', $user_groups)) $classe = 'L1G1';
    elseif (in_array('G_L1G2', $user_groups)) $classe = 'L1G2';
    elseif (in_array('G_L2G1', $user_groups)) $classe = 'L2G1';
    elseif (in_array('G_L2G2', $user_groups)) $classe = 'L2G2';

    $edt_query = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE classe = ? ORDER BY FIELD(jour_semaine, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'), heure_debut");
    $edt_query->bind_param("s", $classe);
}

$edt_query->execute();
$edt_result = $edt_query->get_result();

// Organiser les cours par jour
$emploi_du_temps = [];
$jours_ordre = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

while ($cours = $edt_result->fetch_assoc()) {
    $jour = $cours['jour_semaine'];
    if (!isset($emploi_du_temps[$jour])) {
        $emploi_du_temps[$jour] = [];
    }
    $emploi_du_temps[$jour][] = $cours;
}

// Jour actuel pour la mise en évidence
$jours_fr = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi', 
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];
$jour_actuel = $jours_fr[date('l')];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps complet - <?php echo htmlspecialchars($nom_complet); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="schedule-styles.css">
    <link rel="stylesheet" href="agenda-styles.css">
    <link rel="stylesheet" href="styleeleve.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <header class="agenda-header">
        <div class="header-navigation">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au profil
        </a>

        </div>
        <div class="header-title">
            <h1><i class="fas fa-calendar-week"></i> Emploi du temps complet</h1>
            <p><?php echo ($est_prof ? "Mes cours toutes classes" : "Classe " . htmlspecialchars($classe)); ?> - Semaine du <?php echo date('d/m/Y', strtotime('monday this week')); ?></p>

        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="printSchedule()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </header>

    <div class="agenda-container">
        <?php if (!empty($emploi_du_temps)): ?>
            <div class="week-view">
                <?php foreach ($jours_ordre as $jour): ?>
                    <?php if (isset($emploi_du_temps[$jour])): ?>
                        <div class="day-column <?php echo ($jour === $jour_actuel) ? 'current-day' : ''; ?>">
                            <div class="day-header">
                                <h3><?php echo $jour; ?></h3>
                                <div class="day-date">
                                    <?php 
                                    $day_number = array_search($jour, $jours_ordre);
                                    $date = date('d/m', strtotime('monday this week +' . $day_number . ' days'));
                                    echo $date;
                                    ?>
                                </div>
                                <?php if ($jour === $jour_actuel): ?>
                                    <div class="today-indicator">
                                        <i class="fas fa-circle"></i> Aujourd'hui
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="day-courses">
                                <?php 
                                $current_time = date('H:i');
                                foreach ($emploi_du_temps[$jour] as $cours): 
                                    $is_current = ($jour === $jour_actuel && $current_time >= $cours['heure_debut'] && $current_time <= $cours['heure_fin']);
                                    $is_upcoming = ($jour === $jour_actuel && $current_time < $cours['heure_debut']);
                                    $is_past = ($jour === $jour_actuel && $current_time > $cours['heure_fin']);
                                    
                                    $status_class = '';
                                    if ($is_current) {
                                        $status_class = 'current';
                                    } elseif ($is_upcoming) {
                                        $status_class = 'upcoming';
                                    } elseif ($is_past) {
                                        $status_class = 'past';
                                    }
                                ?>
                                    <div class="agenda-course-item <?php echo $status_class; ?>" data-subject="<?php echo htmlspecialchars($cours['matiere']); ?>">
                                        <div class="course-time-slot">
                                            <?php echo htmlspecialchars($cours['heure_debut']); ?>
                                            <span class="time-separator">-</span>
                                            <?php echo htmlspecialchars($cours['heure_fin']); ?>
                                        </div>
                                        <div class="course-content">
                                            <div class="course-subject-name">
                                                <?php echo htmlspecialchars($cours['matiere']); ?>
                                            </div>
                                            <div class="course-details-mini">
                                                <span class="course-room-mini">
                                                    <i class="fas fa-door-open"></i>
                                                    <?php echo htmlspecialchars($cours['salle']); ?>
                                                </span>
                                                <?php if ($est_prof): ?>
                                                    <span class="course-teacher-mini">
                                                        <i class="fas fa-users"></i>
                                                        Classe <?php echo htmlspecialchars($cours['classe']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="course-teacher-mini">
                                                        <i class="fas fa-user"></i>
                                                        <?php echo htmlspecialchars($cours['professeur']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($is_current): ?>
                                            <div class="live-indicator">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="day-column empty-day">
                            <div class="day-header">
                                <h3><?php echo $jour; ?></h3>
                                <div class="day-date">
                                    <?php 
                                    $day_number = array_search($jour, $jours_ordre);
                                    $date = date('d/m', strtotime('monday this week +' . $day_number . ' days'));
                                    echo $date;
                                    ?>
                                </div>
                            </div>
                            <div class="day-courses">
                                <div class="no-courses">
                                    <i class="fas fa-calendar-times"></i>
                                    <span>Aucun cours</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-schedule-full">
                <div class="empty-schedule-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h2>Aucun cours programmé</h2>
                <p>L'emploi du temps n'a pas encore été défini pour votre classe.</p>
                <a href="eleve.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour au profil
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="schedule-summary">
        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="summary-info">
                <h4>Heures de cours cette semaine</h4>
                <div class="summary-value">
                    <?php 
                    $total_hours = 0;
                    foreach ($emploi_du_temps as $jour => $cours_jour) {
                        foreach ($cours_jour as $cours) {
                            $debut = new DateTime($cours['heure_debut']);
                            $fin = new DateTime($cours['heure_fin']);
                            $duree = $fin->diff($debut);
                            $total_hours += $duree->h + ($duree->i / 60);
                        }
                    }
                    echo number_format($total_hours, 1) . 'h';
                    ?>
                </div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="summary-info">
                <h4>Matières différentes</h4>
                <div class="summary-value">
                    <?php 
                    $matieres = [];
                    foreach ($emploi_du_temps as $jour => $cours_jour) {
                        foreach ($cours_jour as $cours) {
                            $matieres[$cours['matiere']] = true;
                        }
                    }
                    echo count($matieres);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="summary-info">
                <h4>Professeurs différents</h4>
                <div class="summary-value">
                    <?php 
                    $professeurs = [];
                    foreach ($emploi_du_temps as $jour => $cours_jour) {
                        foreach ($cours_jour as $cours) {
                            $professeurs[$cours['professeur']] = true;
                        }
                    }
                    echo count($professeurs);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printSchedule() {
    window.print();
}
</script>
</body>
</html>