<?php
session_start();
header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération de l'ID de la note
$note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;

if ($note_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de note invalide']);
    exit;
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur connexion base de données']);
    exit;
}

// Vérifier que la note existe et appartient à la matière du professeur
$matiere = $_SESSION['matiere'] ?? '';
if (empty($matiere)) {
    echo json_encode(['success' => false, 'error' => 'Matière non définie dans la session']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM notes WHERE id = ? AND matiere = ?");
$stmt->bind_param("is", $note_id, $matiere);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Note non trouvée ou non autorisée']);
    $stmt->close();
    $mysqli->close();
    exit;
}

$stmt->close();

// Supprimer la note
$stmt = $mysqli->prepare("DELETE FROM notes WHERE id = ? AND matiere = ?");
$stmt->bind_param("is", $note_id, $matiere);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Note supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune note supprimée']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $mysqli->error]);
}

$stmt->close();
$mysqli->close();
?>