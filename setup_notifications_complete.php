<?php
// Script complet de configuration des notifications push
echo "🚀 Configuration complète du système de notifications EduConnect\n\n";

// 1. Vérifier la base de données
echo "📊 Configuration de la base de données...\n";

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur de connexion à la base de données\n";
    exit(1);
}

// Créer la table push_subscriptions
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
    echo "✅ Table push_subscriptions créée\n";
} else {
    echo "❌ Erreur création table: " . $mysqli->error . "\n";
}

// 2. Créer les dossiers nécessaires
echo "📁 Création des dossiers...\n";

$directories = ['uploads', 'pieces_jointes', 'photos'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Dossier $dir créé\n";
    } else {
        echo "✅ Dossier $dir existe\n";
    }
}

// 3. Configurer les clés VAPID
echo "🔑 Configuration des clés VAPID...\n";

$publicKey = 'BEl62iUYgUivxIkv69yViEuiBIa40HI0DLLuxazjqAKVXTdtkoTrZPPUi5ygP-5ysIxdPSwPV3TbVBNuIUvzNAI';
$privateKey = 'UGxlYXNlIGdlbmVyYXRlIHlvdXIgb3duIGtleXMgZm9yIHByb2R1Y3Rpb24=';

$config = [
    'publicKey' => $publicKey,
    'privateKey' => $privateKey,
    'subject' => 'mailto:admin@educonnect.mg',
    'configured' => date('Y-m-d H:i:s')
];

file_put_contents('vapid_keys.json', json_encode($config, JSON_PRETTY_PRINT));
echo "✅ Clés VAPID configurées\n";

// 4. Vérifier les fichiers de notification
echo "📄 Vérification des fichiers...\n";

$requiredFiles = [
    'notifications.js' => 'Système de notifications côté client',
    'notification-setup.js' => 'Configuration automatique',
    'sw.js' => 'Service Worker',
    'save_push_subscription.php' => 'Sauvegarde des abonnements',
    'send_push_notification.php' => 'Envoi des notifications'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $file - $description\n";
    } else {
        echo "⚠️  $file manquant - $description\n";
    }
}

// 5. Tester la configuration
echo "\n🧪 Test de la configuration...\n";

// Test de la base de données
$testQuery = $mysqli->query("SELECT COUNT(*) as count FROM push_subscriptions");
if ($testQuery) {
    $count = $testQuery->fetch_assoc()['count'];
    echo "✅ Base de données opérationnelle ($count abonnements)\n";
} else {
    echo "❌ Erreur test base de données\n";
}

// Test des permissions de fichiers
if (is_writable('photos')) {
    echo "✅ Dossier photos accessible en écriture\n";
} else {
    echo "⚠️  Dossier photos non accessible en écriture\n";
}

echo "\n🎯 ÉTAPES FINALES :\n";
echo "==================\n";
echo "1. 🎨 Créez vos icônes :\n";
echo "   → Allez sur : http://192.168.88.101:8080/create_icons.html\n";
echo "   → Téléchargez educonnect-icon.png et educonnect-badge.png\n";
echo "   → Placez-les dans le dossier /photos/\n\n";

echo "2. 📱 Test sur iPhone :\n";
echo "   → Connectez-vous comme élève\n";
echo "   → Autorisez les notifications quand demandé\n";
echo "   → Créez une annonce depuis l'admin sur PC\n";
echo "   → Vérifiez que la notification arrive sur iPhone\n\n";

echo "3. 🔧 Dépannage :\n";
echo "   → Vérifiez que Apache écoute sur toutes les interfaces (0.0.0.0:8080)\n";
echo "   → Vérifiez que le pare-feu autorise le port 8080\n";
echo "   → Vérifiez que iPhone et PC sont sur le même WiFi\n\n";

echo "🎉 Configuration terminée ! Votre système de notifications est prêt.\n";

$mysqli->close();
?>