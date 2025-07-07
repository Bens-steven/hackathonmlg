<?php
session_start();
header('Content-Type: application/json');

// Log de débogage (après le session_start)
file_put_contents("log_debug.txt", json_encode([
    'session_matiere' => $_SESSION['matiere'] ?? 'NON DEFINIE',
    'classe_get' => $_GET['classe'] ?? 'NON DEFINIE',
    'session_username' => $_SESSION['username'] ?? 'NON CONNECTÉ'
], JSON_PRETTY_PRINT));

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur connexion base de données: ' . $mysqli->connect_error]);
    exit;
}

$classe = isset($_GET['classe']) ? $_GET['classe'] : '';
$matiere = $_SESSION['matiere'] ?? ''; // Si la matière n'est pas définie, ce sera une chaîne vide

if (empty($classe)) {
    echo json_encode(['success' => false, 'error' => 'Classe non spécifiée']);
    exit;
}

if (empty($matiere)) {
    echo json_encode(['success' => false, 'error' => 'Matière non définie dans la session']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT 
        id,
        eleve_username, 
        note, 
        matiere,
        DATE_FORMAT(date, '%d/%m/%Y') as date_formatted 
    FROM notes 
    WHERE classe = ? AND matiere = ? 
    ORDER BY eleve_username, date DESC
");

if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête SQL: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("ss", $classe, $matiere);
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}

$nb_eleves = 0;
$moyenne = 0;

if (!empty($notes)) {
    $eleves_uniques = array_unique(array_column($notes, 'eleve_username'));
    $nb_eleves = count($eleves_uniques);
    $total_notes = array_sum(array_column($notes, 'note'));
    $moyenne = round($total_notes / count($notes), 1);
}

$stats = [
    'nb_eleves' => $nb_eleves,
    'moyenne' => $moyenne
];

echo json_encode([
    'success' => true, 
    'notes' => $notes,
    'stats' => $stats
]);

$stmt->close();
$mysqli->close();
?>
