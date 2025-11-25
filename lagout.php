<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php'; // Fichier de configuration de la base de données
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>