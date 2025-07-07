<?php
/**
 * Notifications Push Firebase - EduConnect
 * Configuré avec votre clé Firebase États-Unis
 */

function sendPushNotification($username, $title, $body, $url = null, $icon = null) {
    return sendPushNotificationFirebase($username, $title, $body, $url, $icon);
}

function sendPushNotificationFirebase($username, $title, $body, $url = null, $icon = null) {
    // Connexion DB
    $mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
    if ($mysqli->connect_error) {
        error_log("Erreur DB: " . $mysqli->connect_error);
        return false;
    }

    try {
        // Récupérer les abonnements
        $stmt = $mysqli->prepare("SELECT endpoint, p256dh_key, auth_key FROM push_subscriptions WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            error_log("Aucun abonnement pour: " . $username);
            return false;
        }

        $success = true;
        $notifications_sent = 0;
        
        while ($row = $result->fetch_assoc()) {
            $endpoint = $row["endpoint"];
            
            // Traitement Firebase
            if (strpos($endpoint, "fcm.googleapis.com") !== false) {
                if (preg_match("/fcm\/send\/(.+)$/", $endpoint, $matches)) {
                    $fcmToken = $matches[1];
                    
                    $payload = [
                        "to" => $fcmToken,
                        "notification" => [
                            "title" => $title,
                            "body" => $body,
                            "icon" => $icon ?: "/photos/educonnect-icon.png",
                            "click_action" => $url ?: "/",
                            "tag" => "educonnect-" . time(),
                            "badge" => "/photos/educonnect-badge.png"
                        ],
                        "data" => [
                            "url" => $url ?: "/",
                            "timestamp" => time(),
                            "username" => $username
                        ],
                        "priority" => "high"
                    ];
                    
                    if (sendToFirebase($payload)) {
                        $notifications_sent++;
                    } else {
                        $success = false;
                    }
                }
            }
        }
        
        error_log("Notifications envoyées: $notifications_sent pour $username");
        return $success;

    } catch (Exception $e) {
        error_log("Erreur Firebase: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

function sendToFirebase($payload) {
    // VOTRE clé serveur Firebase (États-Unis)
    $serverKey = "BNt8Mn96OQVUDXaSqKyP-uIF92DQMQ-VFa9ls6Rsc-IEKEcpC3H-xs52gQAK04h1IMwD42LDPLV18K0Tbe2hczo";
    
    $url = "https://fcm.googleapis.com/fcm/send";
    
    $headers = [
        "Authorization: key=" . $serverKey,
        "Content-Type: application/json"
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Erreur cURL: " . $error);
        return false;
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData["success"]) && $responseData["success"] > 0) {
            return true;
        } else {
            error_log("Erreur Firebase: " . $response);
            return false;
        }
    } else {
        error_log("Erreur HTTP Firebase: $httpCode - $response");
        return false;
    }
}

// Fonction pour envoyer à tous les élèves
function sendPushToAllStudents($title, $body, $url = null) {
    $mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
    if ($mysqli->connect_error) {
        return false;
    }
    
    $result = $mysqli->query("SELECT DISTINCT username FROM push_subscriptions");
    $success = true;
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        if (sendPushNotification($row["username"], $title, $body, $url)) {
            $count++;
        } else {
            $success = false;
        }
    }
    
    error_log("Notifications envoyées à $count élèves");
    $mysqli->close();
    return $success;
}

// Test si appelé directement
if (basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"] ?? "")) {
    echo "🧪 Test avec Votre Clé Firebase\n";
    echo "===============================\n\n";
    
    $mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
    if ($mysqli->connect_error) {
        echo "❌ Erreur DB: " . $mysqli->connect_error . "\n";
        exit(1);
    }
    
    echo "✅ Connexion DB OK\n";
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM push_subscriptions");
    $row = $result->fetch_assoc();
    echo "📊 Total abonnements: " . $row["count"] . "\n";
    
    if ($row["count"] > 0) {
        $result = $mysqli->query("SELECT DISTINCT username FROM push_subscriptions LIMIT 1");
        if ($user = $result->fetch_assoc()) {
            $username = $user["username"];
            echo "👤 Test avec: $username\n";
            
            echo "🚀 Test avec votre clé Firebase...\n";
            
            $success = sendPushNotification(
                $username,
                "🎉 Votre Firebase Fonctionne !",
                "Configuration réussie avec votre clé États-Unis !",
                "/"
            );
            
            if ($success) {
                echo "🎉 SUCCÈS ! Votre clé fonctionne parfaitement\n";
                echo "📱 Vérifiez votre appareil\n";
            } else {
                echo "❌ Échec - vérifiez la configuration\n";
            }
        }
    }
    
    $mysqli->close();
}
?>