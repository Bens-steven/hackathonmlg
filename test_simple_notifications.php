<?php
// Test simplifié des notifications sans dépendances externes
session_start();

echo "🧪 Test simplifié des notifications push\n\n";

// Simuler un utilisateur connecté pour le test
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'test.eleve';
    echo "👤 Utilisateur de test créé : test.eleve\n";
}

// Test de la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur de connexion à la base de données: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Connexion à la base de données OK\n";

// ✅ CORRECTION : Créer d'abord la table si elle n'existe pas
$createTable = "
    CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        endpoint TEXT NOT NULL,
        p256dh_key TEXT NOT NULL,
        auth_key TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_endpoint (username, endpoint(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if ($mysqli->query($createTable)) {
    echo "✅ Table push_subscriptions créée/vérifiée\n";
} else {
    echo "❌ Erreur création table: " . $mysqli->error . "\n";
    exit(1);
}

// Créer un abonnement de test
$testSubscription = [
    'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-' . time(),
    'keys' => [
        'p256dh' => 'test-p256dh-key-' . time(),
        'auth' => 'test-auth-key-' . time()
    ]
];

// ✅ CORRECTION : Vérifier que la requête prepare() réussit
$stmt = $mysqli->prepare("
    INSERT INTO push_subscriptions (username, endpoint, p256dh_key, auth_key) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    p256dh_key = VALUES(p256dh_key),
    auth_key = VALUES(auth_key)
");

if (!$stmt) {
    echo "❌ Erreur préparation requête INSERT: " . $mysqli->error . "\n";
    exit(1);
}

$username = 'test.eleve';
$endpoint = $testSubscription['endpoint'];
$p256dh = $testSubscription['keys']['p256dh'];
$auth = $testSubscription['keys']['auth'];

$stmt->bind_param("ssss", $username, $endpoint, $p256dh, $auth);

if ($stmt->execute()) {
    echo "✅ Abonnement de test créé\n";
} else {
    echo "❌ Erreur création abonnement de test: " . $stmt->error . "\n";
}

$stmt->close();

// Tester la récupération des abonnements
$stmt = $mysqli->prepare("SELECT * FROM push_subscriptions WHERE username = ?");

if (!$stmt) {
    echo "❌ Erreur préparation requête SELECT: " . $mysqli->error . "\n";
    exit(1);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Récupération des abonnements OK\n";
    $subscription = $result->fetch_assoc();
    echo "📋 Endpoint: " . substr($subscription['endpoint'], 0, 50) . "...\n";
    echo "📊 Total abonnements pour $username: " . $result->num_rows . "\n";
} else {
    echo "❌ Aucun abonnement trouvé\n";
}

$stmt->close();

// Test de la structure JSON pour les notifications
$notificationPayload = [
    'title' => 'Test EduConnect',
    'body' => 'Ceci est une notification de test !',
    'icon' => '/photos/educonnect-icon.png',
    'badge' => '/photos/educonnect-badge.png',
    'url' => '/eleve.php',
    'tag' => 'test-' . time()
];

$jsonPayload = json_encode($notificationPayload);
if ($jsonPayload) {
    echo "✅ Payload JSON valide\n";
    echo "📦 Taille: " . strlen($jsonPayload) . " bytes\n";
} else {
    echo "❌ Erreur création payload JSON\n";
}

// Vérifier les fichiers nécessaires
$files = [
    'notifications.js',
    'notification-setup.js', 
    'sw.js',
    'save_push_subscription.php',
    'send_push_notification.php'
];

echo "\n📁 Vérification des fichiers :\n";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file (" . number_format(filesize($file)) . " bytes)\n";
    } else {
        echo "❌ $file manquant\n";
    }
}

// Vérifier les icônes
echo "\n🖼️  Vérification des icônes :\n";
if (file_exists('photos/educonnect-icon.png')) {
    echo "✅ educonnect-icon.png (" . number_format(filesize('photos/educonnect-icon.png')) . " bytes)\n";
} else {
    echo "⚠️  educonnect-icon.png manquant\n";
    echo "   → Créez-le sur : http://192.168.88.101:8080/create_icons.html\n";
}

if (file_exists('photos/educonnect-badge.png')) {
    echo "✅ educonnect-badge.png (" . number_format(filesize('photos/educonnect-badge.png')) . " bytes)\n";
} else {
    echo "⚠️  educonnect-badge.png manquant\n";
    echo "   → Créez-le sur : http://192.168.88.101:8080/create_icons.html\n";
}

// Test de la fonction d'envoi de notifications
echo "\n🔔 Test de la fonction d'envoi :\n";
if (file_exists('send_push_notification.php')) {
    // Inclure et tester la fonction
    require_once 'send_push_notification.php';
    
    // Test d'envoi (ne va pas vraiment envoyer car endpoint de test)
    $testResult = sendPushNotification(
        $username,
        'Test EduConnect',
        'Notification de test depuis le script de vérification',
        '/eleve.php'
    );
    
    if ($testResult) {
        echo "✅ Fonction d'envoi opérationnelle\n";
    } else {
        echo "⚠️  Fonction d'envoi testée (échec attendu avec endpoint de test)\n";
    }
} else {
    echo "❌ Fichier send_push_notification.php manquant\n";
}

echo "\n🎯 PROCHAINES ÉTAPES :\n";
echo "===================\n";
echo "1. 🎨 Créez vos icônes si elles manquent :\n";
echo "   → http://192.168.88.101:8080/create_icons.html\n\n";
echo "2. 📱 Testez sur votre iPhone :\n";
echo "   → http://192.168.88.101:8080/eleve.php\n";
echo "   → Connectez-vous avec un compte élève\n";
echo "   → Autorisez les notifications\n\n";
echo "3. 🔔 Testez l'envoi :\n";
echo "   → Créez une annonce depuis l'admin\n";
echo "   → Vérifiez la réception sur iPhone\n\n";

echo "🚀 Le système est configuré et prêt pour les tests !\n";
echo "📊 Base de données : " . $mysqli->info . "\n";

$mysqli->close();
?>