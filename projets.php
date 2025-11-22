<?php
$page_title = "Projets Sociaux";
$page_description = "Découvrez les projets de développement communautaire et d'impact social menés par SOCOU_U";
require_once 'includes/header.php';

// Récupération des projets
$statut_filtre = isset($_GET['statut']) ? $_GET['statut'] : '';

try {
     $pdo = getConnection();
    // Construction de la requête
    $where_clause = "WHERE 1=1";
    if ($statut_filtre && in_array($statut_filtre, ['planifie', 'en_cours', 'termine', 'suspendu'])) {
        $where_clause .= " AND p.statut = :statut";
    }
    
    $query = "
        SELECT p.*, 
               m.nom as responsable_nom, m.prenom as responsable_prenom,
               COUNT(b.id_beneficiaire) as nb_beneficiaires
        FROM projets_sociaux p
        LEFT JOIN utilisateurs u ON p.responsable_id = u.id_utilisateur
        LEFT JOIN membres m ON u.id_membre = m.id_membre
        LEFT JOIN beneficiaires_projets b ON p.id_projet = b.id_projet AND b.statut_participation IN ('inscrit', 'actif')
        $where_clause
        GROUP BY p.id_projet
        ORDER BY 
            CASE p.statut 
                WHEN 'en_cours' THEN 1 
                WHEN 'planifie' THEN 2 
                WHEN 'termine' THEN 3 
                WHEN 'suspendu' THEN 4 
            END,
            p.date_debut DESC
    ";
    
    $stmt = $pdo->prepare($query);
    if ($statut_filtre) {
        $stmt->bindParam(':statut', $statut_filtre);
    }
    $stmt->execute();
    $projets = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $projets = [];
}

// Données par défaut si la base est vide
if (empty($projets)) {
    $projets_defaut = [
        [
            'id_projet' => 1,
            'nom_projet' => 'Programme d\'Autonomisation des Femmes Rurales',
            'description' => 'Programme visant à renforcer les capacités des femmes rurales dans l\'entrepreneuriat agricole et la transformation des produits.',
            'objectif' => 'Former 100 femmes aux techniques de transformation et leur fournir un accompagnement pour créer leurs micro-entreprises.',
            'resultats_attendus' => '100 femmes formées, 50 micro-entreprises créées, amélioration des revenus de 60%',
            'date_debut' => '2024-01-15',
            'date_fin' => '2025-12-31',
            'budget_previsto' => 25000000,
            'budget_depense' => 15000000,
            'statut' => 'en_cours',
            'beneficiaires_cibles' => 100,
            'zone_intervention' => 'Communes Mukaza, Muha, Ntahangwa',
            'partenaires' => 'ONU Femmes, Ministère du Genre',
            'responsable_nom' => 'Coordination',
            'responsable_prenom' => 'Équipe',
            'nb_beneficiaires' => 75,
            'image_illustration' => 'femmes-rurales.jpg'
        ],
        [
            'id_projet' => 2,
            'nom_projet' => 'Amélioration de la Sécurité Alimentaire',
            'description' => 'Projet de distribution de semences améliorées et formation aux techniques agricoles durables pour lutter contre l\'insécurité alimentaire.',
            'objectif' => 'Améliorer la production agricole de 500 ménages vulnérables et assurer leur sécurité alimentaire.',
            'resultats_attendus' => '500 ménages bénéficiaires, augmentation de la production de 50%, réduction de l\'insécurité alimentaire',
            'date_debut' => '2024-03-01',
            'date_fin' => '2025-02-28',
            'budget_previsto' => 35000000,
            'budget_depense' => 8000000,
            'statut' => 'en_cours',
            'beneficiaires_cibles' => 500,
            'zone_intervention' => 'Province Bujumbura Rural',
            'partenaires' => 'PAM, FAO, Gouvernement du Burundi',
            'responsable_nom' => 'Développement',
            'responsable_prenom' => 'Équipe',
            'nb_beneficiaires' => 320,
            'image_illustration' => 'securite-alimentaire.jpg'
        ],
        [
            'id_projet' => 3,
            'nom_projet' => 'Formation des Jeunes Agriculteurs',
            'description' => 'Programme de formation et d\'accompagnement des jeunes dans l\'agriculture moderne et l\'entrepreneuriat agricole.',
            'objectif' => 'Former 200 jeunes aux techniques agricoles modernes et les accompagner dans la création d\'entreprises agricoles.',
            'resultats_attendus' => '200 jeunes formés, 100 entreprises agricoles créées, création de 500 emplois',
            'date_debut' => '2024-06-01',
            'date_fin' => '2026-05-31',
            'budget_previsto' => 18000000,
            'budget_depense' => 3000000,
            'statut' => 'planifie',
            'beneficiaires_cibles' => 200,
            'zone_intervention' => 'Toutes les communes de Bujumbura',
            'partenaires' => 'UNICEF, Ministère de la Jeunesse',
            'responsable_nom' => 'Formation',
            'responsable_prenom' => 'Équipe',
            'nb_beneficiaires' => 45,
            'image_illustration' => 'jeunes-agriculteurs.jpg'
        ]
    ];
    
    // Filtrer par statut si nécessaire
    if ($statut_filtre) {
        $projets_defaut = array_filter($projets_defaut, function($p) use ($statut_filtre) {
            return $p['statut'] === $statut_filtre;
        });
    }
    
    $projets = $projets_defaut;
}
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold text-primary mb-3">Projets Sociaux</h1>
            <p class="lead">
                SOCOU_U s'engage dans des projets de développement communautaire qui visent à 
                améliorer les conditions de vie des populations rurales et à promouvoir un 
                développement durable et inclusif.
            </p>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle p-2 me-3">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Impact Communautaire</h6>
                            <small class="text-muted">Social & économique</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle p-2 me-3">
                            <i class="fas fa-handshake text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Partenariats</h6>
                            <small class="text-muted">Collaboration</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle p-2 me-3">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Résultats Mesurables</h6>
                            <small class="text-muted">Impact quantifié</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-center">
            <i class="fas fa-heart fa-5x text-primary opacity-75"></i>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-0">
                                <i class="fas fa-filter me-2"></i>Filtrer par statut :
                            </h6>
                        </div>
                        <div class="col-md-9">
                            <div class="btn-group w-100" role="group">
                                <a href="<?php echo SITE_URL; ?>/projets.php" 
                                   class="btn <?php echo empty($statut_filtre) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-th-large me-1"></i>Tous
                                </a>
                                <a href="<?php echo SITE_URL; ?>/projets.php?statut=en_cours" 
                                   class="btn <?php echo $statut_filtre == 'en_cours' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <i class="fas fa-play me-1"></i>En cours
                                </a>
                                <a href="<?php echo SITE_URL; ?>/projets.php?statut=planifie" 
                                   class="btn <?php echo $statut_filtre == 'planifie' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    <i class="fas fa-calendar me-1"></i>Planifiés
                                </a>
                                <a href="<?php echo SITE_URL; ?>/projets.php?statut=termine" 
                                   class="btn <?php echo $statut_filtre == 'termine' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                                    <i class="fas fa-check me-1"></i>Terminés
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projets -->
    <?php if (!empty($projets)): ?>
        <div class="row">
            <?php foreach($projets as $projet): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 projet-card" data-projet-id="<?php echo $projet['id_projet']; ?>">
                        <!-- En-tête avec badge statut -->
                        <div class="card-header border-0 bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0"><?php echo escape($projet['nom_projet']); ?></h5>
                                </div>
                                <div class="col-auto">
                                    <?php
                                    $badge_class = 'badge bg-info';
                                    $badge_text = 'Planifié';
                                    $badge_icon = 'fas fa-calendar';
                                    
                                    switch($projet['statut']) {
                                        case 'en_cours':
                                            $badge_class = 'badge bg-success';
                                            $badge_text = 'En cours';
                                            $badge_icon = 'fas fa-play';
                                            break;
                                        case 'termine':
                                            $badge_class = 'badge bg-secondary';
                                            $badge_text = 'Terminé';
                                            $badge_icon = 'fas fa-check';
                                            break;
                                        case 'suspendu':
                                            $badge_class = 'badge bg-warning';
                                            $badge_text = 'Suspendu';
                                            $badge_icon = 'fas fa-pause';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $badge_class; ?>">
                                        <i class="<?php echo $badge_icon; ?> me-1"></i><?php echo $badge_text; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Description -->
                            <p class="text-muted">
                                <?php echo escape(substr($projet['description'], 0, 150)) . '...'; ?>
                            </p>

                            <!-- Informations principales -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($projet['date_debut'])); ?>
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($projet['date_fin'])); ?>
                                    </small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo number_format($projet['beneficiaires_cibles'], 0, ',', ' '); ?> bénéficiaires ciblés
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo escape($projet['zone_intervention']); ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Progression des bénéficiaires -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progression des bénéficiaires</small>
                                    <small class="text-muted participant-count">
                                        <?php echo $projet['nb_beneficiaires']; ?>/<?php echo $projet['beneficiaires_cibles']; ?>
                                    </small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <?php 
                                    $pourcentage_beneficiaires = ($projet['nb_beneficiaires'] / $projet['beneficiaires_cibles']) * 100;
                                    $couleur = $pourcentage_beneficiaires < 30 ? 'bg-danger' : ($pourcentage_beneficiaires < 70 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $couleur; ?>" 
                                         style="width: <?php echo min($pourcentage_beneficiaires, 100); ?>%">
                                    </div>
                                </div>
                            </div>

                            <!-- Budget -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Budget utilisé</small>
                                    <small class="text-muted">
                                        <?php 
                                        $pourcentage_budget = ($projet['budget_depense'] / $projet['budget_previsto']) * 100;
                                        echo number_format($pourcentage_budget, 1); ?>%
                                    </small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" 
                                         style="width: <?php echo min($pourcentage_budget, 100); ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo number_format($projet['budget_depense'], 0, ',', ' '); ?> / 
                                    <?php echo number_format($projet['budget_previsto'], 0, ',', ' '); ?> FBU
                                </small>
                            </div>

                            <!-- Partenaires -->
                            <?php if (!empty($projet['partenaires'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-handshake me-1"></i>
                                        <strong>Partenaires:</strong> <?php echo escape($projet['partenaires']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent border-0">
                            <div class="d-grid gap-2">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#projetModal"
                                        onclick="afficherDetailsProjet(<?php echo htmlspecialchars(json_encode($projet)); ?>)">
                                    <i class="fas fa-info-circle me-1"></i>Voir détails
                                </button>
                                
                                <?php if ($projet['statut'] == 'en_cours'): ?>
                                    <button type="button" 
                                            class="btn btn-success btn-sm btn-participer"
                                            onclick="participerProjet(<?php echo $projet['id_projet']; ?>)"
                                            data-projet-id="<?php echo $projet['id_projet']; ?>">
                                        <i class="fas fa-heart me-1"></i>Participer/Soutenir
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Impact global -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h4 class="text-center mb-4">Notre Impact Social</h4>
                        <div class="row text-center">
                            <?php
                            $total_beneficiaires = array_sum(array_column($projets, 'nb_beneficiaires'));
                            $total_budget = array_sum(array_column($projets, 'budget_depense'));
                            $projets_actifs = count(array_filter($projets, function($p) { return $p['statut'] == 'en_cours'; }));
                            $projets_termines = count(array_filter($projets, function($p) { return $p['statut'] == 'termine'; }));
                            ?>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-primary"><?php echo number_format($total_beneficiaires); ?></h3>
                                <p class="mb-0">Bénéficiaires actuels</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-success"><?php echo $projets_actifs; ?></h3>
                                <p class="mb-0">Projets en cours</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-info"><?php echo $projets_termines; ?></h3>
                                <p class="mb-0">Projets réalisés</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h3 class="text-warning"><?php echo number_format($total_budget / 1000000, 1); ?>M</h3>
                                <p class="mb-0">Budget mobilisé (FBU)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Aucun projet trouvé -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucun projet trouvé</h4>
                    <p class="text-muted">
                        <?php if ($statut_filtre): ?>
                            Aucun projet avec le statut "<?php echo escape($statut_filtre); ?>" n'est disponible pour le moment.
                        <?php else: ?>
                            Aucun projet social n'est disponible pour le moment.
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/projets.php" class="btn btn-primary">
                        <i class="fas fa-th-large me-2"></i>Voir tous les projets
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Objectifs de Développement Durable -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-5">Contribution aux ODD</h3>
            <p class="text-center text-muted mb-4">
                Nos projets sociaux s'alignent sur les Objectifs de Développement Durable des Nations Unies
            </p>
            <div class="row justify-content-center">
                <div class="col-md-2 col-4 mb-3 text-center">
                    <div class="bg-danger rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="text-white fw-bold">1</span>
                    </div>
                    <small>Pas de pauvreté</small>
                </div>
                <div class="col-md-2 col-4 mb-3 text-center">
                    <div class="bg-warning rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="text-white fw-bold">2</span>
                    </div>
                    <small>Faim zéro</small>
                </div>
                <div class="col-md-2 col-4 mb-3 text-center">
                    <div class="bg-success rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="text-white fw-bold">5</span>
                    </div>
                    <small>Égalité des sexes</small>
                </div>
                <div class="col-md-2 col-4 mb-3 text-center">
                    <div class="bg-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="text-white fw-bold">8</span>
                    </div>
                    <small>Travail décent</small>
                </div>
                <div class="col-md-2 col-4 mb-3 text-center">
                    <div class="bg-info rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <span class="text-white fw-bold">17</span>
                    </div>
                    <small>Partenariats</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to action -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white text-center p-5">
                    <h3 class="fw-bold mb-3">
                        <i class="fas fa-hands-helping me-2"></i>Rejoignez nos Projets Sociaux
                    </h3>
                    <p class="lead mb-4">
                        Votre participation peut faire la différence dans la vie de nombreuses familles. 
                        Ensemble, construisons un avenir meilleur pour nos communautés.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo SITE_URL; ?>/mes-participations.php" class="btn btn-light btn-lg me-md-3 mb-2 mb-md-0">
                                <i class="fas fa-user-check me-2"></i>Mes Participations
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/inscription.php" class="btn btn-light btn-lg me-md-3 mb-2 mb-md-0">
                                <i class="fas fa-user-plus me-2"></i>Rejoindre SOCOU_U
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-envelope me-2"></i>Nous Contacter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails projet -->
<div class="modal fade" id="projetModal" tabindex="-1" aria-labelledby="projetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="projetModalLabel">Détails du projet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="projetModalBody">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" id="participerModalBtn" style="display: none;">
                    <i class="fas fa-heart me-2"></i>Participer/Soutenir
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.projet-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.projet-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    transition: width 0.6s ease;
}

.btn-group .btn {
    flex: 1;
}

.highlight-project {
    animation: highlight 2s ease-in-out;
}

@keyframes highlight {
    0%, 100% { background-color: transparent; }
    50% { background-color: rgba(0, 123, 255, 0.1); }
}

.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
    
    .display-4 {
        font-size: 2.5rem;
    }
}

.badge {
    font-size: 0.75em;
}

.rounded-circle {
    transition: transform 0.3s ease;
}

.card:hover .rounded-circle {
    transform: scale(1.05);
}

/* Style pour les alertes flottantes */
.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<script>
// Variables globales
let projetActuel = null;
const SITE_URL = '<?php echo SITE_URL; ?>';

function afficherDetailsProjet(projet) {
    projetActuel = projet;
    
    const modalBody = document.getElementById('projetModalBody');
    const modalLabel = document.getElementById('projetModalLabel');
    const participerBtn = document.getElementById('participerModalBtn');
    
    modalLabel.textContent = projet.nom_projet;
    
    const pourcentageBeneficiaires = (projet.nb_beneficiaires / projet.beneficiaires_cibles) * 100;
    const pourcentageBudget = (projet.budget_depense / projet.budget_previsto) * 100;
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h5>Description complète</h5>
                <p>${projet.description}</p>
                
                <h6>Objectif principal</h6>
                <p class="text-muted">${projet.objectif}</p>
                
                ${projet.resultats_attendus ? `
                    <h6>Résultats attendus</h6>
                    <p class="text-muted">${projet.resultats_attendus}</p>
                ` : ''}
                
                <h6>Zone d'intervention</h6>
                <p><i class="fas fa-map-marker-alt text-primary me-2"></i>${projet.zone_intervention}</p>
                
                ${projet.partenaires ? `
                    <h6>Partenaires</h6>
                    <p><i class="fas fa-handshake text-success me-2"></i>${projet.partenaires}</p>
                ` : ''}
            </div>
            <div class="col-md-4">
                <div class="card bg-light mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Informations clés</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Période :</strong><br>
                            <small>${new Date(projet.date_debut).toLocaleDateString('fr-FR')} - ${new Date(projet.date_fin).toLocaleDateString('fr-FR')}</small>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Budget total :</strong><br>
                            <span class="text-primary">${new Intl.NumberFormat().format(projet.budget_previsto)} FBU</span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Budget utilisé :</strong>
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: ${Math.min(pourcentageBudget, 100)}%"></div>
                            </div>
                            <small>${new Intl.NumberFormat().format(projet.budget_depense)} FBU (${pourcentageBudget.toFixed(1)}%)</small>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Bénéficiaires :</strong>
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${Math.min(pourcentageBeneficiaires, 100)}%"></div>
                            </div>
                            <small id="beneficiaires-count-modal">${projet.nb_beneficiaires}/${projet.beneficiaires_cibles} (${pourcentageBeneficiaires.toFixed(1)}%)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    if (participerBtn) {
        participerBtn.style.display = projet.statut == 'en_cours' ? 'inline-block' : 'none';
        participerBtn.onclick = function() {
            participerProjet(projet.id_projet);
        };
    }
}

function participerProjet(projetId) {
    // Vérifier si l'utilisateur est connecté (côté client)
    <?php if (!isLoggedIn()): ?>
    if (confirm('Vous devez être connecté pour participer à un projet. Souhaitez-vous vous inscrire ?')) {
        window.location.href = `${SITE_URL}/inscription.php`;
    }
    return;
    <?php endif; ?>
    
    // Confirmer la participation
    if (!confirm('Êtes-vous sûr de vouloir participer à ce projet ? Nous vous contacterons pour plus de détails sur votre rôle et les prochaines étapes.')) {
        return;
    }
    
    // Désactiver les boutons pendant la requête
    const buttons = document.querySelectorAll(`button[onclick*="participerProjet(${projetId})"]`);
    const modalBtn = document.getElementById('participerModalBtn');
    
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>En cours...';
    });
    
    if (modalBtn) {
        modalBtn.disabled = true;
        modalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>En cours...';
    }
    
    fetch(`${SITE_URL}/ajax/participer_projet.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            projet_id: projetId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Afficher message de succès
            showAlert(data.message, 'success');
            
            // Fermer le modal si ouvert
            const modal = document.getElementById('projetModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
            
            // Mettre à jour l'interface
            if (data.nouveau_nb_participants) {
                updateParticipantsCount(projetId, data.nouveau_nb_participants);
            }
            
            // Masquer les boutons de participation pour ce projet
            buttons.forEach(btn => {
                const cardFooter = btn.closest('.card-footer');
                if (cardFooter) {
                    btn.style.display = 'none';
                    
                    // Ajouter un message de confirmation
                    const successMsg = document.createElement('div');
                    successMsg.className = 'alert alert-success alert-sm mt-2';
                    successMsg.innerHTML = '<i class="fas fa-check me-1"></i>Participation enregistrée';
                    cardFooter.appendChild(successMsg);
                }
            });
            
            // Proposer d'aller voir ses participations
            setTimeout(() => {
                if (confirm('Souhaitez-vous consulter l\'historique de vos participations ?')) {
                    window.location.href = `${SITE_URL}/mes-participations.php`;
                }
            }, 2000);
            
        } else {
            // Afficher l'erreur
            showAlert('Erreur : ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Une erreur est survenue. Veuillez réessayer plus tard.', 'danger');
    })
    .finally(() => {
        // Réactiver les boutons
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-heart me-1"></i>Participer/Soutenir';
        });
        
        if (modalBtn) {
            modalBtn.disabled = false;
            modalBtn.innerHTML = '<i class="fas fa-heart me-2"></i>Participer/Soutenir';
        }
    });
}

function updateParticipantsCount(projetId, newCount) {
    // Mettre à jour les compteurs de participants dans l'interface
    const projectCard = document.querySelector(`[data-projet-id="${projetId}"]`);
    
    if (projectCard) {
        const countElement = projectCard.querySelector('.participant-count');
        if (countElement) {
            // Supposant un format "X/Y"
            const text = countElement.textContent;
            const total = text.split('/')[1];
            countElement.textContent = `${newCount}/${total}`;
            
            // Mettre à jour la barre de progression
            const progressBar = projectCard.querySelector('.progress-bar');
            if (progressBar && total) {
                const totalNum = parseInt(total);
                const percentage = (newCount / totalNum) * 100;
                progressBar.style.width = `${Math.min(percentage, 100)}%`;
                
                // Changer la couleur en fonction du pourcentage
                progressBar.className = 'progress-bar';
                if (percentage < 30) {
                    progressBar.classList.add('bg-danger');
                } else if (percentage < 70) {
                    progressBar.classList.add('bg-warning');
                } else {
                    progressBar.classList.add('bg-success');
                }
            }
        }
    }
    
    // Mettre à jour dans le modal s'il est ouvert
    const modalCount = document.getElementById('beneficiaires-count-modal');
    if (modalCount && projetActuel && projetActuel.id_projet == projetId) {
        const total = projetActuel.beneficiaires_cibles;
        const percentage = (newCount / total) * 100;
        modalCount.textContent = `${newCount}/${total} (${percentage.toFixed(1)}%)`;
        
        // Mettre à jour l'objet projet actuel
        projetActuel.nb_beneficiaires = newCount;
    }
}

function showAlert(message, type) {
    // Créer et afficher une alerte Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-floating`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 150);
        }
    }, 5000);
}

// Fonction pour vérifier le statut de participation de l'utilisateur
function verifierStatutParticipation(projetId) {
    <?php if (isLoggedIn()): ?>
    fetch(`${SITE_URL}/ajax/verifier_participation.php?projet_id=${projetId}`)
    .then(response => response.json())
    .then(data => {
        if (data.participe) {
            // Masquer les boutons de participation
            const buttons = document.querySelectorAll(`button[data-projet-id="${projetId}"].btn-participer`);
            buttons.forEach(btn => {
                btn.style.display = 'none';
                const parent = btn.parentElement;
                if (parent) {
                    const statusMsg = document.createElement('div');
                    statusMsg.className = 'alert alert-info alert-sm mt-2';
                    statusMsg.innerHTML = '<i class="fas fa-check me-1"></i>Vous participez déjà à ce projet';
                    parent.appendChild(statusMsg);
                }
            });
            
            // Masquer aussi dans le modal
            const modalBtn = document.getElementById('participerModalBtn');
            if (modalBtn && projetActuel && projetActuel.id_projet == projetId) {
                modalBtn.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Erreur vérification participation:', error));
    <?php endif; ?>
}

// Vérifier les participations au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer tous les IDs de projets sur la page
    const projetCards = document.querySelectorAll('[data-projet-id]');
    projetCards.forEach(card => {
        const projetId = card.getAttribute('data-projet-id');
        if (projetId) {
            verifierStatutParticipation(projetId);
        }
    });
    
    // Gestion des ancres pour les projets spécifiques
    const projetIdFromUrl = getProjetIdFromUrl();
    if (projetIdFromUrl) {
        setTimeout(() => {
            const element = document.querySelector(`[data-projet-id="${projetIdFromUrl}"]`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.classList.add('highlight-project');
                setTimeout(() => element.classList.remove('highlight-project'), 3000);
            }
        }, 500);
    }
});

// Fonction utilitaire pour extraire l'ID du projet depuis l'URL
function getProjetIdFromUrl() {
    const hash = window.location.hash;
    if (hash.startsWith('#projet-')) {
        return hash.replace('#projet-', '');
    }
    return null;
}

// Gestion du filtrage responsive
window.addEventListener('resize', function() {
    const btnGroup = document.querySelector('.btn-group');
    if (btnGroup && window.innerWidth < 768) {
        btnGroup.classList.remove('btn-group');
        btnGroup.classList.add('btn-group-vertical');
    } else if (btnGroup && window.innerWidth >= 768) {
        btnGroup.classList.remove('btn-group-vertical');
        btnGroup.classList.add('btn-group');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>