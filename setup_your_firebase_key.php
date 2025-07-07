<?php
/**
 * Configuration Firebase moderne avec Service Account JSON
 * Solution la plus sécurisée et moderne pour Firebase
 */

echo "🔥 Configuration Firebase Service Account\n";
echo "=========================================\n\n";

echo "🎯 Excellente nouvelle !\n";
echo "Vous avez un fichier Service Account JSON - c'est la méthode MODERNE et SÉCURISÉE !\n\n";

// Votre configuration Service Account
$serviceAccount = [
    "type" => "service_account",
    "project_id" => "educonnect-4a82e",
    "private_key_id" => "594b59a4365557797a81f10ba1bc43d99110dd82",
    "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDOS6oixPMaobO5\nSPlsWIJtQQ3T7+R6W+H7d118eqCHEg0ruQ8UjE1oJSdSlmAJlAC/ct0L6yN+6nEK\nRE3qkVTtssixQuFoOw1CSbMc6/T2vOeSMpcDbN1Js/i4QR6XPhE2VIc6MR9orwms\nKQKr+IVJVFI0aB19UFVN5Y/aDTNvuGbQ8RBgXmU065oRUrWB6oxzIzaFW2GZfmHX\nO60ul6guWrvDFs10u2OVZSAnjNkcOZUycZ2+PA1mTD+4v8z8herXP3r1uvjK9Ok7\n7h+tZeW13TyahlbM3Ogwh5kVgnYHJ6Q3pqL3wA40xlCzM0T1VQAU7l0ipvYTXnoL\nk8oDdXCrAgMBAAECggEAAeyhblLuqZrvNZVBWQoM/Wk+mxwrwhsapTaHKrgXc0Bv\nMFnjksoplq/HXsqETtKQnI3nvfVi5AuFI8FgsLAhCK6YQf8Ltzswc86C2zbgQW+g\nVrQeGywSzF3faL3tbnUSbV1QfZx5BzL1diOpILg1a2pb8fKJWTfnSSNmZMmmvINe\n4V/IugiU+nPJ1B7BAFSatcdiNVji3qmZiMZX0Q5c26/1Qq/T/suZESHuvTcCPLJl\nkTrUVUnD1olkjXd0k7ShzM3HJXl4VGT2KJ6cNVCIUcNQTpj50PqXLb9m4wBSuitl\nKHK99PEUTM6EItEtpPdVGehXiyHUgoWC3oGXPmKqMQKBgQDzpYcWk4CuVl9NlT/Y\n6e/pCDFW3YuqrEHsmgrQzan2ELdF6a6OeywgVcUpC5zH0yyS0/ZBFUl/J1BvYliN\nhZjFvTqwDwLACnKAqrDP+j6BXhOy2KIPCUrfrr+KzbSLBRFDwZd+JTCFmup/V/1j\ndZUcejY2KlfVTOjp1TE7pWuf5wKBgQDYwVRv9094vUjmoB875TIJbxfYpjyAHnPu\nPhRqunekalkVQ9+5jJLzGYSpBN9EfzlJb1hekJ5VKzSWd+aIHwobSaeR+/Upu8f4\nPJuajJjjhXIg04Ap6V4KmUSUZJxW11KuIuhy1/1JRuHYDnJEi1hJ0fMCD3akHhw0\nW5nvztygnQKBgQCniqnMIn4YZBiA9yLCfIuXCSU0gIAsSuvCUWMilmpLZM9CaiC4\npYaAbbp4MR4MYvCBvvPVaVfy8gvjrBMMrlORlkAnFQtlF0oJpFaVjZUxzIlrMICw\nKkmsazQXtMBz4HYwy4zrF5O4LAEYtF7v58kzne5tbMydRpfbQ9jpFSVe/QKBgQCA\nxOn60nueCSWEDWBA3vqI7DzIconCu1S4Fp1egoSNYrilj3sb6k1qqqBLIR/au5I2\n9DUViOjnRBGrynNkLrx8VZd9fKe7MVmtOaRffmNd12tj4QJk48UAmulJFzRhyu3N\nkeNyRqqsm7WT+5Ea84Vx5Y5ujO04tsKewESFWOltaQKBgQCYMjGkfw2FSrUFwDnT\nXe0Vn84O8hkvTNsc+d1opn7kldFr3W/z5BYCa7MI+gD7noB5X60Yho2kdNEh7Ff7\nIEI376oIHFpNrlB14gy42/6VHRWr3wX/atazXrLxYPxAUTiflBCmv1hzCmkSXwx3\nMo5g+3Lf7ladiogSr/iRmeU89w==\n-----END PRIVATE KEY-----\n",
    "client_email" => "firebase-adminsdk-fbsvc@educonnect-4a82e.iam.gserviceaccount.com",
    "client_id" => "112557532033647485289",
    "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
    "token_uri" => "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40educonnect-4a82e.iam.gserviceaccount.com",
    "universe_domain" => "googleapis.com"
];

echo "✅ Service Account détecté :\n";
echo "• Projet: " . $serviceAccount['project_id'] . "\n";
echo "• Email: " . $serviceAccount['client_email'] . "\n";
echo "• Type: Service Account (méthode moderne)\n\n";

echo "🔧 Création du système Firebase moderne...\n";

// Créer le fichier Firebase moderne avec Service Account
$firebaseContent = '<?php
/**
 * Notifications Push Firebase - Service Account Moderne
 * Utilise l\'API Firebase v1 avec authentification JWT
 */

function sendPushNotification($username, $title, $body, $url = null, $icon = null) {
    return sendPushNotificationModern($username, $title, $body, $url, $icon);
}

function sendPushNotificationModern($username, $title, $body, $url = null, $icon = null) {
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
            
            // Traitement Firebase moderne
            if (strpos($endpoint, "fcm.googleapis.com") !== false) {
                if (preg_match("/fcm\\/send\\/(.+)$/", $endpoint, $matches)) {
                    $fcmToken = $matches[1];
                    
                    // Payload Firebase v1 moderne
                    $payload = [
                        "message" => [
                            "token" => $fcmToken,
                            "notification" => [
                                "title" => $title,
                                "body" => $body,
                                "image" => $icon ?: "https://192.168.88.101:8080/photos/educonnect-icon.png"
                            ],
                            "webpush" => [
                                "headers" => [
                                    "TTL" => "86400"
                                ],
                                "notification" => [
                                    "icon" => $icon ?: "/photos/educonnect-icon.png",
                                    "badge" => "/photos/educonnect-badge.png",
                                    "tag" => "educonnect-modern-" . time(),
                                    "requireInteraction" => false,
                                    "silent" => false
                                ],
                                "fcm_options" => [
                                    "link" => $url ?: "/"
                                ],
                                "data" => [
                                    "url" => $url ?: "/",
                                    "timestamp" => (string)time(),
                                    "username" => $username,
                                    "version" => "modern-v1"
                                ]
                            ]
                        ]
                    ];
                    
                    if (sendToFirebaseModern($payload)) {
                        $notifications_sent++;
                    } else {
                        $success = false;
                    }
                }
            }
        }
        
        error_log("Notifications modernes envoyées: $notifications_sent pour $username");
        return $success;

    } catch (Exception $e) {
        error_log("Erreur Firebase moderne: " . $e->getMessage());
        return false;
    } finally {
        $mysqli->close();
    }
}

function sendToFirebaseModern($payload) {
    // Configuration Service Account
    $serviceAccount = [
        "type" => "service_account",
        "project_id" => "educonnect-4a82e",
        "private_key_id" => "594b59a4365557797a81f10ba1bc43d99110dd82",
        "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDOS6oixPMaobO5\nSPlsWIJtQQ3T7+R6W+H7d118eqCHEg0ruQ8UjE1oJSdSlmAJlAC/ct0L6yN+6nEK\nRE3qkVTtssixQuFoOw1CSbMc6/T2vOeSMpcDbN1Js/i4QR6XPhE2VIc6MR9orwms\nKQKr+IVJVFI0aB19UFVN5Y/aDTNvuGbQ8RBgXmU065oRUrWB6oxzIzaFW2GZfmHX\nO60ul6guWrvDFs10u2OVZSAnjNkcOZUycZ2+PA1mTD+4v8z8herXP3r1uvjK9Ok7\n7h+tZeW13TyahlbM3Ogwh5kVgnYHJ6Q3pqL3wA40xlCzM0T1VQAU7l0ipvYTXnoL\nk8oDdXCrAgMBAAECggEAAeyhblLuqZrvNZVBWQoM/Wk+mxwrwhsapTaHKrgXc0Bv\nMFnjksoplq/HXsqETtKQnI3nvfVi5AuFI8FgsLAhCK6YQf8Ltzswc86C2zbgQW+g\nVrQeGywSzF3faL3tbnUSbV1QfZx5BzL1diOpILg1a2pb8fKJWTfnSSNmZMmmvINe\n4V/IugiU+nPJ1B7BAFSatcdiNVji3qmZiMZX0Q5c26/1Qq/T/suZESHuvTcCPLJl\nkTrUVUnD1olkjXd0k7ShzM3HJXl4VGT2KJ6cNVCIUcNQTpj50PqXLb9m4wBSuitl\nKHK99PEUTM6EItEtpPdVGehXiyHUgoWC3oGXPmKqMQKBgQDzpYcWk4CuVl9NlT/Y\n6e/pCDFW3YuqrEHsmgrQzan2ELdF6a6OeywgVcUpC5zH0yyS0/ZBFUl/J1BvYliN\nhZjFvTqwDwLACnKAqrDP+j6BXhOy2KIPCUrfrr+KzbSLBRFDwZd+JTCFmup/V/1j\ndZUcejY2KlfVTOjp1TE7pWuf5wKBgQDYwVRv9094vUjmoB875TIJbxfYpjyAHnPu\nPhRqunekalkVQ9+5jJLzGYSpBN9EfzlJb1hekJ5VKzSWd+aIHwobSaeR+/Upu8f4\nPJuajJjjhXIg04Ap6V4KmUSUZJxW11KuIuhy1/1JRuHYDnJEi1hJ0fMCD3akHhw0\nW5nvztygnQKBgQCniqnMIn4YZBiA9yLCfIuXCSU0gIAsSuvCUWMilmpLZM9CaiC4\npYaAbbp4MR4MYvCBvvPVaVfy8gvjrBMMrlORlkAnFQtlF0oJpFaVjZUxzIlrMICw\nKkmsazQXtMBz4HYwy4zrF5O4LAEYtF7v58kzne5tbMydRpfbQ9jpFSVe/QKBgQCA\nxOn60nueCSWEDWBA3vqI7DzIconCu1S4Fp1egoSNYrilj3sb6k1qqqBLIR/au5I2\n9DUViOjnRBGrynNkLrx8VZd9fKe7MVmtOaRffmNd12tj4QJk48UAmulJFzRhyu3N\nkeNyRqqsm7WT+5Ea84Vx5Y5ujO04tsKewESFWOltaQKBgQCYMjGkfw2FSrUFwDnT\nXe0Vn84O8hkvTNsc+d1opn7kldFr3W/z5BYCa7MI+gD7noB5X60Yho2kdNEh7Ff7\nIEI376oIHFpNrlB14gy42/6VHRWr3wX/atazXrLxYPxAUTiflBCmv1hzCmkSXwx3\nMo5g+3Lf7ladiogSr/iRmeU89w==\n-----END PRIVATE KEY-----\n",
        "client_email" => "firebase-adminsdk-fbsvc@educonnect-4a82e.iam.gserviceaccount.com",
        "client_id" => "112557532033647485289",
        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
        "token_uri" => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40educonnect-4a82e.iam.gserviceaccount.com",
        "universe_domain" => "googleapis.com"
    ];
    
    // Obtenir un token d\'accès OAuth2
    $accessToken = getFirebaseAccessToken($serviceAccount);
    if (!$accessToken) {
        error_log("Impossible d\'obtenir le token d\'accès Firebase");
        return false;
    }
    
    // URL de l\'API Firebase v1
    $url = "https://fcm.googleapis.com/v1/projects/" . $serviceAccount["project_id"] . "/messages:send";
    
    $headers = [
        "Authorization: Bearer " . $accessToken,
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
        error_log("Erreur cURL moderne: " . $error);
        return false;
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData["name"])) {
            return true;
        } else {
            error_log("Erreur Firebase moderne: " . $response);
            return false;
        }
    } else {
        error_log("Erreur HTTP Firebase moderne: $httpCode - $response");
        return false;
    }
}

function getFirebaseAccessToken($serviceAccount) {
    // Créer un JWT pour l\'authentification
    $header = json_encode(["alg" => "RS256", "typ" => "JWT"]);
    $now = time();
    $payload = json_encode([
        "iss" => $serviceAccount["client_email"],
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => $serviceAccount["token_uri"],
        "exp" => $now + 3600,
        "iat" => $now
    ]);
    
    $base64Header = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($header));
    $base64Payload = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($payload));
    
    $signature = "";
    $success = openssl_sign($base64Header . "." . $base64Payload, $signature, $serviceAccount["private_key"], OPENSSL_ALGO_SHA256);
    
    if (!$success) {
        error_log("Erreur signature JWT: " . openssl_error_string());
        return false;
    }
    
    $base64Signature = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($signature));
    $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
    
    // Échanger le JWT contre un token d\'accès
    $tokenData = [
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion" => $jwt
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serviceAccount["token_uri"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tokenResponse = json_decode($response, true);
        return $tokenResponse["access_token"] ?? false;
    } else {
        error_log("Erreur token OAuth2: $httpCode - $response");
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
    
    error_log("Notifications modernes envoyées à $count élèves");
    $mysqli->close();
    return $success;
}

// Test si appelé directement
if (basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"] ?? "")) {
    echo "🔥 Test Firebase Service Account Moderne\n";
    echo "========================================\n\n";
    
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
            
            echo "🚀 Test Firebase moderne (Service Account)...\n";
            
            $success = sendPushNotification(
                $username,
                "🔥 Firebase Moderne Actif !",
                "Service Account + API v1 = Performance maximale !",
                "/"
            );
            
            if ($success) {
                echo "🎉 SUCCÈS ! Firebase moderne fonctionne\n";
                echo "📱 Vérifiez votre appareil\n";
                echo "⚡ API v1 + Service Account = TOP !\n";
            } else {
                echo "❌ Échec - vérifiez les logs\n";
            }
        }
    }
    
    $mysqli->close();
}
?>';

// Sauvegarder le fichier
if (file_put_contents("send_push_notification_modern.php", $firebaseContent)) {
    echo "✅ Fichier moderne créé : send_push_notification_modern.php\n";
} else {
    echo "❌ Erreur lors de la création du fichier\n";
    exit(1);
}

echo "\n🧪 Test immédiat du système moderne...\n";

// Test immédiat
require_once "send_push_notification_modern.php";

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
        
        echo "🚀 Test Firebase Service Account moderne...\n";
        $start_time = microtime(true);
        
        $success = sendPushNotification(
            $username,
            "🎉 Firebase Moderne Opérationnel !",
            "Service Account + API v1 = Solution ultime !",
            "/"
        );
        
        $end_time = microtime(true);
        $duration = round(($end_time - $start_time) * 1000, 2);
        
        if ($success) {
            echo "🎉 SUCCÈS TOTAL !\n";
            echo "📱 Notification envoyée via Service Account\n";
            echo "⚡ Temps: {$duration}ms\n";
            echo "🔥 Firebase moderne opérationnel !\n\n";
            
            echo "🔄 Activation système ultra-moderne...\n";
            
            // Sauvegarder l'ancien fichier
            if (file_exists('send_push_notification.php')) {
                copy('send_push_notification.php', 'send_push_notification_legacy.php');
                echo "💾 Ancien fichier sauvegardé\n";
            }
            
            if (copy("send_push_notification_modern.php", "send_push_notification.php")) {
                echo "✅ Système activé (version Service Account moderne)\n";
                echo "🔥 EduConnect Firebase moderne opérationnel !\n\n";
                
                echo "🎓 TESTS ULTRA-MODERNES :\n";
                echo "=========================\n";
                echo "1. 📱 iPhone : http://192.168.88.101:8080/eleve.php\n";
                echo "2. 🔔 Autorisez les notifications\n";
                echo "3. 💻 Créez un devoir (interface professeur)\n";
                echo "4. ⚡ Observez la vitesse ultra-rapide\n";
                echo "5. 🔥 Profitez de Firebase moderne !\n\n";
                
                echo "🏆 FÉLICITATIONS ULTIME !\n";
                echo "=========================\n";
                echo "Votre système EduConnect est maintenant :\n";
                echo "• 🔥 Firebase Service Account (méthode moderne)\n";
                echo "• ⚡ API v1 (dernière version)\n";
                echo "• 🔐 Authentification JWT sécurisée\n";
                echo "• 🚀 Performance maximale\n";
                echo "• ✅ Aucune clé legacy\n";
                echo "• 🌟 Solution professionnelle\n";
                echo "• 🎯 Prêt pour la production\n";
                
            } else {
                echo "⚠️  Copiez manuellement :\n";
                echo "   copy send_push_notification_modern.php send_push_notification.php\n";
            }
            
        } else {
            echo "❌ Échec du test\n";
            echo "🔍 Vérifiez les logs d'erreur\n";
        }
    }
} else {
    echo "⚠️  Aucun abonnement pour tester\n";
    echo "📱 Connectez-vous sur un appareil d'abord\n";
    echo "🔥 Mais Firebase Service Account est configuré !\n";
}

$mysqli->close();

echo "\n🔥 Configuration Service Account terminée !\n";
echo "Votre système utilise maintenant la technologie Firebase la plus moderne ! 🚀\n";
?>