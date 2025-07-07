<?php
session_start();

// Connexion MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Récupération des paramètres
$id = $_GET['id'] ?? '';
$classe = $_GET['classe'] ?? '';

if (empty($id) || empty($classe)) {
    header("Location: gestion_edt.php?classe=" . urlencode($classe) . "&error=Paramètres manquants");
    exit;
}

// Suppression du cours
$stmt = $mysqli->prepare("DELETE FROM emplois_du_temps WHERE id = ? AND classe = ?");
$stmt->bind_param("is", $id, $classe);

if ($stmt->execute()) {
    // Redirection avec message de succès
    header("Location: gestion_edt.php?classe=" . urlencode($classe) . "&success=Cours supprimé avec succès");
} else {
    // Redirection avec message d'erreur
    header("Location: gestion_edt.php?classe=" . urlencode($classe) . "&error=Erreur lors de la suppression");
}

$stmt->close();
$mysqli->close();
exit;
?>
