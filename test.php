<?php
$ldap_server = "ldap://192.168.20.132"; // ou "ldap://educonnect.mg" si ton DNS le résout bien
$ldap_dn = "EDUCONNECT\\ElimProf";       // Remplace par un compte AD valide
$ldap_password = "12345Orion";           // Mot de passe du compte

$ldapconn = ldap_connect($ldap_server);
if (!$ldapconn) {
    die("❌ Impossible de se connecter au serveur LDAP.");
}

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

if (@ldap_bind($ldapconn, $ldap_dn, $ldap_password)) {
    echo "✅ Connexion et liaison LDAP réussies.";
} else {
    echo "❌ Erreur de liaison LDAP. Vérifie le nom d'utilisateur ou le mot de passe.";
}
ldap_unbind($ldapconn);
?>
