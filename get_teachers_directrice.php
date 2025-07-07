<?php
session_start();

// Vérification si l'utilisateur est connecté et fait partie du groupe G_Admin_Direction
if (!isset($_SESSION['username']) || !in_array('G_Admin_Direction', $_SESSION['groups'] ?? [])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Log de débogage
error_log("🔍 Début de get_teachers_directrice.php");

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
    // ÉTAPE 1: Récupérer tous les membres du groupe G_Tous_Professeurs
    $search_all_teachers = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_Tous_Professeurs)", ["member"]);
    if (!$search_all_teachers) {
        throw new Exception('Erreur lors de la recherche du groupe G_Tous_Professeurs: ' . ldap_error($ldapconn));
    }
    
    $all_teachers_entries = ldap_get_entries($ldapconn, $search_all_teachers);
    
    $all_teachers_dns = [];
    if ($all_teachers_entries["count"] > 0 && isset($all_teachers_entries[0]["member"])) {
        for ($i = 0; $i < $all_teachers_entries[0]["member"]["count"]; $i++) {
            $all_teachers_dns[] = $all_teachers_entries[0]["member"][$i];
        }
    }
    
    error_log("🔍 Nombre total de professeurs dans G_Tous_Professeurs: " . count($all_teachers_dns));
    
    // ÉTAPE 2: Rechercher tous les groupes de matières dans l'AD (G_Mathematiques, G_Francais, etc.)
    $search_subjects = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_*)", ["cn", "member"]);
    if (!$search_subjects) {
        throw new Exception('Erreur de recherche des groupes de matières: ' . ldap_error($ldapconn));
    }
    
    $subjects_entries = ldap_get_entries($ldapconn, $search_subjects);
    error_log("🔍 Nombre de groupes trouvés: " . $subjects_entries["count"]);
    
    $teachers_by_subject = [];
    
    // Liste des matières connues (sans accents dans les groupes)
    $known_subjects = [
        'G_Mathematique' => 'Mathématiques',
        'G_Francais' => 'Français', 
        'G_Histoire' => 'Histoire',
        'G_Physique' => 'Physique',
        'G_Chimie' => 'Chimie',
        'G_Biologie' => 'Biologie',
        'G_Anglais' => 'Anglais',
        'G_Sport' => 'Sport',
        'G_EPS' => 'EPS',
        'G_Geographie' => 'Géographie',
        'G_Philosophie' => 'Philosophie',
        'G_Economie' => 'Économie'
    ];
    
    for ($i = 0; $i < $subjects_entries["count"]; $i++) {
        $groupName = $subjects_entries[$i]["cn"][0];
        
        // Vérifier si c'est un groupe de matière connu
        if (array_key_exists($groupName, $known_subjects)) {
            $subjectName = $known_subjects[$groupName];
            error_log("✅ Matière détectée: " . $subjectName . " (groupe: " . $groupName . ")");
            
            $teachers_by_subject[$subjectName] = [];
            
            // ÉTAPE 3: Pour chaque groupe de matière, trouver les professeurs
            if (isset($subjects_entries[$i]["member"])) {
                for ($j = 0; $j < $subjects_entries[$i]["member"]["count"]; $j++) {
                    $memberDN = $subjects_entries[$i]["member"][$j];
                    
                    // Vérifier si ce membre fait AUSSI partie du groupe G_Tous_Professeurs
                    if (in_array($memberDN, $all_teachers_dns)) {
                        error_log("✅ Professeur validé pour $subjectName: " . $memberDN);
                        
                        // Extraire le nom d'utilisateur du DN
                        if (preg_match('/CN=([^,]+)/', $memberDN, $matches)) {
                            $username = $matches[1];
                            
                            // Rechercher les détails de l'utilisateur dans l'AD
                            $userSearch = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(cn=$username)", ["sAMAccountName", "cn", "memberOf"]);
                            if (!$userSearch) {
                                error_log("⚠️ Impossible de rechercher les détails pour: " . $username);
                                continue;
                            }
                            
                            $userEntries = ldap_get_entries($ldapconn, $userSearch);
                            
                            $realUsername = $username; // Par défaut
                            $displayName = $username;
                            $userClasses = [];
                            
                            if ($userEntries["count"] > 0) {
                                if (isset($userEntries[0]["samaccountname"][0])) {
                                    $realUsername = $userEntries[0]["samaccountname"][0];
                                }
                                if (isset($userEntries[0]["cn"][0])) {
                                    $displayName = $userEntries[0]["cn"][0];
                                }
                                
                                // Récupérer les classes auxquelles le professeur enseigne
                                if (isset($userEntries[0]["memberof"])) {
                                    for ($k = 0; $k < $userEntries[0]["memberof"]["count"]; $k++) {
                                        $group = $userEntries[0]["memberof"][$k];
                                        if (preg_match('/CN=G_(L[1-2]G[1-2])/', $group, $classMatches)) {
                                            $userClasses[] = $classMatches[1];
                                        }
                                    }
                                }
                            }
                            
                            error_log("👨‍🏫 Professeur: $realUsername, Classes: " . implode(', ', $userClasses));
                            
                            // Calculer les moyennes par classe pour ce professeur dans sa matière
                            $class_averages = [];
                            foreach ($userClasses as $classe) {
                                $stmt = $mysqli->prepare("SELECT ROUND(AVG(note), 1) as moyenne FROM notes WHERE classe = ? AND matiere = ?");
                                if ($stmt) {
                                    $stmt->bind_param("ss", $classe, $subjectName);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $moyenneData = $result->fetch_assoc();
                                    $moyenne = $moyenneData['moyenne'] ?? 0;
                                    $stmt->close();
                                    
                                    if ($moyenne > 0) {
                                        $class_averages[] = [
                                            'classe' => $classe,
                                            'moyenne' => $moyenne
                                        ];
                                    }
                                }
                            }
                            
                            $teachers_by_subject[$subjectName][] = [
                                'username' => $realUsername,
                                'display_name' => $displayName,
                                'classes' => $userClasses,
                                'class_averages' => $class_averages,
                                'total_classes' => count($userClasses)
                            ];
                        }
                    } else {
                        error_log("❌ Membre ignoré pour $subjectName (pas dans G_Tous_Professeurs): " . $memberDN);
                    }
                }
            }
            
            error_log("👥 Nombre de professeurs pour $subjectName: " . count($teachers_by_subject[$subjectName]));
        }
    }
    
    ldap_unbind($ldapconn);
    $mysqli->close();
    
    // Supprimer les matières sans professeurs
    $teachers_by_subject = array_filter($teachers_by_subject, function($teachers) {
        return !empty($teachers);
    });
    
    // Trier les matières par nom
    ksort($teachers_by_subject);
    
    error_log("✅ Nombre final de matières avec professeurs: " . count($teachers_by_subject));
    
    echo json_encode([
        'success' => true,
        'teachers_by_subject' => $teachers_by_subject,
        'debug' => [
            'total_teachers_in_school' => count($all_teachers_dns),
            'subjects_with_teachers' => count($teachers_by_subject)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("💥 Erreur dans get_teachers_directrice.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>