<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$target_dir = "photos/";

if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
    $file_tmp = $_FILES["photo"]["tmp_name"];
    $file_type = mime_content_type($file_tmp);

    // Types autorisés
    $allowed_types = ["image/jpeg", "image/png", "image/gif"];
    if (in_array($file_type, $allowed_types)) {
        // Déterminer l’extension
        $extension = match($file_type) {
            "image/jpeg" => ".jpg",
            "image/png" => ".png",
            "image/gif" => ".gif",
        };

        $target_file = $target_dir . $username . $extension;

        // Supprimer les anciennes versions
        foreach ([".jpg", ".png", ".gif"] as $ext) {
            $old_file = $target_dir . $username . $ext;
            if (file_exists($old_file)) unlink($old_file);
        }

        // Enregistrer la nouvelle photo
        move_uploaded_file($file_tmp, $target_file);
    }
}

// 🔁 Rediriger vers la page précédente automatiquement
$redirect = $_SERVER['HTTP_REFERER'] ?? 'eleve.php';
header("Location: " . $redirect . "?v=" . time());
exit;
?>
