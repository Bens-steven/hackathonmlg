<?php
session_start();

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['username'])) {
    if ($is_ajax) {
        echo "❌ Session invalide.";
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

if (!isset($_POST['devoir_id']) || !isset($_FILES['fichier_rendu'])) {
    $msg = "❌ Données manquantes pour rendre le devoir.";
    if ($is_ajax) {
        echo $msg;
        exit;
    } else {
        $_SESSION['message'] = $msg;
        header("Location: eleve.php");
        exit;
    }
}

$username = $_SESSION['username'];
$devoir_id = $_POST['devoir_id'];

$mysqli = new mysqli("localhost", "root", "Basique12345", "educonnect");
if ($mysqli->connect_error) {
    echo "❌ Erreur connexion MySQL: " . $mysqli->connect_error;
    exit;
}

$stmt = $mysqli->prepare("SELECT titre, date_limite FROM devoirs WHERE id = ?");
$stmt->bind_param("i", $devoir_id);
$stmt->execute();
$result = $stmt->get_result();
$devoir = $result->fetch_assoc();
$stmt->close();

if (!$devoir) {
    $msg = "❌ Devoir non trouvé.";
    if ($is_ajax) {
        echo $msg;
    } else {
        $_SESSION['message'] = $msg;
        header("Location: eleve.php");
    }
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM rendus_devoirs WHERE devoir_id = ? AND eleve_username = ?");
$stmt->bind_param("is", $devoir_id, $username);
$stmt->execute();
$result = $stmt->get_result();
$existing_rendu = $result->fetch_assoc();
$stmt->close();

if ($existing_rendu) {
    $msg = "❌ Vous avez déjà rendu ce devoir.";
    if ($is_ajax) {
        echo $msg;
    } else {
        $_SESSION['message'] = $msg;
        header("Location: eleve.php");
    }
    exit;
}

$fichier_nom = null;
if (isset($_FILES['fichier_rendu']) && $_FILES['fichier_rendu']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "rendus_eleves/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if ($_FILES['fichier_rendu']['size'] > 10 * 1024 * 1024) {
        echo "❌ Le fichier est trop volumineux (max 10MB).";
        exit;
    }

    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png', 'zip'];
    $file_extension = strtolower(pathinfo($_FILES['fichier_rendu']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        echo "❌ Type de fichier non autorisé.";
        exit;
    }

    $fichier_nom = $username . "_devoir" . $devoir_id . "_" . uniqid() . "." . $file_extension;
    $chemin_complet = $upload_dir . $fichier_nom;

    if (!move_uploaded_file($_FILES['fichier_rendu']['tmp_name'], $chemin_complet)) {
        echo "❌ Erreur lors du téléchargement du fichier.";
        exit;
    }
} else {
    echo "❌ Aucun fichier sélectionné ou erreur de téléchargement.";
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO rendus_devoirs (devoir_id, eleve_username, fichier_rendu) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $devoir_id, $username, $fichier_nom);

if ($stmt->execute()) {
    $is_late = new DateTime() > new DateTime($devoir['date_limite']);
    $msg = $is_late ?
        "⚠️ Devoir rendu en retard avec succès." :
        "✅ Devoir rendu avec succès.";
    if ($is_ajax) {
        echo $msg;
    } else {
        $_SESSION['message'] = $msg;
        $redirect_page = isset($_POST['redirect']) ? $_POST['redirect'] : 'eleve.php';
        header("Location: " . $redirect_page);
    }
} else {
    if (file_exists($chemin_complet)) {
        unlink($chemin_complet);
    }
    echo "❌ Erreur lors de l'enregistrement du rendu.";
}

$stmt->close();
$mysqli->close();
exit;
?>
