<?php
// Version simplifiée des notifications push sans Composer
function sendSimplePushNotification($username, $title, $body, $url = null) {
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

        $success = true;
        
        // Pour chaque abonnement de l'utilisateur
        while ($row = $result->fetch_assoc()) {
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => '/photos/educonnect-icon.png',
                'badge' => '/photos/educonnect-badge.png',
                'url' => $url ?: '/',
                'tag' => 'educonnect-' . time()
            ]);
            
            // Utiliser cURL pour envoyer la notification
            $sent = sendPushWithCurl($row['endpoint'], $payload, $row['p256dh_key'], $row['auth_key']);
            
            if (!$sent) {
                $success = false;
                error_log("Erreur envoi push pour: " . $username);
            }
        }

        return $success;

    } catch (Exception $e) {
        error_log("Erreur push simple: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

function sendPushWithCurl($endpoint, $payload, $p256dh, $auth) {
    // Configuration basique pour les notifications push
    $headers = [
        'Content-Type: application/json',
        'TTL: 86400', // 24 heures
    ];
    
    // Extraire le domaine de l'endpoint pour déterminer le service
    $parsedUrl = parse_url($endpoint);
    $domain = $parsedUrl['host'];
    
    // Configuration spécifique selon le service
    if (strpos($domain, 'fcm.googleapis.com') !== false) {
        // Firebase Cloud Messaging (Chrome, Edge)
        $headers[] = 'Authorization: key=YOUR_FCM_SERVER_KEY'; // À remplacer
    } elseif (strpos($domain, 'updates.push.services.mozilla.com') !== false) {
        // Mozilla Push Service (Firefox)
        // Pas d'autorisation spéciale nécessaire
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Considérer comme succès si le code HTTP est 200-299
    return $httpCode >= 200 && $httpCode < 300;
}

// Fonction pour envoyer à tous les élèves (version simple)
function sendSimplePushToAllStudents($title, $body, $url = null) {
    $mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
    if ($mysqli->connect_error) {
        return false;
    }

    try {
        // Récupérer tous les élèves avec abonnements push
        $result = $mysqli->query("
            SELECT DISTINCT username 
            FROM push_subscriptions
        ");

        $success = true;
        $sent_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            if (sendSimplePushNotification($row['username'], $title, $body, $url)) {
                $sent_count++;
            } else {
                $success = false;
            }
        }
        
        error_log("Notifications envoyées à $sent_count utilisateurs");
        return $success;

    } catch (Exception $e) {
        error_log("Erreur push tous élèves simple: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

// Si appelé directement (pour test)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    session_start();
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['username'])) {
        $result = sendSimplePushNotification(
            $data['username'], 
            'Test EduConnect (Simple)', 
            'Notification de test sans Composer !',
            '/eleve.php'
        );
        
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Username manquant']);
    }
}
?>