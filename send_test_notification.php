<?php
// Fichier pour tester les notifications push
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Inclure le système de notifications
require_once 'send_push_notification.php';

// Récupérer les données
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$username = $data['username'] ?? $_SESSION['username'];

// Envoyer une notification de test
$title = "🎉 Test EduConnect";
$body = "Félicitations ! Vos notifications push fonctionnent parfaitement. Vous recevrez maintenant toutes les nouvelles annonces et devoirs directement sur votre téléphone ! 📱✨";
$url = "/eleve.php";

$success = sendPushNotification($username, $title, $body, $url);

if ($success) {
    echo json_encode([
        'success' => true, 
        'message' => 'Notification de test envoyée !',
        'username' => $username
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'envoi de la notification de test'
    ]);
}
?>