<?php
session_start();

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username']) || !in_array('G_Admin_Direction', $_SESSION['groups'] ?? [])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que les paramètres sont fournis
if (!isset($_POST['username']) || !isset($_POST['matiere'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$username = trim($_POST['username']);
$matiere = trim($_POST['matiere']);

error_log("🔍 Récupération des détails pour le professeur: $username, matière: $matiere");

// Connexion LDAP
$ldapconn = ldap_connect("ldap://192.168.20.132");
if (!$ldapconn) {
    echo json_encode(['success' => false, 'message' => 'Connexion LDAP impossible']);
    exit;
}

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");
if (!$ldapbind) {
    echo json_encode(['success' => false, 'message' => 'Authentification LDAP échouée']);
    exit;
}

// Connexion à MySQL pour les données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Rechercher l'utilisateur dans l'AD
    $search = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(|(sAMAccountName=$username)(cn=$username))", ["cn", "sAMAccountName", "memberOf"]);
    $entries = ldap_get_entries($ldapconn, $search);

    if ($entries["count"] == 0) {
        throw new Exception("Professeur '$username' non trouvé dans l'Active Directory");
    }

    // Récupérer les informations du professeur
    $realUsername = $entries[0]["samaccountname"][0] ?? $username;
    $displayName = $entries[0]["cn"][0] ?? $username;
    
    // Récupérer les classes auxquelles le professeur enseigne
    $userClasses = [];
    if (isset($entries[0]["memberof"])) {
        for ($i = 0; $i < $entries[0]["memberof"]["count"]; $i++) {
            $group = $entries[0]["memberof"][$i];
            if (preg_match('/CN=G_(L[1-2]G[1-2])/', $group, $matches)) {
                $userClasses[] = $matches[1];
            }
        }
    }
    
    // ✅ FONCTION POUR COMPTER LES ÉLÈVES RÉELS DANS UNE CLASSE VIA LDAP
    function countStudentsInClassLDAP($ldapconn, $classe) {
        $groupName = "G_" . $classe;
        
        // Rechercher le groupe de classe dans l'AD
        $search_class = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=$groupName)", ["member"]);
        if (!$search_class) {
            error_log("❌ Impossible de trouver le groupe $groupName");
            return 0;
        }
        
        $class_entries = ldap_get_entries($ldapconn, $search_class);
        
        if ($class_entries["count"] == 0 || !isset($class_entries[0]["member"])) {
            error_log("⚠️ Aucun membre dans le groupe $groupName");
            return 0;
        }
        
        // Récupérer tous les membres du groupe G_Tous_Eleves pour vérification
        $search_all_students = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_Tous_Eleves)", ["member"]);
        $all_students_dns = [];
        
        if ($search_all_students) {
            $all_students_entries = ldap_get_entries($ldapconn, $search_all_students);
            if ($all_students_entries["count"] > 0 && isset($all_students_entries[0]["member"])) {
                for ($i = 0; $i < $all_students_entries[0]["member"]["count"]; $i++) {
                    $all_students_dns[] = $all_students_entries[0]["member"][$i];
                }
            }
        }
        
        // Compter les membres qui sont à la fois dans la classe ET dans G_Tous_Eleves
        $student_count = 0;
        for ($i = 0; $i < $class_entries[0]["member"]["count"]; $i++) {
            $memberDN = $class_entries[0]["member"][$i];
            
            // Vérifier si ce membre est aussi un élève
            if (in_array($memberDN, $all_students_dns)) {
                $student_count++;
            }
        }
        
        error_log("👥 Classe $classe: $student_count élèves trouvés via LDAP");
        return $student_count;
    }
    
    // Récupérer les statistiques détaillées pour chaque classe
    $detailed_stats = [];
    $total_students = 0;
    $total_notes = 0;
    $sum_averages = 0;
    $classes_with_data = 0;
    
    foreach ($userClasses as $classe) {
        // ✅ COMPTER LES ÉLÈVES RÉELS VIA LDAP
        $nb_eleves_ldap = countStudentsInClassLDAP($ldapconn, $classe);
        
        // Moyenne de la classe dans cette matière
        $stmt = $mysqli->prepare("SELECT ROUND(AVG(note), 1) as moyenne FROM notes WHERE classe = ? AND matiere = ?");
        $stmt->bind_param("ss", $classe, $matiere);
        $stmt->execute();
        $result = $stmt->get_result();
        $moyenneData = $result->fetch_assoc();
        $moyenne = $moyenneData['moyenne'] ?? 0;
        $stmt->close();
        
        // Nombre d'élèves ayant des notes dans cette matière (pour info)
        $stmt = $mysqli->prepare("SELECT COUNT(DISTINCT eleve_username) as nb_eleves_avec_notes FROM notes WHERE classe = ? AND matiere = ?");
        $stmt->bind_param("ss", $classe, $matiere);
        $stmt->execute();
        $result = $stmt->get_result();
        $elevesData = $result->fetch_assoc();
        $nb_eleves_avec_notes = $elevesData['nb_eleves_avec_notes'] ?? 0;
        $stmt->close();
        
        // Nombre total de notes données dans cette classe
        $stmt = $mysqli->prepare("SELECT COUNT(*) as nb_notes FROM notes WHERE classe = ? AND matiere = ?");
        $stmt->bind_param("ss", $classe, $matiere);
        $stmt->execute();
        $result = $stmt->get_result();
        $notesData = $result->fetch_assoc();
        $nb_notes = $notesData['nb_notes'] ?? 0;
        $stmt->close();
        
        // Répartition des notes (excellent, bon, moyen, faible)
        $stmt = $mysqli->prepare("
            SELECT 
                SUM(CASE WHEN note >= 16 THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN note >= 12 AND note < 16 THEN 1 ELSE 0 END) as bon,
                SUM(CASE WHEN note >= 10 AND note < 12 THEN 1 ELSE 0 END) as moyen,
                SUM(CASE WHEN note < 10 THEN 1 ELSE 0 END) as faible
            FROM notes 
            WHERE classe = ? AND matiere = ?
        ");
        $stmt->bind_param("ss", $classe, $matiere);
        $stmt->execute();
        $result = $stmt->get_result();
        $repartition = $result->fetch_assoc();
        $stmt->close();
        
        if ($moyenne > 0) {
            $sum_averages += $moyenne;
            $classes_with_data++;
        }
        
        // ✅ UTILISER LE NOMBRE D'ÉLÈVES LDAP POUR LE TOTAL
        $total_students += $nb_eleves_ldap;
        $total_notes += $nb_notes;
        
        $detailed_stats[] = [
            'classe' => $classe,
            'moyenne' => $moyenne,
            'nb_eleves' => $nb_eleves_ldap, // ✅ Nombre réel d'élèves via LDAP
            'nb_eleves_avec_notes' => $nb_eleves_avec_notes, // Info supplémentaire
            'nb_notes' => $nb_notes,
            'repartition' => [
                'excellent' => intval($repartition['excellent'] ?? 0),
                'bon' => intval($repartition['bon'] ?? 0),
                'moyen' => intval($repartition['moyen'] ?? 0),
                'faible' => intval($repartition['faible'] ?? 0)
            ]
        ];
    }
    
    // Calculer la moyenne générale du professeur
    $moyenne_generale = $classes_with_data > 0 ? round($sum_averages / $classes_with_data, 1) : 0;
    
    ldap_unbind($ldapconn);
    $mysqli->close();
    
    $teacher = [
        'username' => $realUsername,
        'display_name' => $displayName,
        'matiere' => $matiere,
        'classes' => $userClasses,
        'detailed_stats' => $detailed_stats,
        'summary' => [
            'moyenne_generale' => $moyenne_generale,
            'total_students' => $total_students,
            'total_notes' => $total_notes,
            'total_classes' => count($userClasses),
            'classes_with_data' => $classes_with_data
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'teacher' => $teacher
    ]);
    
} catch (Exception $e) {
    error_log("💥 Erreur dans get_teacher_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>