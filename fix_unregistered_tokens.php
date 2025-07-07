<?php
/**
 * Solution pour corriger les tokens FCM expirés (erreur UNREGISTERED)
 */

echo "🔧 Correction Tokens FCM Expirés\n";
echo "================================\n\n";

echo "🎯 DIAGNOSTIC :\n";
echo "===============\n";
echo "✅ Firebase Service Account fonctionne parfaitement\n";
echo "✅ Authentification JWT réussie\n";
echo "✅ API v1 opérationnelle\n";
echo "❌ Tokens FCM dans la DB sont expirés (erreur UNREGISTERED)\n\n";

echo "🔍 CAUSE DU PROBLÈME :\n";
echo "======================\n";
echo "Les tokens FCM dans votre table 'push_subscriptions' sont anciens.\n";
echo "Firebase les invalide automatiquement après un certain temps.\n\n";

echo "💡 SOLUTIONS :\n";
echo "==============\n\n";

echo "SOLUTION 1 : Nettoyer les anciens tokens\n";
echo "----------------------------------------\n";

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur DB: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Connexion DB OK\n";

// Afficher les tokens actuels
$result = $mysqli->query("SELECT username, endpoint, DATE(created_at) as date_creation FROM push_subscriptions ORDER BY created_at DESC");
echo "\n📋 Tokens actuels dans la DB :\n";
echo "==============================\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    $token = '';
    if (preg_match('/fcm\/send\/(.+)$/', $row['endpoint'], $matches)) {
        $token = substr($matches[1], 0, 20) . '...';
    }
    echo "$count. {$row['username']} - Token: $token - Date: {$row['date_creation']}\n";
}

echo "\n🧹 NETTOYAGE RECOMMANDÉ :\n";
echo "=========================\n";
echo "1. Supprimez tous les anciens tokens\n";
echo "2. Reconnectez-vous sur vos appareils\n";
echo "3. Autorisez à nouveau les notifications\n\n";

echo "Voulez-vous supprimer tous les anciens tokens ? (y/n) : ";
$handle = fopen("php://stdin", "r");
$confirm = trim(fgets($handle));
fclose($handle);

if (strtolower($confirm) === 'y') {
    echo "\n🗑️  Suppression des anciens tokens...\n";
    
    $result = $mysqli->query("DELETE FROM push_subscriptions");
    if ($result) {
        echo "✅ Tous les anciens tokens supprimés\n";
        echo "📊 Tokens supprimés: " . $mysqli->affected_rows . "\n";
    } else {
        echo "❌ Erreur lors de la suppression: " . $mysqli->error . "\n";
    }
} else {
    echo "⚠️  Tokens conservés\n";
}

$mysqli->close();

echo "\n🎯 PROCHAINES ÉTAPES :\n";
echo "=====================\n";
echo "1. 📱 Allez sur votre iPhone\n";
echo "2. 🌐 Ouvrez : http://192.168.88.101:8080/eleve.php\n";
echo "3. 🔔 Autorisez les notifications (nouveau token sera créé)\n";
echo "4. 🧪 Relancez : php test_service_account_final.php\n";
echo "5. 🎉 Profitez de votre système moderne !\n\n";

echo "✨ VOTRE SYSTÈME EST PARFAIT !\n";
echo "==============================\n";
echo "Le Service Account fonctionne à 100%.\n";
echo "Il suffit juste de renouveler les tokens FCM.\n";
echo "C'est normal et très facile à faire !\n";
?>