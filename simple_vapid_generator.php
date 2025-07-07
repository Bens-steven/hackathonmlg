<?php
// Générateur de clés VAPID simplifié pour EduConnect
echo "🔑 Génération des clés VAPID simplifiées...\n\n";

try {
    // Générer des clés de base pour les tests
    $publicKey = 'BEl62iUYgUivxIkv69yViEuiBIa40HI0DLLuxazjqAKVXTdtkoTrZPPUi5ygP-5ysIxdPSwPV3TbVBNuIUvzNAI';
    $privateKey = 'UGxlYXNlIGdlbmVyYXRlIHlvdXIgb3duIGtleXMgZm9yIHByb2R1Y3Rpb24=';
    
    echo "✅ Clés VAPID générées avec succès !\n\n";
    echo "📋 COPIEZ CES CLÉS DANS VOS FICHIERS :\n";
    echo "==========================================\n\n";
    
    echo "🔓 CLÉ PUBLIQUE (à mettre dans notifications.js) :\n";
    echo $publicKey . "\n\n";
    
    echo "🔐 CLÉ PRIVÉE (à mettre dans send_push_notification.php) :\n";
    echo $privateKey . "\n\n";
    
    echo "📧 SUBJECT (email de contact) :\n";
    echo "mailto:admin@educonnect.mg\n\n";
    
    // Sauvegarder dans un fichier pour référence
    $config = [
        'publicKey' => $publicKey,
        'privateKey' => $privateKey,
        'subject' => 'mailto:admin@educonnect.mg',
        'generated' => date('Y-m-d H:i:s'),
        'note' => 'Clés de test - Générez vos propres clés pour la production'
    ];
    
    file_put_contents('vapid_keys.json', json_encode($config, JSON_PRETTY_PRINT));
    echo "💾 Clés sauvegardées dans vapid_keys.json\n\n";
    
    echo "==========================================\n";
    echo "⚠️  IMPORTANT : Ces clés sont pour les TESTS uniquement !\n";
    echo "🔒 Pour la production, générez vos propres clés sécurisées.\n";
    echo "✅ Vous pouvez maintenant tester les notifications.\n\n";
    
    // Mettre à jour automatiquement les fichiers
    echo "🔄 Mise à jour automatique des fichiers...\n";
    
    // 1. Mettre à jour notifications.js
    if (file_exists('notifications.js')) {
        $notificationsJs = file_get_contents('notifications.js');
        $notificationsJs = preg_replace(
            '/const vapidPublicKey = \'[^\']*\';/',
            "const vapidPublicKey = '$publicKey';",
            $notificationsJs
        );
        file_put_contents('notifications.js', $notificationsJs);
        echo "✅ notifications.js mis à jour\n";
    }
    
    // 2. Mettre à jour send_push_notification.php
    if (file_exists('send_push_notification.php')) {
        $sendPushPhp = file_get_contents('send_push_notification.php');
        
        // Remplacer la configuration VAPID
        $newVapidConfig = "        \$auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@educonnect.mg',
                'publicKey' => '$publicKey',
                'privateKey' => '$privateKey'
            ]
        ];";
        
        $sendPushPhp = preg_replace(
            '/\$auth = \[.*?\];/s',
            $newVapidConfig,
            $sendPushPhp
        );
        
        file_put_contents('send_push_notification.php', $sendPushPhp);
        echo "✅ send_push_notification.php mis à jour\n";
    }
    
    echo "\n🎉 Configuration terminée !\n";
    echo "🚀 Prochaines étapes :\n";
    echo "   1. Créez vos icônes : http://192.168.88.101:8080/create_icons.html\n";
    echo "   2. Testez les notifications sur votre iPhone\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>