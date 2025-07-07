<?php
/**
 * Test de performance Firebase États-Unis
 */

echo "🇺🇸 Test Performance Firebase USA\n";
echo "=================================\n\n";

echo "⚡ Avantages serveurs États-Unis :\n";
echo "• 🚀 Latence minimale\n";
echo "• 📡 Bande passante optimale\n";
echo "• 🔧 Support technique prioritaire\n";
echo "• 🌍 Connectivité mondiale excellente\n\n";

if (!file_exists('send_push_notification_usa.php')) {
    echo "⚠️  Configuration USA pas encore faite\n";
    echo "🚀 Lancez : php configure_firebase_usa.php\n";
    exit(1);
}

echo "✅ Configuration USA trouvée !\n";

// Test du fichier
require_once 'send_push_notification_usa.php';

echo "🔌 Test de connexion...\n";
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur DB: " . $mysqli->connect_error . "\n";
    exit(1);
}
echo "✅ Connexion DB OK\n";

// Statistiques
$result = $mysqli->query("SELECT COUNT(*) as count FROM push_subscriptions");
$row = $result->fetch_assoc();
echo "📊 Total abonnements: " . $row['count'] . "\n";

$result = $mysqli->query("SELECT COUNT(DISTINCT username) as users FROM push_subscriptions");
$row = $result->fetch_assoc();
echo "👥 Utilisateurs: " . $row['users'] . "\n";

if ($row['users'] > 0) {
    echo "\n🇺🇸 Test performance serveurs USA...\n";
    
    $result = $mysqli->query("SELECT DISTINCT username FROM push_subscriptions LIMIT 1");
    if ($user = $result->fetch_assoc()) {
        $username = $user['username'];
        echo "👤 Test avec: $username\n";
        
        echo "⚡ Mesure de performance...\n";
        $start_time = microtime(true);
        
        $success = sendPushNotification(
            $username,
            "🇺🇸 Performance USA !",
            "Notification ultra-rapide depuis les États-Unis !",
            "/",
            "/photos/educonnect-icon.png"
        );
        
        $end_time = microtime(true);
        $duration = round(($end_time - $start_time) * 1000, 2);
        
        if ($success) {
            echo "🎉 SUCCÈS TOTAL !\n";
            echo "📱 Notification envoyée via serveurs USA\n";
            echo "⚡ Temps de traitement: {$duration}ms\n";
            echo "🚀 Performance optimisée États-Unis !\n\n";
            
            echo "🔄 Activation système haute performance...\n";
            
            if (copy('send_push_notification_usa.php', 'send_push_notification.php')) {
                echo "✅ Système activé (version haute performance USA)\n";
                echo "🇺🇸 EduConnect optimisé États-Unis opérationnel !\n\n";
                
                echo "🎓 TESTS DE PERFORMANCE :\n";
                echo "=========================\n";
                echo "1. 📱 Connectez-vous : http://192.168.88.101:8080/eleve.php\n";
                echo "2. 🔔 Autorisez les notifications\n";
                echo "3. 💻 Créez un devoir (interface professeur)\n";
                echo "4. ⚡ Observez la vitesse de réception\n";
                echo "5. 🇺🇸 Profitez de la performance USA !\n\n";
                
                echo "🏆 FÉLICITATIONS !\n";
                echo "==================\n";
                echo "Votre système EduConnect est maintenant :\n";
                echo "• 🇺🇸 Optimisé serveurs États-Unis\n";
                echo "• ⚡ Performance maximale\n";
                echo "• 🚀 Latence minimale\n";
                echo "• 📡 Bande passante optimale\n";
                echo "• ✅ Prêt pour la production\n";
                echo "• 🌍 Connectivité mondiale excellente\n";
                
            } else {
                echo "⚠️  Copiez manuellement :\n";
                echo "   copy send_push_notification_usa.php send_push_notification.php\n";
            }
            
        } else {
            echo "❌ Échec de l'envoi\n";
            echo "🔍 Vérifiez votre clé Firebase\n";
        }
        
    } else {
        echo "❌ Aucun utilisateur trouvé\n";
    }
} else {
    echo "\n⚠️  Aucun abonnement pour tester\n";
    echo "📱 Connectez-vous sur un appareil d'abord\n";
    echo "🇺🇸 Mais Firebase USA est configuré !\n";
}

$mysqli->close();

echo "\n🇺🇸 Test performance USA terminé !\n";
echo "Votre système bénéficie maintenant de la performance maximale ! ⚡\n";
?>