<?php
session_start();

$ldap_host = "192.168.20.132"; // IP de ton serveur AD
$ldap_domain = "Educonnect.mg";

$username = $_POST['username'];
$password = $_POST['password'];
$ldap_user = $username . "@" . $ldap_domain;

// Connexion au serveur LDAP
$ldap_conn = ldap_connect($ldap_host);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

// Vérification de la connexion LDAP
if (!$ldap_conn) {
    $_SESSION['login_error'] = 'ldap_connection';
    $_SESSION['login_error_message'] = 'Connexion au serveur LDAP impossible';
    file_put_contents("debug_ldap_error.txt", "Erreur de connexion LDAP: " . $ldap_host . "\n", FILE_APPEND);
    header("Location: login.php");
    exit;
}

// Connexion avec les identifiants fournis
if (!@ldap_bind($ldap_conn, $ldap_user, $password)) {
    $_SESSION['login_error'] = 'invalid_credentials';
    $_SESSION['login_error_message'] = 'Identifiant ou mot de passe incorrect';
    file_put_contents("debug_ldap_error.txt", "Erreur lors du bind pour $ldap_user avec le mot de passe.\n", FILE_APPEND);
    header("Location: login.php");
    exit;
}

// Recherche de l'utilisateur dans l'AD
$search_base = "DC=Educonnect,DC=mg";
$filter = "(sAMAccountName=$username)";
$result = ldap_search($ldap_conn, $search_base, $filter, ["cn", "memberOf"]);

if (!$result) {
    $_SESSION['login_error'] = 'ldap_search';
    $_SESSION['login_error_message'] = 'Erreur lors de la recherche LDAP';
    file_put_contents("debug_ldap_error.txt", "Erreur de recherche LDAP pour $username.\n", FILE_APPEND);
    header("Location: login.php");
    exit;
}

$entries = ldap_get_entries($ldap_conn, $result);

// Initialisation des données
$groups = [];
$fullname = $username;

if ($entries["count"] > 0) {
    if (isset($entries[0]["cn"][0])) {
        $fullname = $entries[0]["cn"][0];
    }

    if (isset($entries[0]["memberof"])) {
        for ($i = 0; $i < $entries[0]["memberof"]["count"]; $i++) {
            if (preg_match("/CN=([^,]+)/", $entries[0]["memberof"][$i], $matches)) {
                $groups[] = $matches[1];
            }
        }
    }
} else {
    $_SESSION['login_error'] = 'no_user_found';
    $_SESSION['login_error_message'] = 'Aucun utilisateur trouvé dans l\'AD';
    file_put_contents("debug_ldap_error.txt", "Aucun utilisateur trouvé pour $username.\n", FILE_APPEND);
    header("Location: login.php");
    exit;
}

// Stockage en session
$_SESSION['username'] = $username;
$_SESSION['fullname'] = $fullname;
$_SESSION['groups'] = $groups;

// ✅ Déduction de la matière à partir du groupe G_<matière>
foreach ($groups as $group) {
    if (preg_match('/^G_([^_]+)$/', $group, $match) && !in_array($group, ['G_Tous_Professeurs', 'G_Tous_Eleves', 'G_Tous_Personnel_Admin'])) {
        $_SESSION['matiere'] = $match[1];
        break;
    }
}

// 🔍 Enregistrement pour debug si besoin
file_put_contents("debug_auth_session.txt", json_encode($_SESSION, JSON_PRETTY_PRINT));

// Redirection selon le groupe
if (in_array("G_Tous_Personnel_Admin", $groups)) {
    header("Location: admin.php");
} elseif (in_array("G_Tous_Professeurs", $groups)) {
    header("Location: professeur.php");
} elseif (in_array("G_Tous_Eleves", $groups)) {
    header("Location: eleve.php");
} elseif (in_array("G_Tous_Personnel_Admin", $groups)) {
    header("Location: admin.php");
} elseif (in_array("G_Admin_Direction", $groups)) {
    header("Location: Directrice.php");
} else {
    $_SESSION['login_error'] = 'no_group';
    $_SESSION['login_error_message'] = 'Cet utilisateur n\'appartient à aucun groupe disponible';
    file_put_contents("debug_ldap_error.txt", "L'utilisateur $username n'appartient à aucun groupe valide.\n", FILE_APPEND);
    header("Location: login.php");
}

exit;
?>
