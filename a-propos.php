<?php
$page_title = "Notre Histoire";
$page_description = "Découvrez l'histoire et l'évolution de la Société Coopérative UMUSHINGE W'UBUZIMA depuis sa création en 2019";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold text-primary mb-3">Notre Histoire</h1>
            <p class="lead">
                Découvrez l'histoire inspirante de SOCOU_U, une coopérative née de la vision partagée 
                d'améliorer les conditions de vie des communautés rurales burundaises.
            </p>
        </div>
        <div class="col-lg-6">
            <img src="<?php echo SITE_URL; ?>/assets/images/histoire-cooperative.jpg" 
                 alt="Histoire de SOCOU_U" class="img-fluid rounded shadow">
        </div>
    </div>

    <!-- Timeline de l'histoire -->
    <div class="row">
        <div class="col-12">
            <h2 class="text-center mb-5">Notre Parcours</h2>
            
            <div class="timeline">
                <!-- 2019 - Fondation -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-primary">2019 - La Fondation</h4>
                                <p class="card-text">
                                    La société Coopérative UMUSHINGE W'UBUZIMA (SOCOU_U) a été fondée par un groupe 
                                    de membres fondateurs visionnaires dans le but d'améliorer les conditions de vie 
                                    des populations locales. Cette initiative s'inscrit dans le cadre des politiques 
                                    nationales de développement coopératif et de lutte contre la pauvreté.
                                </p>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="stat-card text-center">
                                            <h5 class="text-primary">15</h5>
                                            <small>Membres fondateurs</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card text-center">
                                            <h5 class="text-primary">3</h5>
                                            <small>Secteurs d'activité</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card text-center">
                                            <h5 class="text-primary">1</h5>
                                            <small>Vision commune</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2020 - Premiers projets -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-success">2020 - Premiers Projets</h4>
                                <p class="card-text">
                                    Lancement des premiers programmes de formation agricole et mise en place 
                                    du système de commercialisation collective. Début de la structuration 
                                    des activités de transformation des produits agropastoraux.
                                </p>
                                <ul class="list-unstyled mt-3">
                                    <li><i class="fas fa-check text-success me-2"></i>Formation de 50 producteurs</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Première récolte collective</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Création du fonds d'entraide</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2021-2022 - Expansion -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-warning"></div>
                    <div class="timeline-content">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-warning">2021-2022 - Croissance et Diversification</h4>
                                <p class="card-text">
                                    Expansion géographique et diversification des activités. Introduction de nouvelles 
                                    techniques agricoles et développement des partenariats avec les institutions 
                                    nationales et internationales.
                                </p>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Réalisations :</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>100+ membres actifs</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>5 zones d'intervention</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>Création d'emplois locaux</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Innovations :</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>Techniques modernes</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>Système de traçabilité</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>Formation continue</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2023-2024 - Digitalisation -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-info"></div>
                    <div class="timeline-content">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-info">2023-2024 - Transformation Digitale</h4>
                                <p class="card-text">
                                    Intégration progressive des TIC dans les processus de gestion et de commercialisation. 
                                    Développement de plateformes numériques pour améliorer l'efficacité opérationnelle 
                                    et faciliter l'accès aux marchés.
                                </p>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Innovation technologique :</strong> Lancement de la plateforme digitale 
                                    pour la gestion des membres et la commercialisation en ligne.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2025 - Aujourd'hui -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-primary">2025 - Aujourd'hui</h4>
                                <p class="card-text">
                                    Consolidation des acquis et préparation de la prochaine phase de développement. 
                                    SOCOU_U s'affirme comme un acteur incontournable du développement économique local 
                                    et un modèle de coopérative moderne au Burundi.
                                </p>
                                <div class="row text-center">
                                    <div class="col-6 col-md-3">
                                        <div class="stat-highlight">
                                            <h3 class="text-primary">200+</h3>
                                            <p class="small mb-0">Membres actifs</p>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="stat-highlight">
                                            <h3 class="text-success">50+</h3>
                                            <p class="small mb-0">Produits</p>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="stat-highlight">
                                            <h3 class="text-warning">15+</h3>
                                            <p class="small mb-0">Projets sociaux</p>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="stat-highlight">
                                            <h3 class="text-info">1000+</h3>
                                            <p class="small mb-0">Bénéficiaires</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nos Valeurs -->
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="text-center mb-5">Nos Valeurs Fondamentales</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-handshake fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Solidarité</h5>
                            <p class="card-text">
                                Nous croyons en l'entraide mutuelle et en la force du collectif 
                                pour surmonter les défis et atteindre nos objectifs communs.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-seedling fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Développement Durable</h5>
                            <p class="card-text">
                                Nous œuvrons pour un développement qui respecte l'environnement 
                                et assure la prospérité des générations futures.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Autonomie</h5>
                            <p class="card-text">
                                Nous encourageons l'autonomisation de nos membres à travers 
                                la formation, l'accompagnement et le renforcement des capacités.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Témoignage du fondateur -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <blockquote class="blockquote text-center">
                        <p class="mb-4">
                            <i class="fas fa-quote-left text-primary me-2"></i>
                            "SOCOU_U représente plus qu'une simple coopérative. C'est un rêve devenu réalité, 
                            une communauté unie par la vision d'un avenir meilleur pour tous. Chaque membre 
                            apporte sa pierre à l'édifice de notre succès collectif."
                            <i class="fas fa-quote-right text-primary ms-2"></i>
                        </p>
                        <footer class="blockquote-footer">
                            <strong>Représentant des Membres Fondateurs</strong>
                            <cite title="Source Title">SOCOU_U</cite>
                        </footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
}

.timeline::after {
    content: '';
    position: absolute;
    width: 4px;
    background-color: var(--bs-primary);
    top: 0;
    bottom: 0;
    left: 50%;
    margin-left: -2px;
}

.timeline-item {
    padding: 20px 40px;
    position: relative;
    background-color: inherit;
    width: 50%;
    margin-bottom: 30px;
}

.timeline-item:nth-child(odd) {
    left: 0;
    padding-right: 60px;
}

.timeline-item:nth-child(even) {
    left: 50%;
    padding-left: 60px;
}

.timeline-marker {
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    top: 15px;
    z-index: 1;
}

.timeline-item:nth-child(odd) .timeline-marker {
    right: -10px;
}

.timeline-item:nth-child(even) .timeline-marker {
    left: -10px;
}

.stat-card {
    padding: 1rem;
    background: rgba(0,123,255,0.1);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.stat-highlight {
    padding: 1rem;
    border-radius: 8px;
    background: rgba(255,255,255,0.8);
}

@media screen and (max-width: 768px) {
    .timeline::after {
        left: 31px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 70px;
        padding-right: 25px;
    }
    
    .timeline-item:nth-child(even) {
        left: 0%;
    }
    
    .timeline-marker {
        left: 21px !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>