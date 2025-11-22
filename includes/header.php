<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- Titre avec emoji optionnel -->
    <title><?php echo '' . (isset($page_title) ? escape($page_title) . ' - ' . SITE_NAME : SITE_TITLE); ?></title>
    
    <!-- Favicon pour l'onglet du navigateur -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/socou_u.png">
    <meta name="description" content="<?php echo isset($page_description) ? escape($page_description) : 'Société Coopérative UMUSHINGE W\'UBUZIMA - Solidarité, Autonomie et Développement Durable'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
  
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
           <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
    <img src="<?php echo SITE_URL; ?>/assets/images/socou_u.png" alt="SOCOU_U" height="40" class="d-inline-block align-text-top me-2" style="border-radius: 50%;">
    <strong>SOCOU_U</strong>
</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Accueil</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-info-circle"></i> À propos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/a-propos.php">Notre Histoire</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/mission.php">Mission & Vision</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/equipe.php">Notre Équipe</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/actualites.php"><i class="fas fa-newspaper"></i> Actualités</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cogs"></i> Services
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/produits.php">Nos Produits</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/formations.php">Formations</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/projets.php">Projets Sociaux</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/galerie.php"><i class="fas fa-images"></i> Galerie</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-envelope"></i> Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo escape($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profil.php">Mon Profil</a></li>
                                <?php if (hasRole('admin')): ?>
    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/">Administration</a></li>
<?php elseif (hasRole('gestionnaire')): ?>
    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/gestionnaire/">Administration</a></li>
<?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/inscription.php"><i class="fas fa-user-plus"></i> Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Espace pour la navigation fixe -->
    <div style="height: 76px;"></div>

    <!-- Messages d'alerte -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                echo escape($_SESSION['message']); 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>