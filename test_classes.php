<?php
session_start();

// Simuler une session de directrice pour le test
$_SESSION['username'] = 'directrice';
$_SESSION['groups'] = ['G_Admin_Direction'];

echo "<h2>Test de connexion LDAP et récupération des classes</h2>";

// Connexion LDAP
$ldapconn = ldap_connect("ldap://192.168.20.132");
if (!$ldapconn) {
    die("❌ Connexion LDAP impossible");
}

echo "✅ Connexion LDAP établie<br>";

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\ElimProf", "12345Orion");
if (!$ldapbind) {
    die("❌ Authentification LDAP échouée: " . ldap_error($ldapconn));
}

echo "✅ Authentification LDAP réussie<br>";

// Rechercher tous les groupes de classes dans l'AD
$search = ldap_search($ldapconn, "OU=Groupes,DC=educonnect,DC=mg", "(cn=G_L*G*)", ["cn", "member"]);
if (!$search) {
    die("❌ Erreur de recherche LDAP: " . ldap_error($ldapconn));
}

$entries = ldap_get_entries($ldapconn, $search);
echo "✅ Recherche LDAP réussie. Nombre de groupes trouvés: " . $entries["count"] . "<br><br>";

echo "<h3>Groupes trouvés:</h3>";
for ($i = 0; $i < $entries["count"]; $i++) {
    $groupName = $entries[$i]["cn"][0];
    echo "- " . $groupName . "<br>";
    
    // Extraire le nom de la classe (ex: G_L1G1 -> L1G1)
    if (preg_match('/^G_(L[1-2]G[1-2])$/', $groupName, $matches)) {
        $className = $matches[1];
        echo "  → Classe détectée: " . $className . "<br>";
        
        // Compter les membres du groupe
        $memberCount = 0;
        if (isset($entries[$i]["member"])) {
            $memberCount = $entries[$i]["member"]["count"];
        }
        echo "  → Nombre de membres: " . $memberCount . "<br>";
    }
    echo "<br>";
}

ldap_unbind($ldapconn);
?>