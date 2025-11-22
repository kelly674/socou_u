<?php
$page_title = "Formations";
$page_description = "Découvrez nos programmes de formation pour renforcer les capacités des membres de la coopérative SOCOU_U";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

// Récupération des formations
$formations_query = "
    SELECT f.*, 
           COUNT(i.id_inscription) as nb_inscrits,
           (SELECT COUNT(*) FROM inscriptions_formations 
            WHERE id_formation = f.id_formation AND statut = 'inscrit') as places_restantes
    FROM formations f
    LEFT JOIN inscriptions_formations i ON f.id_formation = i.id_formation
    WHERE f.statut != 'annulee'
    GROUP BY f.id_formation
    ORDER BY f.date_formation ASC
";
$conn = getConnection();
$formations = $conn->query($formations_query)->fetch_all();

// Traitement de l'inscription à une formation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrire_formation'])) {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "Veuillez vous connecter pour vous inscrire à une formation.";
        $_SESSION['message_type'] = "warning";
    } else {
        $id_formation = (int)$_POST['id_formation'];
        $id_membre = $_SESSION['user_id'];
        
        // Vérifier si déjà inscrit
        $check_query = "SELECT id_inscription FROM inscriptions_formations 
                       WHERE id_formation = ? AND id_membre = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $id_formation, $id_membre);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            $_SESSION['message'] = "Vous êtes déjà inscrit à cette formation.";
            $_SESSION['message_type'] = "info";
        } else {
            // Vérifier les places disponibles
            $places_query = "SELECT f.max_participants,
                            COUNT(i.id_inscription) as nb_inscrits
                            FROM formations f
                            LEFT JOIN inscriptions_formations i ON f.id_formation = i.id_formation
                            WHERE f.id_formation = ?
                            GROUP BY f.id_formation";
            $places_stmt = $conn->prepare($places_query);
            $places_stmt->bind_param("i", $id_formation);
            $places_stmt->execute();
            $places_info = $places_stmt->get_result()->fetch_assoc();
            
            if ($places_info['nb_inscrits'] >= $places_info['max_participants']) {
                $_SESSION['message'] = "Désolé, cette formation est complète.";
                $_SESSION['message_type'] = "danger";
            } else {
                // Inscription
                $insert_query = "INSERT INTO inscriptions_formations 
                               (id_formation, id_membre, date_inscription) 
                               VALUES (?, ?, CURDATE())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ii", $id_formation, $id_membre);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['message'] = "Inscription réussie ! Vous recevrez plus d'informations par email.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Erreur lors de l'inscription. Veuillez réessayer.";
                    $_SESSION['message_type'] = "danger";
                }
            }
        }
        
        header("Location: formations.php");
        exit();
    }
}

// Séparer les formations par statut
$formations_programmees = array_filter($formations, function($f) { 
    return $f['statut'] === 'programmee' && $f['date_formation'] >= date('Y-m-d'); 
});
$formations_en_cours = array_filter($formations, function($f) { 
    return $f['statut'] === 'en_cours'; 
});
$formations_terminees = array_filter($formations, function($f) { 
    return $f['statut'] === 'terminee'; 
});
?>

<div class="container py-5">
    <!-- En-tête de la section -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 fw-bold text-primary mb-3">
                <i class="fas fa-graduation-cap"></i> Nos Formations
            </h1>
            <p class="lead text-muted">
                SOCOU_U propose des programmes de formation pour renforcer les capacités de ses membres 
                dans les domaines de l'agriculture, de l'élevage, de la transformation et de la commercialisation.
            </p>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x mb-3"></i>
                    <h4><?php echo count($formations_programmees); ?></h4>
                    <p class="mb-0">Formations Programmées</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-play-circle fa-2x mb-3"></i>
                    <h4><?php echo count($formations_en_cours); ?></h4>
                    <p class="mb-0">En Cours</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h4><?php echo count($formations_terminees); ?></h4>
                    <p class="mb-0">Terminées</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <h4>
                        <?php 
                        $total_inscrits = array_sum(array_column($formations, 'nb_inscrits'));
                        echo $total_inscrits;
                        ?>
                    </h4>
                    <p class="mb-0">Participants Total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formations programmées -->
    <?php if (!empty($formations_programmees)): ?>
    <div class="mb-5">
        <h2 class="h3 mb-4 text-primary">
            <i class="fas fa-calendar-plus"></i> Formations Programmées
        </h2>
        <div class="row">
            <?php foreach ($formations_programmees as $formation): ?>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book"></i> <?php echo escape($formation['titre']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('d/m/Y', strtotime($formation['date_formation'])); ?>
                                <?php if ($formation['heure_debut']): ?>
                                    à <?php echo substr($formation['heure_debut'], 0, 5); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <p class="card-text"><?php echo escape($formation['description']); ?></p>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-user-tie"></i> 
                                    <strong>Formateur:</strong><br>
                                    <?php echo escape($formation['formateur']); ?>
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <strong>Lieu:</strong><br>
                                    <?php echo escape($formation['lieu']); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> 
                                    <strong>Durée:</strong><br>
                                    <?php echo $formation['duree_heures']; ?> heures
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-money-bill"></i> 
                                    <strong>Frais:</strong><br>
                                    <?php echo $formation['frais_participation'] > 0 ? 
                                        number_format($formation['frais_participation']) . ' BIF' : 'Gratuit'; ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Barre de progression des inscriptions -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Places disponibles</small>
                                <small class="text-muted">
                                    <?php echo $formation['nb_inscrits']; ?>/<?php echo $formation['max_participants']; ?>
                                </small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($formation['nb_inscrits']/$formation['max_participants'])*100; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if ($formation['objectifs']): ?>
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong><i class="fas fa-target"></i> Objectifs:</strong><br>
                                <?php echo nl2br(escape($formation['objectifs'])); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light">
                        <?php if ($formation['nb_inscrits'] >= $formation['max_participants']): ?>
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-times"></i> Complet
                            </button>
                        <?php elseif (isLoggedIn()): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="id_formation" value="<?php echo $formation['id_formation']; ?>">
                                <button type="submit" name="inscrire_formation" class="btn btn-success btn-sm">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-sign-in-alt"></i> Se connecter pour s'inscrire
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $formation['id_formation']; ?>">
                            <i class="fas fa-info-circle"></i> Plus de détails
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modal des détails -->
            <div class="modal fade" id="detailsModal<?php echo $formation['id_formation']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo escape($formation['titre']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-calendar"></i> Informations pratiques</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($formation['date_formation'])); ?></li>
                                        <li><strong>Heure:</strong> <?php echo $formation['heure_debut'] ? substr($formation['heure_debut'], 0, 5) : 'À définir'; ?></li>
                                        <li><strong>Durée:</strong> <?php echo $formation['duree_heures']; ?> heures</li>
                                        <li><strong>Lieu:</strong> <?php echo escape($formation['lieu']); ?></li>
                                        <li><strong>Formateur:</strong> <?php echo escape($formation['formateur']); ?></li>
                                        <li><strong>Places:</strong> <?php echo $formation['max_participants']; ?></li>
                                        <li><strong>Frais:</strong> <?php echo $formation['frais_participation'] > 0 ? 
                                            number_format($formation['frais_participation']) . ' BIF' : 'Gratuit'; ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-info-circle"></i> Description</h6>
                                    <p><?php echo nl2br(escape($formation['description'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($formation['objectifs']): ?>
                            <hr>
                            <h6><i class="fas fa-target"></i> Objectifs de la formation</h6>
                            <p><?php echo nl2br(escape($formation['objectifs'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($formation['supports']): ?>
                            <hr>
                            <h6><i class="fas fa-file-alt"></i> Supports fournis</h6>
                            <p><?php echo nl2br(escape($formation['supports'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <?php if ($formation['nb_inscrits'] >= $formation['max_participants']): ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-times"></i> Complet
                                </button>
                            <?php elseif (isLoggedIn()): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_formation" value="<?php echo $formation['id_formation']; ?>">
                                    <button type="submit" name="inscrire_formation" class="btn btn-success">
                                        <i class="fas fa-user-plus"></i> S'inscrire à cette formation
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Se connecter pour s'inscrire
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formations en cours -->
    <?php if (!empty($formations_en_cours)): ?>
    <div class="mb-5">
        <h2 class="h3 mb-4 text-warning">
            <i class="fas fa-play-circle"></i> Formations en Cours
        </h2>
        <div class="row">
            <?php foreach ($formations_en_cours as $formation): ?>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book"></i> <?php echo escape($formation['titre']); ?>
                        </h5>
                        <small><span class="badge bg-dark">En cours</span></small>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo escape($formation['description']); ?></p>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y', strtotime($formation['date_formation'])); ?>
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> 
                                    <?php echo $formation['nb_inscrits']; ?> participants
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formations terminées (les 6 plus récentes) -->
    <?php if (!empty($formations_terminees)): ?>
    <div class="mb-5">
        <h2 class="h3 mb-4 text-success">
            <i class="fas fa-check-circle"></i> Formations Récemment Terminées
        </h2>
        <div class="row">
            <?php 
            $formations_recentes = array_slice($formations_terminees, 0, 6);
            foreach ($formations_recentes as $formation): 
            ?>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="card-title mb-0">
                            <?php echo escape($formation['titre']); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('d/m/Y', strtotime($formation['date_formation'])); ?>
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-users"></i> 
                            <?php echo $formation['nb_inscrits']; ?> participants
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo escape($formation['formateur']); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($formations_terminees) > 6): ?>
        <div class="text-center">
            <button class="btn btn-outline-success" onclick="toggleHistorique()">
                <i class="fas fa-history"></i> Voir toutes les formations terminées
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Section d'information sur les formations -->
    <div class="row mt-5">
        <div class="col-lg-8 mx-auto">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h4 class="card-title text-primary">
                        <i class="fas fa-question-circle"></i> Pourquoi se former avec SOCOU_U ?
                    </h4>
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="mb-3">
                                <i class="fas fa-certificate fa-2x text-primary"></i>
                            </div>
                            <h6>Certification</h6>
                            <p class="small text-muted">
                                Obtenez des certificats reconnus pour valoriser vos compétences
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="mb-3">
                                <i class="fas fa-users fa-2x text-primary"></i>
                            </div>
                            <h6>Échange d'expériences</h6>
                            <p class="small text-muted">
                                Apprenez avec d'autres membres de la coopérative
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="mb-3">
                                <i class="fas fa-chart-line fa-2x text-primary"></i>
                            </div>
                            <h6>Amélioration des performances</h6>
                            <p class="small text-muted">
                                Augmentez votre productivité et vos revenus
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!isLoggedIn()): ?>
                    <div class="mt-4">
                        <a href="<?php echo SITE_URL; ?>/inscription.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Rejoindre SOCOU_U
                        </a>
                        <p class="small text-muted mt-2">
                            Devenez membre pour accéder à nos formations
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress {
    border-radius: 10px;
}
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-5px);
}
.badge {
    font-size: 0.7em;
}
</style>

<script>
function toggleHistorique() {
    // Fonction pour afficher/masquer l'historique complet
    // À implémenter selon les besoins
    alert('Fonctionnalité à venir : Affichage de toutes les formations terminées');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>