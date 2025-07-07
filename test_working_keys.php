<?php
// Test robuste avec les clés qui fonctionnent

echo "🧪 Test Robuste - Clés Pré-testées\n";
echo "==================================\n\n";

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Test de base des clés
$publicKey = "BKd3dGdX-W_FdAm-ce-9HlqhkTBD0bLuTw7ZFPGGHmaG7Vv9uNX6kdFLIGdjMVCbRqcQHuJOwfFdMQPEM6SPBhg";
$privateKey = "Oy7XkafkOAVKbxJvx-Hs6V4i5ePHqO3QjqOcYvOz_Rk";

echo "🔍 Validation des clés...\n";

function base64url_decode($data) {
    return base64_decode(strtr($data, "-_", "+/") . str_repeat("=", 3 - (3 + strlen($data)) % 4));
}

$pubDecoded = base64url_decode($publicKey);
$privDecoded = base64url_decode($privateKey);

echo "📏 Clé publique: " . strlen($publicKey) . " chars -> " . strlen($pubDecoded) . " bytes\n";
echo "📏 Clé privée: " . strlen($privateKey) . " chars -> " . strlen($privDecoded) . " bytes\n";

$keysValid = true;

if (strlen($pubDecoded) === 65 && ord($pubDecoded[0]) === 0x04) {
    echo "✅ Clé publique valide\n";
} else {
    echo "❌ Clé publique invalide\n";
    $keysValid = false;
}

if (strlen($privDecoded) === 32) {
    echo "✅ Clé privée valide\n";
} else {
    echo "❌ Clé privée invalide\n";
    $keysValid = false;
}

if (!$keysValid) {
    echo "❌ Clés invalides, arrêt du test\n";
    exit(1);
}

echo "\n🔌 Test de connexion DB...\n";

// Test de connexion DB
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur DB: " . $mysqli->connect_error . "\n";
    exit(1);
}
echo "✅ Connexion DB OK\n";

// Compter les abonnements
$result = $mysqli->query("SELECT COUNT(*) as count FROM push_subscriptions");
if (!$result) {
    echo "❌ Erreur requête: " . $mysqli->error . "\n";
    exit(1);
}

$row = $result->fetch_assoc();
echo "📊 Total abonnements: " . $row["count"] . "\n";

if ($row["count"] > 0) {
    echo "\n📤 Test de notification...\n";
    
    // Vérifier que le fichier existe
    if (!file_exists("send_push_notification.php")) {
        echo "❌ Fichier send_push_notification.php non trouvé\n";
        exit(1);
    }
    
    // Inclure le fichier de notification
    require_once "send_push_notification.php";
    
    // Vérifier que la fonction existe
    if (!function_exists("sendPushNotification")) {
        echo "❌ Fonction sendPushNotification non trouvée\n";
        exit(1);
    }
    
    // Prendre le premier utilisateur
    $result = $mysqli->query("SELECT DISTINCT username FROM push_subscriptions LIMIT 1");
    if (!$result) {
        echo "❌ Erreur requête utilisateurs: " . $mysqli->error . "\n";
        exit(1);
    }
    
    if ($user = $result->fetch_assoc()) {
        $username = $user["username"];
        echo "👤 Test avec utilisateur: $username\n";
        
        echo "🚀 Envoi de la notification de test...\n";
        
        try {
            $success = sendPushNotification(
                $username,
                "🎉 Test Réussi !",
                "Les clés VAPID pré-testées fonctionnent !",
                "/",
                "/photos/educonnect-icon.png"
            );
            
            if ($success) {
                echo "🎉 SUCCÈS ! Notification envoyée sans erreur\n";
                echo "📱 Vérifiez votre appareil pour la notification\n";
            } else {
                echo "⚠️  La fonction retourne false, mais pas d'erreur fatale\n";
                echo "📋 Vérifiez les logs d'erreur PHP\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Exception capturée: " . $e->getMessage() . "\n";
            echo "📋 Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
        } catch (Error $e) {
            echo "❌ Erreur fatale capturée: " . $e->getMessage() . "\n";
            echo "📋 Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
        }
        
    } else {
        echo "❌ Aucun utilisateur avec abonnement trouvé\n";
    }
} else {
    echo "⚠️  Aucun abonnement push trouvé\n";
    echo "📱 Connectez-vous sur un appareil et autorisez les notifications\n";
}

$mysqli->close();

echo "\n🎯 Test terminé !\n";
echo "================\n";
echo "Si aucune erreur OpenSSL n'est apparue, les clés fonctionnent !\n";
?>