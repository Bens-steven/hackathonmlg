<?php 
session_start();

// ✅ CONTRÔLE D'ACCÈS - Seuls les élèves peuvent accéder
require_once 'check_access.php';
checkAccess(['G_Tous_Eleves']);

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

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

$user_groups = isset($_SESSION['groups']) ? $_SESSION['groups'] : [];

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
$mysqli->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
if ($mysqli->connect_error) {
    die("Erreur MySQL : " . $mysqli->connect_error);
}

// Classe
$classe = '';
if (in_array('G_L1G1', $user_groups)) $classe = 'L1G1';
elseif (in_array('G_L1G2', $user_groups)) $classe = 'L1G2';
elseif (in_array('G_L2G1', $user_groups)) $classe = 'L2G1';
elseif (in_array('G_L2G2', $user_groups)) $classe = 'L2G2';

// Devoirs récents (uniquement le plus récent)
$stmt = $mysqli->prepare("
    SELECT 
        d.id,
        d.titre, 
        d.contenu, 
        d.date_creation,
        d.date_limite,
        d.fichier,
        rd.fichier_rendu,
        rd.date_rendu,
        d.matiere,
        CASE WHEN rd.fichier_rendu IS NOT NULL THEN 1 ELSE 0 END as rendu
    FROM devoirs d
    LEFT JOIN rendus_devoirs rd ON d.id = rd.devoir_id AND rd.eleve_username = ?
    WHERE d.classe = ? 
    ORDER BY d.date_creation DESC 
    LIMIT 1
");
$stmt->bind_param("ss", $username, $classe);
$stmt->execute();
$result = $stmt->get_result();
$devoirs = [];
while ($row = $result->fetch_assoc()) {
    $devoirs[] = $row;
}

// Compter le nombre total de devoirs
$stmt_count = $mysqli->prepare("SELECT COUNT(*) as total FROM devoirs WHERE classe = ?");
$stmt_count->bind_param("s", $classe);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_devoirs = $result_count->fetch_assoc()['total'];

// Notes récentes
$stmt_notes = $mysqli->prepare("SELECT note, matiere, date FROM notes WHERE eleve_username = ? ORDER BY date DESC LIMIT 3");
$stmt_notes->bind_param("s", $username);
$stmt_notes->execute();
$result_notes = $stmt_notes->get_result();
$notes_recentes = [];
while ($row_notes = $result_notes->fetch_assoc()) {
    $notes_recentes[] = $row_notes;
}

// Nombre total de notes
$stmt_count = $mysqli->prepare("SELECT COUNT(*) as total FROM notes WHERE eleve_username = ?");
$stmt_count->bind_param("s", $username);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_notes = $result_count->fetch_assoc()['total'];

// Compter les annonces non lues (simulé)
$stmt_annonces = $mysqli->prepare("SELECT COUNT(*) as total FROM annonces");
$stmt_annonces->execute();
$result_annonces = $stmt_annonces->get_result();
$total_annonces = $result_annonces->fetch_assoc()['total'];

// Compter les absences et retards de l'élève
$stmt_absences = $mysqli->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
        SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
    FROM absence_retard 
    WHERE eleve = ?
");
$stmt_absences->bind_param("s", $username);
$stmt_absences->execute();
$result_absences = $stmt_absences->get_result();
$absences_stats = $result_absences->fetch_assoc();

// Emploi du temps du jour actuel
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

$edt_today_query = $mysqli->prepare("SELECT * FROM emplois_du_temps WHERE classe = ? AND jour_semaine = ? ORDER BY heure_debut");
$edt_today_query->bind_param("ss", $classe, $jour_actuel);
$edt_today_query->execute();
$edt_today_result = $edt_today_query->get_result();

// Compter le nombre total de cours dans la semaine
$edt_count_query = $mysqli->prepare("SELECT COUNT(*) as total FROM emplois_du_temps WHERE classe = ?");
$edt_count_query->bind_param("s", $classe);
$edt_count_query->execute();
$edt_count_result = $edt_count_query->get_result();
$total_cours = $edt_count_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Profil Élève - <?php echo htmlspecialchars($nom_complet); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="theme-color" content="#3b82f6">
  
  <!-- Styles -->
  <link rel="stylesheet" href="styleeleve.css">
  <link rel="stylesheet" href="styleeleve-mobile.css">
  <link rel="stylesheet" href="schedule-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <!-- PWA Meta -->
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="photos/educonnect-icon.png">
</head>
<body class="student-page">
<div class="container">
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
          <span class="meta-item"><i class="fas fa-user-graduate"></i> Élève</span>
          <?php if ($classe): ?>
            <span class="meta-item"><i class="fas fa-users"></i> Classe <?php echo htmlspecialchars($classe); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="header-actions">
      <a href="annonces.php" class="btn btn-announcements">
        <i class="fas fa-bullhorn"></i> 
        <span class="btn-text">Annonces</span>
        <?php if ($total_annonces > 0): ?>
          <span class="notification-badge" id="announcementsBadge"><?php echo $total_annonces; ?></span>
        <?php endif; ?>
      </a>
      <a href="absence_retard.php" class="btn btn-warning">
        <i class="fas fa-user-clock"></i>
        <span class="btn-text">Absences/Retards</span>
        <?php if ($absences_stats['total'] > 0): ?>
          <span class="notification-badge"><?php echo $absences_stats['total']; ?></span>
        <?php endif; ?>
      </a>
      <button class="btn btn-primary" onclick="toggleEditMode()">
        <i class="fas fa-edit"></i> Modifier
      </button>
      <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
  </header>

  <div class="main-content">
    <div class="content-left">
      <section class="card schedule-card">
        <div class="card-header">
          <h3><i class="fas fa-calendar-day"></i> Emploi du temps - <?php echo $jour_actuel; ?></h3>
          <div class="schedule-date">
            <i class="fas fa-calendar-alt"></i>
            <?php echo date('d/m/Y'); ?>
          </div>
        </div>
        <div class="card-body">
          <?php if ($edt_today_result->num_rows > 0): ?>
            <div class="today-schedule">
              <?php 
              $current_time = date('H:i');
              while ($cours = $edt_today_result->fetch_assoc()): 
                $is_current = ($current_time >= $cours['heure_debut'] && $current_time <= $cours['heure_fin']);
                $is_upcoming = ($current_time < $cours['heure_debut']);
                $is_past = ($current_time > $cours['heure_fin']);
                
                $status_class = '';
                $status_text = '';
                $status_icon = '';
                
                if ($is_current) {
                  $status_class = 'current';
                  $status_text = 'En cours';
                  $status_icon = 'fas fa-play-circle';
                } elseif ($is_upcoming) {
                  $status_class = 'upcoming';
                  $status_text = 'À venir';
                  $status_icon = 'fas fa-clock';
                } else {
                  $status_class = 'past';
                  $status_text = 'Terminé';
                  $status_icon = 'fas fa-check-circle';
                }
              ?>
                <div class="course-item <?php echo $status_class; ?>" data-subject="<?php echo htmlspecialchars($cours['matiere']); ?>">
                  <div class="course-time">
                    <div class="time-range">
                      <i class="fas fa-clock"></i>
                      <?php echo htmlspecialchars($cours['heure_debut'] . ' - ' . $cours['heure_fin']); ?>
                    </div>
                    <div class="course-status <?php echo $status_class; ?>">
                      <i class="<?php echo $status_icon; ?>"></i>
                      <?php echo $status_text; ?>
                    </div>
                  </div>
                  <div class="course-details">
                    <div class="course-subject">
                      <?php echo htmlspecialchars($cours['matiere']); ?>
                    </div>
                    <div class="course-info">
                      <span class="course-room">
                        <i class="fas fa-door-open"></i>
                        Salle <?php echo htmlspecialchars($cours['salle']); ?>
                      </span>
                      <span class="course-teacher">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?php echo htmlspecialchars($cours['professeur']); ?>
                      </span>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
            
            <?php if ($total_cours > 0): ?>
              <div class="show-all-container">
                <a href="emploi-du-temps-complet.php" class="btn btn-gradient btn-full">
                  <i class="fas fa-calendar-week"></i> Voir l'emploi du temps complet (<?php echo $total_cours; ?> cours cette semaine)
                </a>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="empty-schedule">
              <div class="empty-schedule-icon">
                <i class="fas fa-calendar-times"></i>
              </div>
              <h4>Aucun cours aujourd'hui</h4>
              <p>Profitez de cette journée libre !</p>
              <?php if ($total_cours > 0): ?>
                <a href="emploi-du-temps-complet.php" class="btn btn-primary">
                  <i class="fas fa-calendar-week"></i> Voir l'emploi du temps de la semaine
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="card grades-card">
        <div class="card-header">
          <h3><i class="fas fa-chart-line"></i> Mes notes récentes</h3>
        </div>
        <div class="card-body">
          <?php if ($notes_recentes): ?>
            <div class="grades-container">
              <?php foreach ($notes_recentes as $note): ?>
                <?php
                $note_value = floatval($note['note']);
                $grade_class = match (true) {
                    $note_value >= 16 => 'excellent',
                    $note_value >= 12 => 'good',
                    $note_value >= 10 => 'average',
                    default => 'poor',
                };
                ?>
                <div class="grade-item">
                  <div class="subject-info">
                    <span class="subject-name"><?php echo htmlspecialchars($note['matiere']); ?></span>
                    <span class="grade-date"><?php echo date('d/m/Y', strtotime($note['date'])); ?></span>
                  </div>
                  <div class="grade-value <?php echo $grade_class; ?>">
                    <?php echo htmlspecialchars($note['note']); ?>/20
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if ($total_notes > 3): ?>
              <div class="show-all-container">
                <a href="toutes_notes.php" class="btn btn-gradient btn-full">
                  <i class="fas fa-eye"></i> Tout afficher (<?php echo $total_notes; ?> notes)
                </a>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <p class="empty-state">Les notes seront affichées ici une fois saisies.</p>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <div class="content-right">
      <section class="card photo-card">
        <div class="card-header">
          <h3><i class="fas fa-camera"></i> Photo de profil</h3>
        </div>
        <div class="card-body">
          <div class="photo-upload-container">
            <div class="current-photo">
              <img src="<?php echo $photo_path . (str_starts_with($photo_path, 'data:image') ? '' : '?v=' . time()); ?>" alt="Photo de profil" id="currentPhoto">
              <div class="photo-overlay"><i class="fas fa-camera"></i></div>
            </div>
            <form action="upload_photo.php" method="POST" enctype="multipart/form-data" class="upload-form">
              <input type="file" name="photo" accept="image/*" id="photoInput" required>
              <label for="photoInput" class="btn btn-gradient"><i class="fas fa-upload"></i> Changer la photo</label>
              <button type="submit" class="btn btn-primary" id="uploadBtn" style="display: none;"><i class="fas fa-check"></i> Confirmer</button>
            </form>
            <p class="upload-info">Formats acceptés: JPG, PNG, GIF<br>Taille max: 5MB</p>
          </div>
        </div>
      </section>

      <section class="card homework-card">
        <div class="card-header">
          <h3><i class="fas fa-tasks"></i> Devoirs récents</h3>
        </div>
        <div class="card-body">
          <?php if ($devoirs): ?>
            <div class="homework-container">
              <?php
              // Initialisation d'un tableau pour grouper les devoirs par matière
              $groupedDevoirs = [];
              foreach ($devoirs as $devoir) {
                $matiere = $devoir['matiere']; // On suppose que 'matiere' est une colonne dans la table 'devoirs'
                if (!isset($groupedDevoirs[$matiere])) {
                  $groupedDevoirs[$matiere] = [];
                }
                $groupedDevoirs[$matiere][] = $devoir;
              }

              // Affichage des devoirs groupés par matière
              foreach ($groupedDevoirs as $matiere => $devoirsMatiere):
              ?>
                <div class="homework-subject-section">
                  <h4 class="homework-subject"><?php echo htmlspecialchars($matiere); ?></h4>
                  <?php foreach ($devoirsMatiere as $devoir): ?>
                    <?php
                    $isOverdue = new DateTime($devoir['date_limite']) < new DateTime() && !$devoir['rendu'];
                    $statusClass = $devoir['rendu'] ? 'rendered' : ($isOverdue ? 'overdue' : 'pending');
                    ?>
                    <div class="homework-item <?php echo $statusClass; ?>">
                      <div class="homework-header">
                        <div class="homework-status">
                          <?php if ($devoir['rendu']): ?>
                            <span class="status-badge rendered">
                              <i class="fas fa-check-circle"></i> Rendu
                            </span>
                          <?php elseif ($isOverdue): ?>
                            <span class="status-badge overdue">
                              <i class="fas fa-times-circle"></i> En retard
                            </span>
                          <?php else: ?>
                            <span class="status-badge pending">
                              <i class="fas fa-clock"></i> À rendre
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="homework-title"><?php echo htmlspecialchars($devoir['titre']); ?></div>
                      <div class="homework-content"><?php echo htmlspecialchars($devoir['contenu']); ?></div>
                      <div class="homework-dates">
                        <div class="homework-date">
                          <i class="fas fa-calendar"></i> Créé le <?php echo date('d/m/Y', strtotime($devoir['date_creation'])); ?>
                        </div>
                        <div class="homework-deadline <?php echo $isOverdue ? 'overdue' : ''; ?>">
                          <i class="fas fa-hourglass-end"></i> À rendre avant le <?php echo date('d/m/Y à H:i', strtotime($devoir['date_limite'])); ?>
                        </div>
                      </div>
                      <div class="homework-actions">
                        <?php if ($devoir['fichier']): ?>
                          <a href="pieces_jointes/<?php echo htmlspecialchars($devoir['fichier']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="fas fa-paperclip"></i> Pièce jointe
                          </a>
                        <?php endif; ?>
                        <?php if (!$devoir['rendu']): ?>
                          <button class="btn btn-sm btn-primary" onclick="showSubmitModal(<?php echo $devoir['id']; ?>, '<?php echo htmlspecialchars($devoir['titre']); ?>')">
                            <i class="fas fa-upload"></i> Rendre le devoir
                          </button>
                        <?php else: ?>
                          <span class="rendered-info">
                            <i class="fas fa-check"></i> Rendu le <?php echo date('d/m/Y à H:i', strtotime($devoir['date_rendu'])); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if ($total_devoirs > 0): ?>
              <div class="show-all-container">
                <a href="tous_devoirs.php" class="btn btn-gradient btn-full">
                  <i class="fas fa-eye"></i> Tout afficher (<?php echo $total_devoirs; ?> devoirs)
                </a>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <p class="empty-state">Les devoirs seront affichés ici une fois définis par le professeur.</p>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- 🔔 SCRIPTS POUR LES NOTIFICATIONS PUSH -->
<script src="notifications.js"></script>
<script src="notification-setup.js"></script>
<script src="scripteleve-mobile.js"></script>
<script src="schedule-script.js"></script>

<script>
// Variables globales pour le modal de rendu
let currentHomeworkId = null;

// Fonction pour afficher le modal de rendu
function showSubmitModal(homeworkId, title) {
    currentHomeworkId = homeworkId;
    
    document.getElementById('homework-info').innerHTML = `
        <div class="homework-info-card">
            <h4>${title}</h4>
            <p>Sélectionnez le fichier que vous souhaitez rendre pour ce devoir.</p>
        </div>
    `;
    
    document.getElementById('devoir_id').value = homeworkId;
    document.getElementById('submitModal').style.display = 'flex';
}

// Fonction pour fermer le modal de rendu
function closeSubmitModal() {
    document.getElementById('submitModal').style.display = 'none';
    document.getElementById('submitForm').reset();
    currentHomeworkId = null;
}

// Fonction pour soumettre le devoir
function submitHomework() {
    const form = document.getElementById('submitForm');
    const fileInput = document.getElementById('fichier_rendu');
    
    if (!fileInput.files[0]) {
        showNotification('Veuillez sélectionner un fichier à rendre.', 'error');
        return;
    }
    
    // Vérifier la taille du fichier (10MB max)
    if (fileInput.files[0].size > 10 * 1024 * 1024) {
        showNotification('La taille du fichier ne doit pas dépasser 10MB.', 'error');
        return;
    }
    
    form.submit();
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    const modal = document.getElementById('submitModal');
    if (e.target === modal) {
        closeSubmitModal();
    }
});

// Raccourci clavier pour fermer le modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSubmitModal();
    }
});

document.getElementById('photoInput').addEventListener('change', function () {
  document.getElementById('uploadBtn').style.display = 'inline-block';
});

// Mettre à jour le badge des annonces
document.addEventListener('DOMContentLoaded', function() {
    updateAnnouncementsBadge();
});

function updateAnnouncementsBadge() {
    const readAnnouncements = JSON.parse(localStorage.getItem('readAnnouncements') || '[]');
    const totalAnnouncements = <?php echo $total_annonces; ?>;
    const unreadCount = Math.max(0, totalAnnouncements - readAnnouncements.length);
    
    const badge = document.getElementById('announcementsBadge');
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Service Worker pour PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}
</script>
</body>
</html>