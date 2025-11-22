<?php
$page_title = "Notre Équipe";
$page_description = "Rencontrez l'équipe dirigeante et les responsables de SOCOU_U qui œuvrent quotidiennement pour le succès de la coopérative";
require_once 'includes/header.php';
//include 'config/database.php';
// Connexion à la base de données pour récupérer les informations de l'équipe
try {
    $conn = getConnection();
    $stmt = $conn->query("
        SELECT u.*, m.nom, m.prenom, m.email, m.telephone, m.photo, m.specialisation
        FROM utilisateurs u 
        JOIN membres m ON u.id_membre = m.id_membre 
        WHERE u.role IN ('admin', 'gestionnaire') AND u.statut = 'actif'
        ORDER BY 
            CASE u.role 
                WHEN 'admin' THEN 1 
                WHEN 'gestionnaire' THEN 2 
                ELSE 3 
            END
    ");
    $equipe = $stmt->fetchAll();
} catch(PDOException $e) {
    $equipe = [];
}
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold text-primary mb-3">Notre Équipe</h1>
            <p class="lead">
                Rencontrez les hommes et femmes passionnés qui dirigent SOCOU_U et œuvrent 
                quotidiennement pour le développement de notre coopérative et l'épanouissement 
                de nos membres.
            </p>
        </div>
        <div class="col-lg-6">
            <img src="<?php echo SITE_URL; ?>/assets/images/equipe-cooperative.jpg" 
                 alt="Équipe SOCOU_U" class="img-fluid rounded shadow">
        </div>
    </div>

    <!-- Message du Président -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img src="<?php echo SITE_URL; ?>/assets/images/president.jpg" 
                                 alt="Président SOCOU_U" 
                                 class="img-fluid rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <h3 class="fw-bold mb-3">Message du Conseil d'Administration</h3>
                            <blockquote class="blockquote">
                                <p class="mb-3">
                                    "SOCOU_U est bien plus qu'une coopérative, c'est une famille unie par des valeurs 
                                    communes et une vision partagée. Chaque membre de notre équipe apporte ses compétences 
                                    et sa passion pour faire avancer notre mission de développement durable et d'autonomisation 
                                    des communautés rurales."
                                </p>
                                <footer class="blockquote-footer">
                                    <cite title="Source Title">Représentant du Conseil d'Administration</cite>
                                </footer>
                            </blockquote>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Structure organisationnelle -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Structure Organisationnelle</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-users text-white fa-2x"></i>
                            </div>
                            <h5 class="card-title text-primary">Assemblée Générale</h5>
                            <p class="card-text">
                                Organe suprême de décision composé de tous les membres de la coopérative. 
                                Elle définit les orientations stratégiques et élit les dirigeants.
                            </p>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-user-friends me-1"></i>200+ membres
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-gavel text-white fa-2x"></i>
                            </div>
                            <h5 class="card-title text-success">Conseil d'Administration</h5>
                            <p class="card-text">
                                Élu par l'Assemblée Générale, il définit la politique générale de la coopérative 
                                et contrôle la gestion quotidienne.
                            </p>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-user-tie me-1"></i>7 membres élus
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-cogs text-white fa-2x"></i>
                            </div>
                            <h5 class="card-title text-warning">Direction Exécutive</h5>
                            <p class="card-text">
                                Assure la gestion opérationnelle quotidienne de la coopérative et met en œuvre 
                                les décisions du Conseil d'Administration.
                            </p>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-user-cog me-1"></i>Équipe technique
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Équipe dirigeante -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Équipe Dirigeante</h2>
            
            <?php if (!empty($equipe)): ?>
                <div class="row">
                    <?php foreach($equipe as $membre): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <?php if (!empty($membre['photo'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/membres/<?php echo escape($membre['photo']); ?>" 
                                                 alt="<?php echo escape($membre['prenom'] . ' ' . $membre['nom']); ?>"
                                                 class="rounded-circle img-fluid"
                                                 style="width: 120px; height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                                 style="width: 120px; height: 120px;">
                                                <i class="fas fa-user fa-3x text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h5 class="card-title mb-1">
                                        <?php echo escape($membre['prenom'] . ' ' . $membre['nom']); ?>
                                    </h5>
                                    
                                    <p class="text-primary mb-2">
                                        <?php 
                                        echo $membre['role'] == 'admin' ? 'Administrateur' : 'Gestionnaire';
                                        ?>
                                    </p>
                                    
                                    <?php if (!empty($membre['specialisation'])): ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo escape($membre['specialisation']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <?php if (!empty($membre['email'])): ?>
                                            <a href="mailto:<?php echo escape($membre['email']); ?>" 
                                               class="btn btn-outline-primary btn-sm me-2 mb-1">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($membre['telephone'])): ?>
                                            <a href="tel:<?php echo escape($membre['telephone']); ?>" 
                                               class="btn btn-outline-success btn-sm mb-1">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Équipe par défaut si pas de données en BDD -->
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="bg-primary rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 120px; height: 120px;">
                                        <i class="fas fa-user-tie fa-3x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title mb-1">Direction Générale</h5>
                                <p class="text-primary mb-2">Administrateur Principal</p>
                                <p class="text-muted small mb-3">
                                    Coordination générale et supervision stratégique
                                </p>
                                <div class="mt-3">
                                    <a href="mailto:direction@socou-u.bi" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="bg-success rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 120px; height: 120px;">
                                        <i class="fas fa-chart-line fa-3x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title mb-1">Gestion Commerciale</h5>
                                <p class="text-success mb-2">Responsable Commercial</p>
                                <p class="text-muted small mb-3">
                                    Marketing, ventes et relations clientèle
                                </p>
                                <div class="mt-3">
                                    <a href="mailto:commercial@socou-u.bi" class="btn btn-outline-success btn-sm me-2">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="bg-warning rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 120px; height: 120px;">
                                        <i class="fas fa-users fa-3x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title mb-1">Projets Sociaux</h5>
                                <p class="text-warning mb-2">Coordinateur Social</p>
                                <p class="text-muted small mb-3">
                                    Développement communautaire et formation
                                </p>
                                <div class="mt-3">
                                    <a href="mailto:social@socou-u.bi" class="btn btn-outline-warning btn-sm me-2">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Départements -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="text-center mb-5">Nos Départements</h3>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-seedling text-white fa-lg"></i>
                            </div>
                            <h6 class="fw-bold">Production Agricole</h6>
                            <p class="small text-muted mb-0">
                                Accompagnement des producteurs, techniques modernes et qualité des produits.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-industry text-white fa-lg"></i>
                            </div>
                            <h6 class="fw-bold">Transformation</h6>
                            <p class="small text-muted mb-0">
                                Valorisation des produits bruts et développement de la chaîne de valeur.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-store text-white fa-lg"></i>
                            </div>
                            <h6 class="fw-bold">Commercialisation</h6>
                            <p class="small text-muted mb-0">
                                Distribution, vente et développement des marchés locaux et internationaux.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="bg-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-graduation-cap text-white fa-lg"></i>
                            </div>
                            <h6 class="fw-bold">Formation</h6>
                            <p class="small text-muted mb-0">
                                Renforcement des capacités et développement des compétences des membres.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejoindre l'équipe -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white text-center p-5">
                    <h3 class="fw-bold mb-3">
                        <i class="fas fa-handshake me-2"></i>Rejoignez Notre Équipe
                    </h3>
                    <p class="lead mb-4">
                        Vous partagez notre vision du développement coopératif ? 
                        Vous souhaitez contribuer à l'autonomisation des communautés rurales ?
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <p class="mb-4">
                                SOCOU_U recherche constamment des talents passionnés pour renforcer ses équipes. 
                                Que vous soyez spécialiste en agriculture, commerce, gestion, ou développement social, 
                                votre expertise peut faire la différence.
                            </p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="<?php echo SITE_URL; ?>/inscription.php" class="btn btn-light btn-lg me-md-3 mb-2 mb-md-0">
                                    <i class="fas fa-user-plus me-2"></i>Devenir Membre
                                </a>
                                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-envelope me-2"></i>Nous Contacter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.3s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.img-fluid {
    transition: all 0.3s ease;
}

.card:hover .img-fluid {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .display-4 {
        font-size: 2.5rem;
    }
    
    .lead {
        font-size: 1.1rem;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>