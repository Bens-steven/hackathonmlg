<?php
// Fichier de vérification d'accès basé sur les groupes AD
function checkAccess($required_groups, $redirect_page = 'login.php') {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['username']) || !isset($_SESSION['groups'])) {
        header("Location: $redirect_page");
        exit();
    }
    
    $user_groups = $_SESSION['groups'];
    
    // Vérifier si l'utilisateur appartient à au moins un des groupes requis
    $has_access = false;
    foreach ($required_groups as $required_group) {
        if (in_array($required_group, $user_groups)) {
            $has_access = true;
            break;
        }
    }
    
    // Si pas d'accès, rediriger vers la page appropriée selon le groupe
    if (!$has_access) {
        redirectToCorrectPage($user_groups);
    }
}

function redirectToCorrectPage($user_groups) {
    // Redirection intelligente selon le groupe de l'utilisateur
    if (in_array("G_Admin_Direction", $user_groups)) {
        header("Location: directrice.php");
    } elseif (in_array("G_Tous_Personnel_Admin", $user_groups)) {
        header("Location: admin.php");
    } elseif (in_array("G_Tous_Professeurs", $user_groups)) {
        header("Location: professeur.php");
    } elseif (in_array("G_Tous_Eleves", $user_groups)) {
        header("Location: eleve.php");
    } else {
        // Si aucun groupe reconnu, déconnecter
        session_destroy();
        header("Location: login.php");
    }
    exit();
}

function showAccessDenied($user_groups) {
    $correct_page = "";
    if (in_array("G_Admin_Direction", $user_groups)) {
        $correct_page = "directrice.php";
    } elseif (in_array("G_Tous_Personnel_Admin", $user_groups)) {
        $correct_page = "admin.php";
    } elseif (in_array("G_Tous_Professeurs", $user_groups)) {
        $correct_page = "professeur.php";
    } elseif (in_array("G_Tous_Eleves", $user_groups)) {
        $correct_page = "eleve.php";
    }
    
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Accès refusé</title>
        <link rel='stylesheet' href='styleeleve.css'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body>
        <div class='container'>
            <div class='card' style='max-width: 600px; margin: 5rem auto; text-align: center;'>
                <div class='card-body'>
                    <div style='color: #ef4444; font-size: 4rem; margin-bottom: 2rem;'>
                        <i class='fas fa-ban'></i>
                    </div>
                    <h2 style='color: #ef4444; margin-bottom: 1rem;'>Accès refusé</h2>
                    <p style='color: #6b7280; margin-bottom: 2rem; font-size: 1.1rem;'>
                        Vous n'avez pas les permissions nécessaires pour accéder à cette page.
                    </p>
                    <div style='display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;'>
                        <a href='$correct_page' class='btn btn-primary'>
                            <i class='fas fa-home'></i> Aller à ma page d'accueil
                        </a>
                        <a href='logout.php' class='btn btn-secondary'>
                            <i class='fas fa-sign-out-alt'></i> Se déconnecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
    exit();
}
?>