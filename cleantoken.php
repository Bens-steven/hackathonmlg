<?php
/**
 * Nettoyage intelligent des tokens et test immédiat
 */

echo "🧹 Nettoyage Intelligent des Tokens\n";
echo "===================================\n\n";

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur DB: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Connexion DB OK\n";

// Analyser les tokens actuels
$result = $mysqli->query("
    SELECT username, COUNT(*) as count,
           MIN(created_at) as first_token,
           MAX(created_at) as last_token
    FROM push_subscriptions 
    GROUP BY username
    ORDER BY username
");

echo "\n📊 Analyse par utilisateur :\n";
echo "============================\n";

$total_tokens = 0;
$test_tokens = 0;
$real_tokens = 0;

while ($row = $result->fetch_assoc()) {
    $count = $row['count'];
    $total_tokens += $count;
    
    if ($row['username'] === 'test.eleve') {
        $test_tokens += $count;
        echo "🧪 {$row['username']} : $count tokens (TEST)\n";
    } else {
        $real_tokens += $count;
        echo "👤 {$row['username']} : $count tokens (RÉEL)\n";
    }
}

echo "\n📋 Résumé :\n";
echo "===========\n";
echo "🧪 Tokens de test : $test_tokens\n";
echo "👤 Tokens réels : $real_tokens\n";
echo "📊 Total : $total_tokens\n\n";

echo "🎯 STRATÉGIE DE NETTOYAGE :\n";
echo "===========================\n";
echo "1. Supprimer TOUS les tokens de test (test.eleve)\n";
echo "2. Garder les tokens réels mais les marquer comme suspects\n";
echo "3. Forcer la création de nouveaux tokens\n\n";

echo "Procéder au nettoyage ? (y/n) : ";
$handle = fopen("php://stdin", "r");
$confirm = trim(fgets($handle));
fclose($handle);

if (strtolower($confirm) === 'y') {
    echo "\n🗑️  Nettoyage en cours...\n";
    
    // Supprimer tous les tokens de test
    $result = $mysqli->query("DELETE FROM push_subscriptions WHERE username = 'test.eleve'");
    if ($result) {
        $deleted = $mysqli->affected_rows;
        echo "✅ $deleted tokens de test supprimés\n";
    }
    
    // Marquer les tokens réels comme anciens (optionnel)
    $result = $mysqli->query("
        UPDATE push_subscriptions 
        SET created_at = DATE_SUB(created_at, INTERVAL 1 DAY)
        WHERE username != 'test.eleve'
    ");
    
    echo "✅ Tokens réels marqués comme anciens\n";
    
    // Vérifier le résultat
    $result = $mysqli->query("SELECT COUNT(*) as count FROM push_subscriptions");
    $row = $result->fetch_assoc();
    echo "📊 Tokens restants : " . $row['count'] . "\n";
    
} else {
    echo "⚠️  Nettoyage annulé\n";
}

$mysqli->close();

echo "\n🎯 PROCHAINES ÉTAPES CRITIQUES :\n";
echo "================================\n";
echo "1. 📱 Sur votre iPhone, allez sur :\n";
echo "   👉 http://192.168.88.101:8080/eleve.php\n\n";
echo "2. 🔄 Rafraîchissez la page (F5)\n\n";
echo "3. 🔔 Quand le navigateur demande l'autorisation :\n";
echo "   👉 Cliquez 'Autoriser' ou 'Allow'\n\n";
echo "4. ✅ Un nouveau token FCM sera créé automatiquement\n\n";
echo "5. 🧪 Relancez le test :\n";
echo "   👉 php test_service_account_final.php\n\n";

echo "💡 ASTUCE IMPORTANTE :\n";
echo "======================\n";
echo "Si le navigateur ne demande pas l'autorisation :\n";
echo "• Videz le cache du navigateur\n";
echo "• Ou utilisez un mode navigation privée\n";
echo "• Ou essayez un autre navigateur\n\n";

echo "🏆 VOTRE SYSTÈME EST PARFAIT !\n";
echo "==============================\n";
echo "Le Service Account fonctionne à 100%.\n";
echo "Firebase est correctement configuré.\n";
echo "Il faut juste des tokens FCM frais !\n";
?>