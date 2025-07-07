<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}
echo "<h2>Bienvenue " . htmlspecialchars($_SESSION['username']) . "</h2>";
echo "<p>Groupes : " . implode(', ', $_SESSION['groups']) . "</p>";
echo "<p><a href='logout.php'>Déconnexion</a></p>";
?>