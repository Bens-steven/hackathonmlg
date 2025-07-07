<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

if (!isset($_POST['homework_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID du devoir manquant']);
    exit;
}

$homework_id = $_POST['homework_id'];

// Connexion à MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion']);
    exit;
}

try {
    // Récupérer les informations du devoir avant suppression (pour supprimer le fichier)
    $stmt = $mysqli->prepare("SELECT fichier FROM devoirs WHERE id = ?");
    $stmt->bind_param("i", $homework_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $homework = $result->fetch_assoc();
    $stmt->close();
    
    if (!$homework) {
        echo json_encode(['success' => false, 'error' => 'Devoir non trouvé']);
        exit;
    }
    
    // Supprimer le devoir de la base de données
    $stmt = $mysqli->prepare("DELETE FROM devoirs WHERE id = ?");
    $stmt->bind_param("i", $homework_id);
    
    if ($stmt->execute()) {
        // Supprimer le fichier associé s'il existe
        if ($homework['fichier'] && file_exists("pieces_jointes/" . $homework['fichier'])) {
            unlink("pieces_jointes/" . $homework['fichier']);
        }
        
        // Supprimer aussi les rendus associés
        $stmt2 = $mysqli->prepare("DELETE FROM rendus_devoirs WHERE devoir_id = ?");
        $stmt2->bind_param("i", $homework_id);
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']);
    }
    
    $stmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>