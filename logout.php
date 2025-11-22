<?php
require_once 'config/config.php';
include 'includes/functions.php';
// Vérifier si l'utilisateur est connecté
if (isLoggedIn()) {
    // Logger la déconnexion
    logActivity('deconnexion', 'utilisateurs', $_SESSION['user_id'], 'Déconnexion');
    
    // Détruire la session
    session_destroy();
    
    // Supprimer le cookie "remember me" s'il existe
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Message de confirmation
    session_start();
    setMessage('Vous avez été déconnecté avec succès.', 'success');
}

// Rediriger vers la page de connexion
header('Location: ' . SITE_URL . '/login.php');
exit();
?>