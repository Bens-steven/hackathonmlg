<?php
session_start();

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username']) || !in_array('G_Admin_Direction', $_SESSION['groups'] ?? [])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que les paramètres sont fournis
if (!isset($_POST['username']) || !isset($_POST['classe'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$username = $_POST['username'];
$classe = $_POST['classe'];

// Connexion LDAP pour vérifier que l'utilisateur existe
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

// Vérifier que l'utilisateur existe dans l'AD - recherche plus flexible
$search = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(|(sAMAccountName=$username)(cn=$username))", ["cn", "sAMAccountName", "memberOf"]);
$entries = ldap_get_entries($ldapconn, $search);

if ($entries["count"] == 0) {
    // Essayer une recherche encore plus large
    $search2 = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(cn=*$username*)", ["cn", "sAMAccountName", "memberOf"]);
    $entries2 = ldap_get_entries($ldapconn, $search2);
    
    if ($entries2["count"] == 0) {
        ldap_unbind($ldapconn);
        echo json_encode(['success' => false, 'message' => "Utilisateur '$username' non trouvé dans l'Active Directory"]);
        exit;
    }
    $entries = $entries2;
}

// Récupérer le vrai nom d'utilisateur depuis l'AD
$realUsername = $entries[0]["samaccountname"][0] ?? $username;
$displayName = $entries[0]["cn"][0] ?? $username;

ldap_unbind($ldapconn);

// Connexion à MySQL pour les données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Créer des variantes du nom d'utilisateur pour la recherche
    $username_variants = [
        $realUsername,
        $username,
        $displayName,
        strtolower($realUsername),
        strtolower($username),
        strtolower($displayName),
        ucfirst(strtolower($realUsername)),
        ucfirst(strtolower($username)),
        str_replace(' ', '.', strtolower($displayName)),
        str_replace(' ', '', strtolower($displayName))
    ];
    
    // Supprimer les doublons
    $username_variants = array_unique($username_variants);
    
    $notes = [];
    $moyenne_generale = 0;
    $nb_absences = 0;
    $nb_retards = 0;
    
    // Essayer de trouver les notes avec chaque variante
    foreach ($username_variants as $variant) {
        $stmt_notes = $mysqli->prepare("
            SELECT 
                matiere,
                ROUND(AVG(note), 1) as moyenne
            FROM notes 
            WHERE eleve_username = ? AND classe = ?
            GROUP BY matiere
            ORDER BY matiere
        ");
        $stmt_notes->bind_param("ss", $variant, $classe);
        $stmt_notes->execute();
        $result_notes = $stmt_notes->get_result();
        
        if ($result_notes->num_rows > 0) {
            $notes = [];
            while ($row = $result_notes->fetch_assoc()) {
                $notes[] = [
                    'matiere' => $row['matiere'],
                    'moyenne' => $row['moyenne']
                ];
            }
            $stmt_notes->close();
            break; // On a trouvé des notes, on arrête
        }
        $stmt_notes->close();
    }
    
    // Calculer la moyenne générale
    foreach ($username_variants as $variant) {
        $stmt_moyenne = $mysqli->prepare("
            SELECT ROUND(AVG(note), 1) as moyenne_generale
            FROM notes 
            WHERE eleve_username = ? AND classe = ?
        ");
        $stmt_moyenne->bind_param("ss", $variant, $classe);
        $stmt_moyenne->execute();
        $result_moyenne = $stmt_moyenne->get_result();
        $moyenne_data = $result_moyenne->fetch_assoc();
        $stmt_moyenne->close();
        
        if ($moyenne_data['moyenne_generale'] > 0) {
            $moyenne_generale = $moyenne_data['moyenne_generale'];
            break;
        }
    }
    
    // Récupérer les statistiques d'absence
    foreach ($username_variants as $variant) {
        $stmt_absences = $mysqli->prepare("
            SELECT 
                SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
                SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
            FROM absence_retard 
            WHERE eleve = ? AND classe = ?
        ");
        $stmt_absences->bind_param("ss", $variant, $classe);
        $stmt_absences->execute();
        $result_absences = $stmt_absences->get_result();
        $absences_data = $result_absences->fetch_assoc();
        $stmt_absences->close();
        
        if ($absences_data['nb_absences'] > 0 || $absences_data['nb_retards'] > 0) {
            $nb_absences = $absences_data['nb_absences'] ?? 0;
            $nb_retards = $absences_data['nb_retards'] ?? 0;
            break;
        }
    }
    
    $mysqli->close();
    
    $student = [
        'username' => $realUsername,
        'display_name' => $displayName,
        'classe' => $classe,
        'notes' => $notes,
        'moyenne_generale' => $moyenne_generale,
        'nb_absences' => $nb_absences,
        'nb_retards' => $nb_retards
    ];
    
    echo json_encode([
        'success' => true,
        'student' => $student
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>