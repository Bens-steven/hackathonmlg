<?php
session_start();
header('Content-Type: application/json');

// Vérification session
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

if (!isset($_GET['homework_id']) || !isset($_GET['classe'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$homework_id = intval($_GET['homework_id']);
$classe = $_GET['classe'];

// Connexion à MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur MySQL']);
    exit;
}

// Récupération de la date limite du devoir
$stmt = $mysqli->prepare("SELECT date_limite FROM devoirs WHERE id = ?");
$stmt->bind_param("i", $homework_id);
$stmt->execute();
$result = $stmt->get_result();
$devoir = $result->fetch_assoc();
$stmt->close();

if (!$devoir) {
    echo json_encode(['success' => false, 'error' => 'Devoir introuvable']);
    exit;
}

// Connexion à Active Directory
$ldapconn = ldap_connect("ldap://192.168.20.132");
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");
if (!$ldapbind) {
    echo json_encode(['success' => false, 'error' => 'Connexion LDAP échouée']);
    exit;
}

// Recherche uniquement les élèves dans le groupe G_Tous_Eleves
$group_cn = "G_Tous_Eleves";  // Groupe des élèves
$group_dn = "CN=$group_cn,OU=Groupes,DC=educonnect,DC=mg";

$search = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(memberOf=$group_dn)", ["sAMAccountName", "memberof"]);
$entries = ldap_get_entries($ldapconn, $search);

$submissions = [];

for ($i = 0; $i < $entries["count"]; $i++) {
    $username = $entries[$i]["samaccountname"][0];

    // Vérifier si l'utilisateur appartient à la classe donnée
    $userGroups = $entries[$i]["memberof"];  // Liste des groupes auxquels appartient l'utilisateur
    $userClass = null;

    foreach ($userGroups as $group) {
        // Vérifier si le groupe de l'utilisateur correspond à la classe demandée (par exemple G_L1G1)
        if (preg_match("/CN=G_(L[1-2]G[1-2])/", $group, $matches)) {
            $userClass = $matches[1];
            break;
        }
    }

    // Si l'élève appartient à la classe demandée
    if ($userClass === $classe) {
        // Vérifier si cet utilisateur a rendu ce devoir
        $stmt = $mysqli->prepare("SELECT fichier_rendu FROM rendus_devoirs WHERE devoir_id = ? AND eleve_username = ?");
        $stmt->bind_param("is", $homework_id, $username);
        $stmt->execute();
        $rendu_result = $stmt->get_result();
        $rendu = $rendu_result->fetch_assoc();
        $stmt->close();

        // Ajouter l'élève et son état de rendu
        $submissions[] = [
            'eleve_username' => $username,
            'rendu' => $rendu ? true : false,
            'fichier_rendu' => $rendu ? $rendu['fichier_rendu'] : null
        ];
    }
}

// Fermer les connexions
ldap_unbind($ldapconn);
$mysqli->close();

// Retourner les données JSON
echo json_encode([
    'success' => true,
    'submissions' => $submissions,
    'homework' => [
        'date_limite' => $devoir['date_limite']
    ]
]);
?>
