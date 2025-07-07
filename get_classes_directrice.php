<?php
session_start();

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username']) || !in_array('G_Admin_Direction', $_SESSION['groups'] ?? [])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Log de débogage
error_log("🔍 Début de get_classes_directrice.php");

// Connexion LDAP
$ldapconn = ldap_connect("ldap://192.168.20.132");
if (!$ldapconn) {
    error_log("❌ Connexion LDAP impossible");
    echo json_encode(['success' => false, 'message' => 'Connexion LDAP impossible']);
    exit;
}

error_log("✅ Connexion LDAP établie");

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");
if (!$ldapbind) {
    error_log("❌ Authentification LDAP échouée: " . ldap_error($ldapconn));
    echo json_encode(['success' => false, 'message' => 'Authentification LDAP échouée: ' . ldap_error($ldapconn)]);
    exit;
}

error_log("✅ Authentification LDAP réussie");

// Connexion à MySQL pour les statistiques
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    error_log("❌ Erreur de connexion MySQL: " . $mysqli->connect_error);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $mysqli->connect_error]);
    exit;
}

error_log("✅ Connexion MySQL établie");

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
    
    // ÉTAPE 2: Rechercher tous les groupes de classes dans l'AD
    $search = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_L*G*)", ["cn", "member"]);
    if (!$search) {
        throw new Exception('Erreur de recherche LDAP: ' . ldap_error($ldapconn));
    }
    
    $entries = ldap_get_entries($ldapconn, $search);
    error_log("🔍 Nombre de groupes trouvés: " . $entries["count"]);
    
    $classes = [];
    
    for ($i = 0; $i < $entries["count"]; $i++) {
        $groupName = $entries[$i]["cn"][0];
        error_log("🔍 Examen du groupe: " . $groupName);
        
        // Extraire le nom de la classe (ex: G_L1G1 -> L1G1)
        if (preg_match('/^G_(L[1-2]G[1-2])$/', $groupName, $matches)) {
            $className = $matches[1];
            error_log("✅ Classe détectée: " . $className);
            
            // ÉTAPE 3: Compter SEULEMENT les membres qui sont AUSSI dans G_Tous_Eleves
            $realStudentCount = 0;
            if (isset($entries[$i]["member"])) {
                for ($j = 0; $j < $entries[$i]["member"]["count"]; $j++) {
                    $memberDN = $entries[$i]["member"][$j];
                    
                    // Vérifier si ce membre fait AUSSI partie du groupe G_Tous_Eleves
                    if (in_array($memberDN, $all_students_dns)) {
                        $realStudentCount++;
                        error_log("✅ Élève validé dans $className: " . $memberDN);
                    } else {
                        error_log("❌ Membre ignoré dans $className (pas dans G_Tous_Eleves): " . $memberDN);
                    }
                }
            }
            
            error_log("👥 Nombre RÉEL d'élèves dans $className: " . $realStudentCount);
            
            // Récupérer la moyenne de la classe depuis MySQL (si elle existe)
            $stmt = $mysqli->prepare("SELECT ROUND(AVG(note), 1) as moyenne FROM notes WHERE classe = ?");
            if (!$stmt) {
                error_log("❌ Erreur préparation requête pour $className: " . $mysqli->error);
                $moyenne = 0;
            } else {
                $stmt->bind_param("s", $className);
                $stmt->execute();
                $result = $stmt->get_result();
                $moyenneData = $result->fetch_assoc();
                $moyenne = $moyenneData['moyenne'] ?? 0;
                $stmt->close();
                error_log("📊 Moyenne pour $className: " . $moyenne);
            }
            
            $classes[] = [
                'nom' => $className,
                'nb_eleves' => $realStudentCount, // Maintenant on compte seulement les vrais élèves
                'moyenne' => $moyenne
            ];
        } else {
            error_log("❌ Groupe ignoré (ne correspond pas au pattern): " . $groupName);
        }
    }
    
    ldap_unbind($ldapconn);
    $mysqli->close();
    
    // Trier les classes par nom
    usort($classes, function($a, $b) {
        return strcmp($a['nom'], $b['nom']);
    });
    
    error_log("✅ Nombre final de classes: " . count($classes));
    
    echo json_encode([
        'success' => true,
        'classes' => $classes,
        'debug' => [
            'total_groups_found' => $entries["count"],
            'valid_classes' => count($classes),
            'total_students_in_school' => count($all_students_dns)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("💥 Erreur dans get_classes_directrice.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>