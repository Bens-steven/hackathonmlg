<?php
session_start();
header('Content-Type: application/json');

// Vérification de session
if (!isset($_SESSION['username']) || !isset($_SESSION['matiere'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté ou matière non définie']);
    exit;
}

if (!isset($_GET['classe'])) {
    echo json_encode(['success' => false, 'error' => 'Classe non spécifiée']);
    exit;
}

$classe = $_GET['classe'];
$matiere = $_SESSION['matiere'];

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion']);
    exit;
}

try {
    // Récupérer les devoirs filtrés par classe + matière
    $stmt = $mysqli->prepare("
        SELECT 
            id,
            titre,
            contenu,
            fichier,
            date_creation,
            date_limite,
            DATE_FORMAT(date_creation, '%d/%m/%Y à %H:%i') as date_creation_formatted,
            DATE_FORMAT(date_limite, '%d/%m/%Y à %H:%i') as date_limite_formatted
        FROM devoirs 
        WHERE classe = ? AND matiere = ?
        ORDER BY date_creation DESC
    ");
    
    $stmt->bind_param("ss", $classe, $matiere);
    $stmt->execute();
    $result = $stmt->get_result();

    $homework = [];
    while ($row = $result->fetch_assoc()) {
        $homework[] = $row;
    }

    $stats = [
        'nb_devoirs' => count($homework)
    ];

    $stmt->close();
    $mysqli->close();

    echo json_encode([
        'success' => true,
        'homework' => $homework,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
