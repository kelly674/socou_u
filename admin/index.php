<?php
$page_title = "Administration";
include '../config/config.php';
include '../includes/functions.php';
requireRole('admin');
require_once '../includes/header.php';

// Statistiques générales
try {
     $pdo = getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM membres WHERE statut = 'actif'");
    $total_membres = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'confirmee'");
    $total_commandes = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM projets_sociaux WHERE statut = 'en_cours'");
    $total_projets = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM formations WHERE statut = 'programmee'");
    $total_formations = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $total_membres = 0;
    $total_commandes = 0;
    $total_projets = 0;
    $total_formations = 0;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>Administration
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/admin/" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/membres.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Membres
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/produits.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box me-2"></i>Produits
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/commandes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/projets.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Projets Sociaux
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/formations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i>Formations
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/actualites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-newspaper me-2"></i>Actualités
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/galerie.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-images me-2"></i>Galerie
                    </a>
                    <?php if (hasRole('admin')): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/utilisateurs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-shield me-2"></i>Utilisateurs
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/configurations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i>Configuration
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête -->
            <div class="row mb-4">
                <div class="col">
                    <h2>Tableau de Bord</h2>
                    <p class="text-muted">Vue d'ensemble de l'activité de SOCOU_U</p>
                </div>
            </div>
            
            <!-- Cartes de statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $total_membres; ?></h3>
                                    <p class="mb-0">Membres Actifs</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/membres.php" class="text-white text-decoration-none">
                                <small>Voir détails <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $total_commandes; ?></h3>
                                    <p class="mb-0">Commandes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/commandes.php" class="text-white text-decoration-none">
                                <small>Voir détails <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $total_projets; ?></h3>
                                    <p class="mb-0">Projets en Cours</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-heart fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/projets.php" class="text-white text-decoration-none">
                                <small>Voir détails <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3><?php echo $total_formations; ?></h3>
                                    <p class="mb-0">Formations Prévues</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/formations.php" class="text-white text-decoration-none">
                                <small>Voir détails <i class="fas fa-arrow-right"></i></small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activité récente -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Activité Récente</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6>Nouveau membre inscrit</h6>
                                        <p class="text-muted mb-1">Marie UWIMANA s'est inscrite en tant que productrice</p>
                                        <small class="text-muted">Il y a 2 heures</small>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6>Commande confirmée</h6>
                                        <p class="text-muted mb-1">Commande #CMD001 confirmée pour 50kg de haricots</p>
                                        <small class="text-muted">Il y a 4 heures</small>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <h6>Formation planifiée</h6>
                                        <p class="text-muted mb-1">Formation "Techniques de conservation" programmée pour demain</p>
                                        <small class="text-muted">Il y a 1 jour</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions Rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/admin/membres.php?action=add" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Ajouter un membre
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/produits.php?action=add" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-2"></i>Ajouter un produit
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/formations.php?action=add" class="btn btn-info btn-sm">
                                    <i class="fas fa-plus me-2"></i>Programmer une formation
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/actualites.php?action=add" class="btn btn-warning btn-sm">
                                    <i class="fas fa-plus me-2"></i>Publier une actualité
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Informations Système</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary">v1.0</h4>
                                    <small>Version</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success">99%</h4>
                                    <small>Uptime</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>

<?php require_once '../includes/footer.php'; ?>