<?php
// Script pour mettre à jour automatiquement les fichiers avec les nouvelles clés VAPID

echo "🔄 Mise à jour des fichiers de notification...\n\n";

// Lire les clés générées
if (!file_exists('vapid_keys.json')) {
    echo "❌ Fichier vapid_keys.json non trouvé. Exécutez d'abord generate_vapid_keys.php\n";
    exit(1);
}

$keys = json_decode(file_get_contents('vapid_keys.json'), true);

if (!$keys) {
    echo "❌ Impossible de lire les clés VAPID\n";
    exit(1);
}

echo "✅ Clés VAPID chargées\n";

// 1. Mettre à jour notifications.js
echo "📝 Mise à jour de notifications.js...\n";

$notificationsJs = file_get_contents('notifications.js');
$notificationsJs = preg_replace(
    '/const vapidPublicKey = \'[^\']*\';/',
    "const vapidPublicKey = '{$keys['publicKey']}';",
    $notificationsJs
);

file_put_contents('notifications.js', $notificationsJs);
echo "✅ notifications.js mis à jour\n";

// 2. Mettre à jour send_push_notification.php
echo "📝 Mise à jour de send_push_notification.php...\n";

$sendPushPhp = file_get_contents('send_push_notification.php');

// Remplacer la configuration VAPID
$newVapidConfig = "        \$auth = [
            'VAPID' => [
                'subject' => '{$keys['subject']}',
                'publicKey' => '{$keys['publicKey']}',
                'privateKey' => '{$keys['privateKey']}'
            ]
        ];";

$sendPushPhp = preg_replace(
    '/\$auth = \[.*?\];/s',
    $newVapidConfig,
    $sendPushPhp
);

file_put_contents('send_push_notification.php', $sendPushPhp);
echo "✅ send_push_notification.php mis à jour\n";

echo "\n🎉 Tous les fichiers ont été mis à jour avec les nouvelles clés VAPID !\n";
echo "🚀 Vous pouvez maintenant tester les notifications push.\n";
?>