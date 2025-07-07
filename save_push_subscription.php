<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['subscription'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$username = $_SESSION['username'];
$subscription = $data['subscription'];

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
    exit;
}

try {
    // Créer la table si elle n'existe pas
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
    $mysqli->query($createTable);

    // Préparer les données
    $endpoint = $subscription['endpoint'];
    $p256dh = $subscription['keys']['p256dh'];
    $auth = $subscription['keys']['auth'];

    // Insérer ou mettre à jour l'abonnement
    $stmt = $mysqli->prepare("
        INSERT INTO push_subscriptions (username, endpoint, p256dh_key, auth_key) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        p256dh_key = VALUES(p256dh_key),
        auth_key = VALUES(auth_key),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("ssss", $username, $endpoint, $p256dh, $auth);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Abonnement sauvegardé',
            'username' => $username
        ]);
    } else {
        throw new Exception('Erreur lors de la sauvegarde');
    }

} catch (Exception $e) {
    error_log("Erreur push subscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} finally {
    $mysqli->close();
}
?>