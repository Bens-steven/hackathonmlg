<?php
// Fonction pour envoyer des notifications push
require_once 'vendor/autoload.php'; // Si vous utilisez Composer pour web-push

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushNotification($username, $title, $body, $url = null, $icon = null) {
    // Connexion à la base de données
    $mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
    if ($mysqli->connect_error) {
        error_log("Erreur DB push: " . $mysqli->connect_error);
        return false;
    }

    try {
        // Récupérer les abonnements de l'utilisateur
        $stmt = $mysqli->prepare("
            SELECT endpoint, p256dh_key, auth_key 
            FROM push_subscriptions 
            WHERE username = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            error_log("Aucun abonnement push pour: " . $username);
            return false;
        }

                                // Configuration VAPID finale - clés spécialement générées pour web-push PHP
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@educonnect.mg',
                'publicKey' => 'BPKBhQmtJtXDUdE2E_5FJvDS4_lfvtKkEXGGGvODiNTjbLdqONiWXvryZvEKgPHoRvdlGZKmYLiuCwQualfTBhs',
                'privateKey' => 'nKhFJjCGbJlvQoU9X8tNcWE7VgOmKpRsLqFdE2vHgTk'
            ]
        ];

        $webPush = new WebPush($auth);

        // Préparer le payload avec les bons chemins d'icônes
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?: '/photos/educonnect-icon.png',
            'badge' => '/photos/educonnect-badge.png',
            'url' => $url ?: '/',
            'tag' => 'educonnect-' . time(),
            'timestamp' => time()
        ]);

        $success = true;
        
        // Envoyer à tous les appareils de l'utilisateur
        while ($row = $result->fetch_assoc()) {
            $subscription = Subscription::create([
                'endpoint' => $row['endpoint'],
                'keys' => [
                    'p256dh' => $row['p256dh_key'],
                    'auth' => $row['auth_key']
                ]
            ]);

            $report = $webPush->sendOneNotification($subscription, $payload);
            
            if (!$report->isSuccess()) {
                error_log("Erreur push pour {$username}: " . $report->getReason());
                $success = false;
            }
        }

        return $success;

    } catch (Exception $e) {
        error_log("Erreur envoi push: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

function sendPushToAllStudents($title, $body, $url = null) {
    // Connexion LDAP
    $ldapconn = ldap_connect("ldap://192.168.20.132");
    if (!$ldapconn) {
        error_log("❌ Connexion LDAP échouée.");
        return false;
    }

    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

    $ldapbind = ldap_bind($ldapconn, "EDUCONNECT\\AndrypL1G2", "Basique12345");
    if (!$ldapbind) {
        error_log("❌ LDAP bind échoué : " . ldap_error($ldapconn));
        return false;
    }

    $success = true;

    // Rechercher les membres du groupe G_Tous_Eleves
    $search = ldap_search($ldapconn, "DC=educonnect,DC=mg", "(cn=G_Tous_Eleves)", ["member"]);
    $entries = ldap_get_entries($ldapconn, $search);

    if ($entries["count"] > 0 && isset($entries[0]["member"])) {
        for ($i = 0; $i < $entries[0]["member"]["count"]; $i++) {
            $dn = $entries[0]["member"][$i];

            // Extraire le nom d'utilisateur depuis le DN
            if (preg_match('/CN=([^,]+)/', $dn, $matches)) {
                $username = strtolower($matches[1]);

                // Envoyer la notification push à cet utilisateur
                if (!sendPushNotification($username, $title, $body, $url)) {
                    $success = false;
                }
            }
        }
    } else {
        error_log("❌ Aucun membre trouvé dans G_Tous_Eleves.");
        $success = false;
    }

    ldap_unbind($ldapconn);
    return $success;
}

// Gestion des appels directs (pour éviter l'erreur REQUEST_METHOD)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Code pour gérer les requêtes POST si nécessaire
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['username'], $input['title'], $input['body'])) {
        $result = sendPushNotification(
            $input['username'],
            $input['title'],
            $input['body'],
            $input['url'] ?? null,
            $input['icon'] ?? null
        );
        
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    }
    exit;
}
?>