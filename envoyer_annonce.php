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
    header("Location: admin.php");
    exit();
}

// Récupération des données du formulaire
$titre = trim($_POST['titre'] ?? '');
$contenu = trim($_POST['contenu'] ?? '');

// Validation des données
if (empty($titre) || empty($contenu)) {
    $_SESSION['message'] = "❌ Le titre et le contenu sont obligatoires.";
    header("Location: admin.php");
    exit();
}

// Gestion du fichier joint
$fichier_nom = null;
if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $fichier_tmp = $_FILES['fichier']['tmp_name'];
    $fichier_nom = time() . '_' . basename($_FILES['fichier']['name']);
    $fichier_path = $upload_dir . $fichier_nom;
    
    // Vérifications de sécurité
    $extensions_autorisees = ['pdf', 'doc', 'docx', 'jpg', 'png', 'txt'];
    $extension = strtolower(pathinfo($fichier_nom, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensions_autorisees)) {
        $_SESSION['message'] = "❌ Type de fichier non autorisé.";
        header("Location: admin.php");
        exit();
    }
    
    if ($_FILES['fichier']['size'] > 10 * 1024 * 1024) { // 10MB max
        $_SESSION['message'] = "❌ Le fichier est trop volumineux (max 10MB).";
        header("Location: admin.php");
        exit();
    }
    
    if (!move_uploaded_file($fichier_tmp, $fichier_path)) {
        $_SESSION['message'] = "❌ Erreur lors de l'upload du fichier.";
        header("Location: admin.php");
        exit();
    }
}

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    $_SESSION['message'] = "❌ Erreur de connexion à la base de données.";
    header("Location: admin.php");
    exit();
}

try {
    // Insertion de l'annonce
    $stmt = $mysqli->prepare("INSERT INTO annonces (titre, contenu, fichier, date_envoi) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $titre, $contenu, $fichier_nom);
    
    if ($stmt->execute()) {
        $annonce_id = $mysqli->insert_id;
        
        // 🔔 NOTIFICATIONS PUSH avec Composer
        require_once 'send_push_notification.php';
        
        $notification_title = "📢 Nouvelle annonce";
        $notification_body = $titre;
        $notification_url = "/annonces.php";
        
        // Envoyer à tous les élèves
        $push_success = sendPushToAllStudents($notification_title, $notification_body, $notification_url);
        
        if ($push_success) {
            $_SESSION['message'] = "✅ Annonce envoyée avec succès et notifications push envoyées !";
        } else {
            $_SESSION['message'] = "✅ Annonce envoyée avec succès (notifications push partiellement envoyées).";
        }
        
        // Log pour debug
        error_log("Annonce créée ID: $annonce_id, Push: " . ($push_success ? 'OK' : 'PARTIAL'));
        
    } else {
        throw new Exception("Erreur lors de l'insertion en base de données");
    }
    
} catch (Exception $e) {
    error_log("Erreur envoyer_annonce: " . $e->getMessage());
    $_SESSION['message'] = "❌ Erreur lors de l'envoi de l'annonce.";
    
    // Supprimer le fichier en cas d'erreur
    if ($fichier_nom && file_exists($upload_dir . $fichier_nom)) {
        unlink($upload_dir . $fichier_nom);
    }
} finally {
    $mysqli->close();
}

header("Location: admin.php");
exit();
?>