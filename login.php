<?php
session_start();

// Récupérer les erreurs de session s'il y en a
$login_error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
$login_error_message = isset($_SESSION['login_error_message']) ? $_SESSION['login_error_message'] : null;

// Nettoyer les erreurs de la session après les avoir récupérées
if (isset($_SESSION['login_error'])) {
    unset($_SESSION['login_error']);
    unset($_SESSION['login_error_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion EduConnect</title>
  <link rel="manifest" href="/manifest.json" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="login.css">
  <meta name="theme-color" content="#2196F3">
  <meta name="description" content="Connexion sécurisée à votre espace éducatif EduConnect">
</head>
<body>
  <div class="login-container">
    <div class="logo-container">
      <div class="logo">
        <i class="fas fa-graduation-cap"></i>
        EduConnect
      </div>
      <p class="subtitle">Connectez-vous à votre espace éducatif</p>
    </div>

    <h2>Connexion à EduConnect</h2>
    
    <!-- Zone d'affichage des erreurs -->
    <div id="error-container" style="display: none;"></div>
    
    <?php if ($login_error): ?>
    <div class="error-message" id="phpError">
      <i class="fas fa-exclamation-triangle"></i>
      <span><?php echo htmlspecialchars($login_error_message); ?></span>
    </div>
    <?php endif; ?>
    
    <form action="auth.php" method="POST">
      <div class="form-group">
        <label for="username">
          <i class="fas fa-user"></i> Nom d'utilisateur :
        </label>
        <div class="input-container">
          <input type="text" id="username" name="username" required placeholder="Entrez votre nom d'utilisateur" autocomplete="username">
          <i class="fas fa-user input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label for="password">
          <i class="fas fa-lock"></i> Mot de passe :
        </label>
        <div class="input-container">
          <input type="password" id="password" name="password" required placeholder="Entrez votre mot de passe" autocomplete="current-password">
          <i class="fas fa-lock input-icon"></i>
          <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Afficher/masquer le mot de passe">
            <i class="fas fa-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn" id="loginBtn">
        <i class="fas fa-sign-in-alt"></i> Se connecter
      </button>
    </form>
  </div>

  <script src="login.js"></script>

  <!-- ✅ Enregistrement du service worker -->
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log("✅ Service Worker enregistré"))
        .catch(err => console.error("❌ Erreur Service Worker :", err));
    }
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Gestion des erreurs PHP
      <?php if ($login_error): ?>
        handleLoginError('<?php echo $login_error; ?>', '<?php echo addslashes($login_error_message); ?>');
      <?php endif; ?>
    });
  </script>
</body>
</html>