<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Connexion à la base de données MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Vérification que les données sont présentes dans le formulaire
if (!isset($_POST['eleve'], $_POST['type'], $_POST['date'])) {
    $_SESSION['message'] = "Données manquantes dans le formulaire.";
    header("Location: admin.php");
    exit();
}

// Récupérer les données du formulaire
$eleve_username = $_POST['eleve'];
$type = $_POST['type'];
$date = $_POST['date'];
$heure = $_POST['heure'] ?? null; // L'heure est optionnelle
$motif = $_POST['motif'] ?? ''; // Le motif est optionnel

// Connexion LDAP à l'adresse IP directe
$ldapconn = ldap_connect("ldap://192.168.20.132");

if (!$ldapconn) {
    die("❌ Impossible de se connecter au serveur LDAP.");
}

// Configurations LDAP
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

// Test de la connexion avec les identifiants
$ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");

if (!$ldapbind) {
    // Affiche plus d'informations sur l'erreur
    die("❌ Erreur de liaison LDAP. Erreur : " . ldap_error($ldapconn));
}

// Recherche de l'utilisateur dans l'AD
$search = ldap_search($ldapconn, "dc=educonnect,dc=mg", "(sAMAccountName=$eleve_username)", ["memberOf"]);
if (!$search) {
    die("❌ Erreur lors de la recherche LDAP : " . ldap_error($ldapconn));
}

$entries = ldap_get_entries($ldapconn, $search);

// Vérification du nombre d'entrées trouvées
if ($entries["count"] <= 0) {
    $_SESSION['message'] = "L'élève n'a pas été trouvé dans l'Active Directory.";
    header("Location: admin.php");
    exit();
}

// Détermination de la classe via les groupes AD
$classe = "Inconnue"; // Valeur par défaut
foreach ($entries[0]["memberof"] as $group) {
    if (preg_match("/CN=G_(L[1-2]G[1-2])/", $group, $matches)) {
        $classe = $matches[1]; // Ex: L1G1
        break;
    }
}

// Insertion de l'absence/retard dans la base de données
$query = "INSERT INTO absence_retard (eleve, date, heure, type, motif, classe) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ssssss", $eleve_username, $date, $heure, $type, $motif, $classe);

if ($stmt->execute()) {
    $_SESSION['message'] = "✅ $type enregistré pour l'élève : $eleve_username (Classe : $classe).";
} else {
    $_SESSION['message'] = "❌ Échec de l'enregistrement.";
}

// Fermer les connexions
$stmt->close();
$mysqli->close();
ldap_unbind($ldapconn);

// Retour à la page admin
header("Location: admin.php");
exit();
?>
