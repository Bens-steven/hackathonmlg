<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que l'ID est fourni
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID non spécifié']);
    exit;
}

$id = intval($_POST['id']);

// Connexion à MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Vérifier que l'enregistrement existe
    $stmt = $mysqli->prepare("SELECT id FROM absence_retard WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Enregistrement non trouvé']);
        $stmt->close();
        $mysqli->close();
        exit;
    }
    $stmt->close();
    
    // Supprimer l'enregistrement
    $stmt = $mysqli->prepare("DELETE FROM absence_retard WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enregistrement supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    
    $stmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>