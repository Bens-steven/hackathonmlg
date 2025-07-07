<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

// Simulation des données élève (à remplacer par vos vraies données)
$student = [
    'username' => $_SESSION['username'],
    'prenom' => 'Jean',
    'nom' => 'Dupont',
    'classe' => '1ère S',
    'photo' => 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop'
];

$emploi_du_temps = [
    'Lundi' => [
        ['heure' => '08:00-09:00', 'matiere' => 'Mathématiques', 'salle' => 'A101'],
        ['heure' => '09:00-10:00', 'matiere' => 'Français', 'salle' => 'B205'],
        ['heure' => '10:15-11:15', 'matiere' => 'Physique', 'salle' => 'C301'],
        ['heure' => '11:15-12:15', 'matiere' => 'Histoire', 'salle' => 'A203']
    ],
    'Mardi' => [
        ['heure' => '08:00-09:00', 'matiere' => 'Anglais', 'salle' => 'B102'],
        ['heure' => '09:00-10:00', 'matiere' => 'SVT', 'salle' => 'C205'],
        ['heure' => '10:15-11:15', 'matiere' => 'Mathématiques', 'salle' => 'A101'],
        ['heure' => '11:15-12:15', 'matiere' => 'Sport', 'salle' => 'Gymnase']
    ]
];

$devoirs = [
    ['matiere' => 'Mathématiques', 'titre' => 'Exercices chapitre 5', 'date' => '2025-01-25', 'statut' => 'en_cours'],
    ['matiere' => 'Français', 'titre' => 'Dissertation sur Molière', 'date' => '2025-01-28', 'statut' => 'a_faire'],
    ['matiere' => 'Physique', 'titre' => 'TP Optique', 'date' => '2025-01-30', 'statut' => 'a_faire'],
    ['matiere' => 'Histoire', 'titre' => 'Exposé Révolution', 'date' => '2025-01-22', 'statut' => 'termine']
];

$notes = [
    ['matiere' => 'Mathématiques', 'note' => '16/20', 'date' => '2025-01-15', 'type' => 'Contrôle'],
    ['matiere' => 'Français', 'note' => '14/20', 'date' => '2025-01-12', 'type' => 'Devoir'],
    ['matiere' => 'Physique', 'note' => '18/20', 'date' => '2025-01-10', 'type' => 'TP'],
    ['matiere' => 'Histoire', 'note' => '15/20', 'date' => '2025-01-08', 'type' => 'Interrogation']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - EduConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(45deg, #2196F3, #4CAF50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logout-btn {
            background: linear-gradient(45deg, #f44336, #e91e63);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .profile-photo {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-photo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #2196F3;
            transition: all 0.3s ease;
        }

        .profile-photo:hover img {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
        }

        .edit-photo {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-photo:hover {
            background: #45a049;
            transform: scale(1.1);
        }

        .student-name {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, #2196F3, #4CAF50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .student-class {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tab-btn.active {
            background: linear-gradient(45deg, #2196F3, #4CAF50);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }

        .tab-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 1);
            border-color: #2196F3;
            transform: translateY(-1px);
        }

        .tab-content {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .schedule-grid {
            display: grid;
            gap: 20px;
        }

        .day-schedule {
            background: linear-gradient(135deg, #f8f9ff, #e8f5e8);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #2196F3;
        }

        .day-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2196F3;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .course {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .course:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .course-info h4 {
            color: #2196F3;
            margin-bottom: 5px;
        }

        .course-details {
            font-size: 0.9rem;
            color: #666;
        }

        .homework-item, .grade-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #4CAF50;
        }

        .homework-item:hover, .grade-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .homework-header, .grade-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .subject-tag {
            background: linear-gradient(45deg, #2196F3, #4CAF50);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.en_cours { background: #fff3cd; color: #856404; }
        .status.a_faire { background: #f8d7da; color: #721c24; }
        .status.termine { background: #d4edda; color: #155724; }

        .grade-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4CAF50;
        }

        .responsive-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .student-name {
                font-size: 2rem;
            }
            
            .tabs {
                flex-direction: column;
                align-items: center;
            }
            
            .tab-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        .file-input {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i> EduConnect
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </div>

        <div class="profile-section">
            <div class="profile-photo">
                <img src="<?php echo $student['photo']; ?>" alt="Photo de profil" id="profileImage">
                <button class="edit-photo" onclick="document.getElementById('photoInput').click()">
                    <i class="fas fa-camera"></i>
                </button>
                <input type="file" id="photoInput" class="file-input" accept="image/*" onchange="changePhoto(event)">
            </div>
            <h1 class="student-name"><?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?></h1>
            <p class="student-class">Classe : <?php echo htmlspecialchars($student['classe']); ?></p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('schedule')">
                <i class="fas fa-calendar-alt"></i>
                Emploi du temps
            </button>
            <button class="tab-btn" onclick="showTab('homework')">
                <i class="fas fa-tasks"></i>
                Devoirs
            </button>
            <button class="tab-btn" onclick="showTab('grades')">
                <i class="fas fa-chart-line"></i>
                Notes
            </button>
        </div>

        <div id="schedule" class="tab-content active">
            <h2 style="text-align: center; margin-bottom: 30px; color: #2196F3;">
                <i class="fas fa-calendar-week"></i> Mon Emploi du Temps
            </h2>
            <div class="schedule-grid">
                <?php foreach ($emploi_du_temps as $jour => $cours): ?>
                <div class="day-schedule">
                    <div class="day-title">
                        <i class="fas fa-calendar-day"></i>
                        <?php echo $jour; ?>
                    </div>
                    <?php foreach ($cours as $c): ?>
                    <div class="course">
                        <div class="course-info">
                            <h4><?php echo htmlspecialchars($c['matiere']); ?></h4>
                            <div class="course-details">
                                <i class="fas fa-clock"></i> <?php echo $c['heure']; ?> | 
                                <i class="fas fa-door-open"></i> Salle <?php echo $c['salle']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="homework" class="tab-content">
            <h2 style="text-align: center; margin-bottom: 30px; color: #2196F3;">
                <i class="fas fa-clipboard-list"></i> Mes Devoirs
            </h2>
            <div class="responsive-grid">
                <?php foreach ($devoirs as $devoir): ?>
                <div class="homework-item">
                    <div class="homework-header">
                        <span class="subject-tag"><?php echo htmlspecialchars($devoir['matiere']); ?></span>
                        <span class="status <?php echo $devoir['statut']; ?>">
                            <?php 
                            $statuts = [
                                'en_cours' => 'En cours',
                                'a_faire' => 'À faire',
                                'termine' => 'Terminé'
                            ];
                            echo $statuts[$devoir['statut']];
                            ?>
                        </span>
                    </div>
                    <h3 style="color: #333; margin-bottom: 10px;"><?php echo htmlspecialchars($devoir['titre']); ?></h3>
                    <p style="color: #666;">
                        <i class="fas fa-calendar"></i> 
                        Pour le <?php echo date('d/m/Y', strtotime($devoir['date'])); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="grades" class="tab-content">
            <h2 style="text-align: center; margin-bottom: 30px; color: #2196F3;">
                <i class="fas fa-trophy"></i> Mes Notes
            </h2>
            <div class="responsive-grid">
                <?php foreach ($notes as $note): ?>
                <div class="grade-item">
                    <div class="grade-header">
                        <span class="subject-tag"><?php echo htmlspecialchars($note['matiere']); ?></span>
                        <span class="grade-value"><?php echo htmlspecialchars($note['note']); ?></span>
                    </div>
                    <h3 style="color: #333; margin-bottom: 10px;"><?php echo htmlspecialchars($note['type']); ?></h3>
                    <p style="color: #666;">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('d/m/Y', strtotime($note['date'])); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Masquer tous les contenus
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Désactiver tous les boutons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Afficher le contenu sélectionné
            document.getElementById(tabName).classList.add('active');
            
            // Activer le bouton correspondant
            event.target.classList.add('active');
        }

        function changePhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                    // Ici vous pouvez ajouter le code pour sauvegarder la photo sur le serveur
                };
                reader.readAsDataURL(file);
            }
        }

        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.course, .homework-item, .grade-item');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    el.style.transition = 'all 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>