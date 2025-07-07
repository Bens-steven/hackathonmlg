<?php
// Script de debug pour vérifier les données dans la base
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    die("Erreur connexion MySQL: " . $mysqli->connect_error);
}

echo "<h2>🔍 Debug des données dans la base</h2>";

// Vérifier les classes disponibles
echo "<h3>📊 Classes dans la table notes:</h3>";
$result = $mysqli->query("SELECT DISTINCT classe, COUNT(*) as nb_notes FROM notes GROUP BY classe");
while ($row = $result->fetch_assoc()) {
    echo "- Classe: {$row['classe']} ({$row['nb_notes']} notes)<br>";
}

echo "<h3>📊 Classes dans la table absence_retard:</h3>";
$result = $mysqli->query("SELECT DISTINCT classe, COUNT(*) as nb_absences FROM absence_retard GROUP BY classe");
while ($row = $result->fetch_assoc()) {
    echo "- Classe: {$row['classe']} ({$row['nb_absences']} absences/retards)<br>";
}

// Vérifier les élèves pour chaque classe
$classes = ['L1G1', 'L1G2', 'L2G1', 'L2G2'];

foreach ($classes as $classe) {
    echo "<h3>👥 Élèves de la classe $classe:</h3>";
    
    echo "<h4>Dans la table notes:</h4>";
    $stmt = $mysqli->prepare("SELECT DISTINCT eleve_username, COUNT(*) as nb_notes, ROUND(AVG(note), 1) as moyenne FROM notes WHERE classe = ? GROUP BY eleve_username");
    $stmt->bind_param("s", $classe);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['eleve_username']} (moyenne: {$row['moyenne']}, {$row['nb_notes']} notes)<br>";
        }
    } else {
        echo "Aucun élève trouvé<br>";
    }
    $stmt->close();
    
    echo "<h4>Dans la table absence_retard:</h4>";
    $stmt = $mysqli->prepare("SELECT DISTINCT eleve, COUNT(*) as nb_total, SUM(CASE WHEN type='absence' THEN 1 ELSE 0 END) as nb_absences, SUM(CASE WHEN type='retard' THEN 1 ELSE 0 END) as nb_retards FROM absence_retard WHERE classe = ? GROUP BY eleve");
    $stmt->bind_param("s", $classe);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['eleve']} (absences: {$row['nb_absences']}, retards: {$row['nb_retards']})<br>";
        }
    } else {
        echo "Aucun élève trouvé<br>";
    }
    $stmt->close();
    
    echo "<br>";
}

$mysqli->close();
?>