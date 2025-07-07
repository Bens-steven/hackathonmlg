<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification que la classe est fournie
if (!isset($_POST['classe'])) {
    echo json_encode(['success' => false, 'message' => 'Classe non spécifiée']);
    exit;
}

$classe = $_POST['classe'];

// Connexion à MySQL
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Récupérer les statistiques de la classe
    $stmt = $mysqli->prepare("
        SELECT 
            COUNT(DISTINCT eleve) as nb_eleves,
            SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
            SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
        FROM absence_retard 
        WHERE classe = ?
    ");
    $stmt->bind_param("s", $classe);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stmt->close();
    
    // Récupérer tous les élèves avec leurs absences et retards
    $stmt = $mysqli->prepare("
        SELECT 
            eleve,
            COUNT(*) as total,
            SUM(CASE WHEN type = 'absence' THEN 1 ELSE 0 END) as nb_absences,
            SUM(CASE WHEN type = 'retard' THEN 1 ELSE 0 END) as nb_retards
        FROM absence_retard 
        WHERE classe = ? 
        GROUP BY eleve
        ORDER BY eleve
    ");
    $stmt->bind_param("s", $classe);
    $stmt->execute();
    $students_result = $stmt->get_result();
    
    $students = [];
    while ($student = $students_result->fetch_assoc()) {
        // Récupérer les détails des absences/retards pour cet élève
        $stmt2 = $mysqli->prepare("
            SELECT id, date, heure, type, motif 
            FROM absence_retard 
            WHERE classe = ? AND eleve = ? 
            ORDER BY date DESC, heure DESC
        ");
        $stmt2->bind_param("ss", $classe, $student['eleve']);
        $stmt2->execute();
        $absences_result = $stmt2->get_result();
        
        $absences = [];
        while ($absence = $absences_result->fetch_assoc()) {
            $absences[] = $absence;
        }
        $stmt2->close();
        
        $student['absences'] = $absences;
        $students[] = $student;
    }
    
    $stmt->close();
    $mysqli->close();
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>