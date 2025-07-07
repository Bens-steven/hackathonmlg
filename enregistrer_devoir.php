<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "❌ Méthode non autorisée.";
    header("Location: professeur.php");
    exit();
}

// Récupération des données du formulaire
$classe = trim($_POST['classe'] ?? '');
$titre = trim($_POST['titre'] ?? '');
$contenu = trim($_POST['contenu'] ?? '');
$matiere = trim($_POST['matiere'] ?? '');
$date_limite = $_POST['date_limite'] ?? '';

// Validation des données
if (empty($classe) || empty($titre) || empty($contenu) || empty($matiere) || empty($date_limite)) {
    $_SESSION['message'] = "❌ Tous les champs obligatoires doivent être remplis.";
    header("Location: professeur.php");
    exit();
}

// Gestion du fichier joint
$fichier_nom = null;
$upload_dir = 'pieces_jointes/';

if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
    // Créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $fichier_tmp = $_FILES['fichier']['tmp_name'];
    $fichier_nom = time() . '_' . basename($_FILES['fichier']['name']);
    $fichier_path = $upload_dir . $fichier_nom;
    
    // Vérifications de sécurité
    $extensions_autorisees = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'];
    $extension = strtolower(pathinfo($fichier_nom, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensions_autorisees)) {
        $_SESSION['message'] = "❌ Type de fichier non autorisé.";
        header("Location: professeur.php");
        exit();
    }
    
    if ($_FILES['fichier']['size'] > 10 * 1024 * 1024) { // 10MB max
        $_SESSION['message'] = "❌ Le fichier est trop volumineux (max 10MB).";
        header("Location: professeur.php");
        exit();
    }
    
    if (!move_uploaded_file($fichier_tmp, $fichier_path)) {
        $_SESSION['message'] = "❌ Erreur lors de l'upload du fichier.";
        header("Location: professeur.php");
        exit();
    }
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    $_SESSION['message'] = "❌ Erreur de connexion à la base de données: " . $mysqli->connect_error;
    header("Location: professeur.php");
    exit();
}

// Configuration du charset
$mysqli->set_charset("utf8");

try {
    // Insertion du devoir
    $sql = "INSERT INTO devoirs (classe, titre, contenu, matiere, date_limite, fichier, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $mysqli->error);
    }
    
    if (!$stmt->bind_param("ssssss", $classe, $titre, $contenu, $matiere, $date_limite, $fichier_nom)) {
        throw new Exception("Erreur de binding des paramètres: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de l'exécution: " . $stmt->error);
    }
    
    $devoir_id = $mysqli->insert_id;
    $stmt->close();
    
    // 🔔 NOTIFICATIONS PUSH aux élèves de la classe
    if (file_exists('send_push_notification.php')) {
        require_once 'send_push_notification.php';
        
        $notification_title = "📚 Nouveau devoir - $matiere";
        $notification_body = "$titre (Classe $classe)";
        $notification_url = "/tous_devoirs.php";
        
        // Essayer d'abord une requête simple pour récupérer les élèves
        $sql_eleves = "SELECT DISTINCT eleve_username FROM notes WHERE classe = ?";
        
        $stmt_eleves = $mysqli->prepare($sql_eleves);
        
        if ($stmt_eleves) {
            if ($stmt_eleves->bind_param("s", $classe)) {
                if ($stmt_eleves->execute()) {
                    $result_eleves = $stmt_eleves->get_result();
                    
                    $notifications_sent = 0;
                    $total_students = 0;
                    
                    while ($row = $result_eleves->fetch_assoc()) {
                        $total_students++;
                        $username = $row['eleve_username'];
                        
                        // Vérifier si la fonction existe avant de l'appeler
                        if (function_exists('sendPushNotification')) {
                            if (sendPushNotification($username, $notification_title, $notification_body, $notification_url)) {
                                $notifications_sent++;
                            }
                        }
                    }
                    
                    $stmt_eleves->close();
                    
                    if ($total_students > 0) {
                        if ($notifications_sent > 0) {
                            $_SESSION['message'] = "✅ Devoir créé avec succès ! Notifications envoyées à $notifications_sent/$total_students élève(s) de la classe $classe.";
                        } else {
                            $_SESSION['message'] = "✅ Devoir créé avec succès ! $total_students élève(s) trouvé(s) mais aucune notification envoyée.";
                        }
                    } else {
                        $_SESSION['message'] = "✅ Devoir créé avec succès ! Aucun élève trouvé dans la classe $classe.";
                    }
                    
                    // Log pour debug
                    error_log("Devoir créé ID: $devoir_id, Classe: $classe, Élèves: $total_students, Notifications: $notifications_sent");
                } else {
                    error_log("Erreur execute() élèves: " . $stmt_eleves->error);
                    $_SESSION['message'] = "✅ Devoir créé avec succès (erreur lors de l'exécution de la requête élèves).";
                    $stmt_eleves->close();
                }
            } else {
                error_log("Erreur bind_param() élèves: " . $stmt_eleves->error);
                $_SESSION['message'] = "✅ Devoir créé avec succès (erreur de binding pour la requête élèves).";
                $stmt_eleves->close();
            }
        } else {
            error_log("Erreur prepare() élèves: " . $mysqli->error);
            $_SESSION['message'] = "✅ Devoir créé avec succès (erreur de préparation de la requête élèves: " . $mysqli->error . ").";
        }
    } else {
        $_SESSION['message'] = "✅ Devoir créé avec succès ! (Fichier send_push_notification.php non trouvé)";
    }
    header("Location: professeur.php");
exit;

} catch (Exception $e) {
    error_log("Erreur enregistrer_devoir: " . $e->getMessage());
    $_SESSION['message'] = "❌ Erreur lors de la création du devoir: " . $e->getMessage();
    
    // Supprimer le fichier en cas d'erreur
    if ($fichier_nom && file_exists($upload_dir . $fichier_nom)) {
        unlink($upload_dir . $fichier_nom);
    }
} finally {
    $mysqli->close();
}

header("Location: professeur.php");
exit();
?>