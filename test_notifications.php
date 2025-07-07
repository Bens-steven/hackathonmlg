<?php
// Script de test pour les notifications push
session_start();

echo "🧪 Test du système de notifications push EduConnect\n\n";

// Vérifier que Composer est installé
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Composer non installé. Exécutez 'composer install' d'abord.\n";
    exit(1);
}

require_once 'vendor/autoload.php';

// Vérifier la base de données
echo "🔍 Vérification de la base de données...\n";

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur de connexion à la base de données: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Connexion à la base de données OK\n";

// Créer la table push_subscriptions si elle n'existe pas
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
    )
";

if ($mysqli->query($createTable)) {
    echo "✅ Table push_subscriptions créée/vérifiée\n";
} else {
    echo "❌ Erreur création table: " . $mysqli->error . "\n";
}

// Vérifier les clés VAPID
echo "🔑 Vérification des clés VAPID...\n";

if (file_exists('vapid_keys.json')) {
    $keys = json_decode(file_get_contents('vapid_keys.json'), true);
    echo "✅ Clés VAPID trouvées\n";
    echo "📋 Clé publique: " . substr($keys['publicKey'], 0, 20) . "...\n";
} else {
    echo "⚠️  Clés VAPID non trouvées. Exécutez generate_vapid_keys.php\n";
}

// Vérifier les fichiers nécessaires
$requiredFiles = [
    'notifications.js',
    'notification-setup.js',
    'sw.js',
    'save_push_subscription.php',
    'send_push_notification.php'
];

echo "📁 Vérification des fichiers...\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file manquant\n";
    }
}

// Vérifier les icônes
echo "🖼️  Vérification des icônes...\n";
if (file_exists('photos/educonnect-icon.png')) {
    echo "✅ educonnect-icon.png\n";
} else {
    echo "⚠️  educonnect-icon.png manquant (créez-le avec create_icons.html)\n";
}

if (file_exists('photos/educonnect-badge.png')) {
    echo "✅ educonnect-badge.png\n";
} else {
    echo "⚠️  educonnect-badge.png manquant (créez-le avec create_icons.html)\n";
}

echo "\n🎯 ÉTAPES SUIVANTES :\n";
echo "1. Créez vos icônes : http://192.168.88.101:8080/create_icons.html\n";
echo "2. Placez-les dans /photos/\n";
echo "3. Connectez-vous comme élève sur votre iPhone\n";
echo "4. Autorisez les notifications\n";
echo "5. Testez en créant une annonce depuis l'admin\n\n";

echo "🚀 Le système est prêt pour les tests !\n";

$mysqli->close();
?>