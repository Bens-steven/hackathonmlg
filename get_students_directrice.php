<?php
session_start();

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username']) || !in_array('G_Admin_Direction', $_SESSION['groups'] ?? [])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que la classe est fournie
if (!isset($_POST['classe']) || empty($_POST['classe'])) {
    echo json_encode(['success' => false, 'message' => 'Classe non spécifiée']);
    exit;
}

$classe = trim($_POST['classe']);

// Log de débogage
error_log("🔍 Recherche des élèves pour la classe: " . $classe);

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
    echo json_encode(['success' => false, 'message' => 'Authentification LDAP échouée: ' . ldap_error($ldapconn)]);
    exit;
}

// Connexion à MySQL pour les données complémentaires
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $mysqli->connect_error]);
    exit;
}

try {
    // ÉTAPE 1: Récupérer tous les membres du groupe G_Tous_Eleves
    $search_all_students = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_Tous_Eleves)", ["member"]);
    if (!$search_all_students) {
        throw new Exception('Erreur lors de la recherche du groupe G_Tous_Eleves: ' . ldap_error($ldapconn));
    }
    
    $all_students_entries = ldap_get_entries($ldapconn, $search_all_students);
    
    $all_students_dns = [];
    if ($all_students_entries["count"] > 0 && isset($all_students_entries[0]["member"])) {
        for ($i = 0; $i < $all_students_entries[0]["member"]["count"]; $i++) {
            $all_students_dns[] = $all_students_entries[0]["member"][$i];
        }
    }
    
    error_log("🔍 Nombre total d'élèves dans G_Tous_Eleves: " . count($all_students_dns));
    
    // ÉTAPE 2: Rechercher le groupe de classe spécifique dans l'AD
    $search = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_$classe)", ["member"]);
    if (!$search) {
        throw new Exception('Erreur lors de la recherche du groupe G_' . $classe . ': ' . ldap_error($ldapconn));
    }
    
    $entries = ldap_get_entries($ldapconn, $search);
    
    if ($entries["count"] === 0) {
        echo json_encode([
            'success' => true,
            'students' => [],
            'debug' => [
                'classe' => $classe,
                'message' => 'Groupe de classe non trouvé dans l\'AD'
            ]
        ]);
        exit;
    }
    
    $students = [];
    
    if ($entries["count"] > 0 && isset($entries[0]["member"])) {
        error_log("🔍 Nombre de membres dans G_$classe: " . $entries[0]["member"]["count"]);
        
        // ÉTAPE 3: Parcourir tous les membres du groupe de classe
        for ($i = 0; $i < $entries[0]["member"]["count"]; $i++) {
            $memberDN = $entries[0]["member"][$i];
            
            // ÉTAPE 4: Vérifier si ce membre fait AUSSI partie du groupe G_Tous_Eleves
            if (!in_array($memberDN, $all_students_dns)) {
                error_log("❌ Utilisateur ignoré (pas dans G_Tous_Eleves): " . $memberDN);
                continue; // Ignorer cet utilisateur car il n'est pas un élève
            }
            
            error_log("✅ Élève validé: " . $memberDN);
            
            // Extraire le nom d'utilisateur du DN
            if (preg_match('/CN=([^,]+)/', $memberDN, $matches)) {
                $username = $matches[1];
                
                // Rechercher les détails de l'utilisateur dans l'AD pour obtenir le sAMAccountName
                $userSearch = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(cn=$username)", ["sAMAccountName", "cn"]);
                if (!$userSearch) {
                    error_log("⚠️ Impossible de rechercher les détails pour: " . $username);
                    continue;
                }
                
                $userEntries = ldap_get_entries($ldapconn, $userSearch);
                
                $realUsername = $username; // Par défaut
                if ($userEntries["count"] > 0) {
                    if (isset($userEntries[0]["samaccountname"][0])) {
                        $realUsername = $userEntries[0]["samaccountname"][0];
                    }
                }
                
                // Créer des variantes du nom d'utilisateur pour la recherche dans MySQL
                $username_variants = [
                    $realUsername,
                    $username,
                    strtolower($realUsername),
                    strtolower($username),
                    ucfirst(strtolower($realUsername)),
                    ucfirst(strtolower($username)),
                    str_replace(' ', '.', strtolower($username)),
                    str_replace(' ', '', strtolower($username)),
                    str_replace('.', ' ', $username),
                    str_replace('.', '', $username)
                ];
                
                // Supprimer les doublons et les valeurs vides
                $username_variants = array_unique(array_filter($username_variants));
                
                $moyenne_generale = 0;
                $nb_absences = 0;
                $nb_retards = 0;
                $found_data = false;
                
                // Debug: afficher les variantes testées
                error_log("🔍 Recherche pour utilisateur: $username (real: $realUsername)");
                
                // Essayer de trouver les notes avec chaque variante
                foreach ($username_variants as $variant) {
                    if (empty($variant)) continue;
                    
                    // Recherche exacte d'abord
                    $stmt = $mysqli->prepare("SELECT ROUND(AVG(note), 1) as moyenne_generale FROM notes WHERE eleve_username = ? AND classe = ?");
                    if (!$stmt) {
                        error_log("❌ Erreur préparation requête notes: " . $mysqli->error);
                        continue;
                    }
                    
                    $stmt->bind_param("ss", $variant, $classe);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $moyenneData = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($moyenneData && $moyenneData['moyenne_generale'] > 0) {
                        $moyenne_generale = $moyenneData['moyenne_generale'];
                        $found_data = true;
                        error_log("✅ Notes trouvées pour: $variant (moyenne: $moyenne_generale)");
                        break;
                    }
                }
                
                // Si pas trouvé, essayer une recherche LIKE
                if (!$found_data) {
                    foreach ($username_variants as $variant) {
                        if (empty($variant)) continue;
                        
                        $stmt = $mysqli->prepare("SELECT ROUND(AVG(note), 1) as moyenne_generale FROM notes WHERE eleve_username LIKE ? AND classe = ?");
                        if (!$stmt) continue;
                        
                        $like_variant = '%' . $variant . '%';
                        $stmt->bind_param("ss", $like_variant, $classe);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $moyenneData = $result->fetch_assoc();
                        $stmt->close();
                        
                        if ($moyenneData && $moyenneData['moyenne_generale'] > 0) {
                            $moyenne_generale = $moyenneData['moyenne_generale'];
                            $found_data = true;
                            error_log("✅ Notes trouvées avec LIKE pour: $variant (moyenne: $moyenne_generale)");
                            break;
                        }
                    }
                }
                
                // Essayer de trouver les absences avec chaque variante
                $found_absences = false;
                foreach ($username_variants as $variant) {
                    if (empty($variant)) continue;
                    
                    $stmt = $mysqli->prepare("
                        SELECT 
                            SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
                            SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
                        FROM absence_retard 
                        WHERE eleve = ? AND classe = ?
                    ");
                    if (!$stmt) continue;
                    
                    $stmt->bind_param("ss", $variant, $classe);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $absencesData = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($absencesData && ($absencesData['nb_absences'] > 0 || $absencesData['nb_retards'] > 0)) {
                        $nb_absences = $absencesData['nb_absences'] ?? 0;
                        $nb_retards = $absencesData['nb_retards'] ?? 0;
                        $found_absences = true;
                        error_log("✅ Absences trouvées pour: $variant (absences: $nb_absences, retards: $nb_retards)");
                        break;
                    }
                }
                
                // Si pas trouvé, essayer une recherche LIKE pour les absences
                if (!$found_absences) {
                    foreach ($username_variants as $variant) {
                        if (empty($variant)) continue;
                        
                        $stmt = $mysqli->prepare("
                            SELECT 
                                SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
                                SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
                            FROM absence_retard 
                            WHERE eleve LIKE ? AND classe = ?
                        ");
                        if (!$stmt) continue;
                        
                        $like_variant = '%' . $variant . '%';
                        $stmt->bind_param("ss", $like_variant, $classe);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $absencesData = $result->fetch_assoc();
                        $stmt->close();
                        
                        if ($absencesData && ($absencesData['nb_absences'] > 0 || $absencesData['nb_retards'] > 0)) {
                            $nb_absences = $absencesData['nb_absences'] ?? 0;
                            $nb_retards = $absencesData['nb_retards'] ?? 0;
                            error_log("✅ Absences trouvées avec LIKE pour: $variant");
                            break;
                        }
                    }
                }
                
                // Debug final
                if (!$found_data && $nb_absences == 0 && $nb_retards == 0) {
                    error_log("❌ Aucune donnée trouvée pour: $username");
                }
                
                $students[] = [
                    'username' => $username,
                    'real_username' => $realUsername,
                    'moyenne_generale' => $moyenne_generale,
                    'nb_absences' => $nb_absences,
                    'nb_retards' => $nb_retards
                ];
            }
        }
    }
    
    ldap_unbind($ldapconn);
    $mysqli->close();
    
    // Trier les élèves par nom d'utilisateur
    usort($students, function($a, $b) {
        return strcmp($a['username'], $b['username']);
    });
    
    error_log("✅ Nombre final d'élèves trouvés: " . count($students));
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'debug' => [
            'classe' => $classe,
            'nb_students_found' => count($students),
            'total_students_in_school' => count($all_students_dns)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("💥 Erreur dans get_students_directrice.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>