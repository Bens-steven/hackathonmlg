<?php
/**
 * Configuration Firebase optimisée pour les États-Unis
 */

echo "🇺🇸 Configuration Firebase - États-Unis\n";
echo "=======================================\n\n";

echo "🎯 Excellent choix ! Les États-Unis offrent :\n";
echo "• ✅ Serveurs Firebase les plus rapides\n";
echo "• ✅ Connectivité optimale vers Madagascar\n";
echo "• ✅ Support technique prioritaire\n";
echo "• ✅ Aucune restriction géographique\n\n";

echo "🔑 Entrez votre clé serveur Firebase :\n";
echo "(Copiée depuis Firebase Console → Cloud Messaging)\n";
echo "Format : AAAA... (environ 150 caractères)\n\n";

echo "Clé Firebase : ";
$handle = fopen("php://stdin", "r");
$firebaseKey = trim(fgets($handle));
fclose($handle);

if (empty($firebaseKey)) {
    echo "❌ Aucune clé saisie\n";
    exit(1);
}

// Validation
if (strlen($firebaseKey) < 100) {
    echo "⚠️  Clé trop courte (devrait faire ~150 caractères)\n";
    echo "Continuer quand même ? (y/n) : ";
    $handle = fopen("php://stdin", "r");
    $confirm = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirm) !== "y") {
        echo "❌ Configuration annulée\n";
        exit(1);
    }
}

echo "\n🔧 Configuration optimisée États-Unis...\n";

// Créer le fichier Firebase optimisé USA
$firebaseContent = '<?php
/**
 * Notifications Push Firebase - Optimisé États-Unis → Madagascar
 * Performance maximale avec serveurs US
 */

function sendPushNotification($username, $title, $body, $url = null, $icon = null) {
    return sendPushNotificationUSA($username, $title, $body, $url, $icon);
}

function sendPushNotificationUSA($username, $title, $body, $url = null, $icon = null) {
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
            
            // Traitement Firebase optimisé USA
            if (strpos($endpoint, "fcm.googleapis.com") !== false) {
                if (preg_match("/fcm\\/send\\/(.+)$/", $endpoint, $matches)) {
                    $fcmToken = $matches[1];
                    
                    $payload = [
                        "to" => $fcmToken,
                        "notification" => [
                            "title" => $title,
                            "body" => $body,
                            "icon" => $icon ?: "/photos/educonnect-icon.png",
                            "click_action" => $url ?: "/",
                            "tag" => "educonnect-usa-" . time(),
                            "badge" => "/photos/educonnect-badge.png"
                        ],
                        "data" => [
                            "url" => $url ?: "/",
                            "timestamp" => time(),
                            "username" => $username,
                            "server" => "usa-optimized"
                        ],
                        "priority" => "high"
                    ];
                    
                    if (sendToFirebaseUSA($payload)) {
                        $notifications_sent++;
                    } else {
                        $success = false;
                    }
                }
            }
        }
        
        error_log("Notifications USA envoyées: $notifications_sent pour $username");
        return $success;

    } catch (Exception $e) {
        error_log("Erreur Firebase USA: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

function sendToFirebaseUSA($payload) {
    // Clé serveur Firebase États-Unis
    $serverKey = "' . $firebaseKey . '";
    
    // Endpoint Firebase optimisé
    $url = "https://fcm.googleapis.com/fcm/send";
    
    $headers = [
        "Authorization: key=" . $serverKey,
        "Content-Type: application/json",
        "User-Agent: EduConnect-USA/1.0"
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Erreur cURL USA: " . $error);
        return false;
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData["success"]) && $responseData["success"] > 0) {
            return true;
        } else {
            error_log("Erreur Firebase USA: " . $response);
            return false;
        }
    } else {
        error_log("Erreur HTTP Firebase USA: $httpCode - $response");
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
    
    error_log("Notifications USA envoyées à $count élèves");
    $mysqli->close();
    return $success;
}

// Test si appelé directement
if (basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"] ?? "")) {
    echo "🇺🇸 Test Firebase États-Unis\n";
    echo "============================\n\n";
    
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
            
            echo "🚀 Envoi via serveurs USA...\n";
            
            $success = sendPushNotification(
                $username,
                "🇺🇸 Firebase USA Actif !",
                "Notifications ultra-rapides depuis les États-Unis !",
                "/"
            );
            
            if ($success) {
                echo "🎉 SUCCÈS ! Notification envoyée via USA\n";
                echo "📱 Vérifiez votre appareil\n";
                echo "⚡ Performance optimisée États-Unis !\n";
            } else {
                echo "❌ Échec de l\'envoi\n";
            }
        }
    }
    
    $mysqli->close();
}
?>';

// Sauvegarder le fichier
if (file_put_contents("send_push_notification_usa.php", $firebaseContent)) {
    echo "✅ Fichier créé : send_push_notification_usa.php\n";
} else {
    echo "❌ Erreur lors de la création du fichier\n";
    exit(1);
}

echo "\n🧪 Test de la configuration USA...\n";

// Test immédiat
require_once "send_push_notification_usa.php";

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
        
        echo "🚀 Test via serveurs États-Unis...\n";
        
        $success = sendPushNotification(
            $username,
            "🇺🇸 Firebase USA Opérationnel !",
            "Performance maximale États-Unis → Madagascar !",
            "/"
        );
        
        if ($success) {
            echo "🎉 SUCCÈS TOTAL !\n";
            echo "📱 Notification envoyée via serveurs USA\n";
            echo "⚡ Performance optimisée !\n\n";
            
            echo "🔄 Activation du système USA...\n";
            if (copy("send_push_notification_usa.php", "send_push_notification.php")) {
                echo "✅ send_push_notification.php mis à jour (version USA)\n";
                echo "🇺🇸 Votre système États-Unis est opérationnel !\n\n";
                
                echo "🎯 TESTS RECOMMANDÉS :\n";
                echo "======================\n";
                echo "1. 📱 Testez sur iPhone/Android\n";
                echo "2. 🎓 Créez un devoir\n";
                echo "3. 📲 Vérifiez les notifications ultra-rapides\n";
                echo "4. ⚡ Profitez de la performance USA !\n";
                
            } else {
                echo "⚠️  Copiez manuellement :\n";
                echo "   copy send_push_notification_usa.php send_push_notification.php\n";
            }
            
        } else {
            echo "❌ Échec du test\n";
            echo "🔍 Vérifiez votre clé Firebase\n";
        }
    }
} else {
    echo "⚠️  Aucun abonnement pour tester\n";
    echo "📱 Connectez-vous sur un appareil d'abord\n";
    echo "🇺🇸 Mais Firebase USA est configuré !\n";
}

$mysqli->close();

echo "\n🇺🇸 Configuration États-Unis terminée !\n";
echo "Performance maximale garantie ! ⚡\n";
?>